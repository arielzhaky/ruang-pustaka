<?php

namespace App\Http\Controllers;

use App\Models\BookReservation;
use App\Services\ReservationQueue;
use App\Support\LoanCharges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoanController extends Controller
{
    public function create(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'member_id' => ['nullable', 'integer']]);
        $members = DB::table('members as m')
            ->join('users as u', 'u.id', '=', 'm.user_id')
            ->where('u.role', 'anggota')
            ->where('u.is_active', 1)
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->where('u.name', 'like', "%{$search}%")->orWhere('m.nis', 'like', "%{$search}%")->orWhere('m.member_code', 'like', "%{$search}%")))
            ->select('m.id', 'm.member_code', 'm.nis', 'u.name')
            ->orderBy('u.name')
            ->get();

        $copies = DB::table('book_copies as bc')
            ->join('books as b', 'b.id', '=', 'bc.book_id')
            ->whereNull('b.archived_at')
            ->whereNull('bc.archived_at')
            ->where('bc.condition', 'baik')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('loan_items as li')
                    ->whereColumn('li.book_copy_id', 'bc.id')
                    ->whereNull('li.returned_at');
            })
            ->select('bc.id', 'bc.inventory_code', 'b.title')
            ->orderBy('b.title')
            ->get();

        $heldCopies = BookReservation::with('member.user')->where('status', 'ready')->where('expires_at', '>', now())->get()->mapWithKeys(fn ($r) => [$r->book_copy_id => $r->member->user->name.' · '.$r->member->member_code]);
        $maxActiveLoans = config('library.max_active_loans');
        $loanDays = config('library.loan_days');
        $defaultDueDate = today()->addDays($loanDays)->toDateString();

        return view('loans.create', compact('heldCopies', 'members', 'copies', 'maxActiveLoans', 'loanDays', 'defaultDueDate'));
    }

    public function store(Request $request)
    {
        $maxActiveLoans = config('library.max_active_loans');
        $data = $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'book_copy_ids' => ['required', 'array', 'min:1', 'max:'.$maxActiveLoans],
            'book_copy_ids.*' => ['required', 'integer', 'distinct', 'exists:book_copies,id'],
            'due_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'member_id.required' => 'Pilih siswa yang akan meminjam.',
            'member_id.integer' => 'Siswa tidak valid.',
            'member_id.exists' => 'Siswa tidak ditemukan.',
            'book_copy_ids.required' => 'Pilih minimal satu eksemplar.',
            'book_copy_ids.array' => 'Pilihan eksemplar tidak valid.',
            'book_copy_ids.min' => 'Pilih minimal satu eksemplar.',
            'book_copy_ids.max' => "Maksimal {$maxActiveLoans} eksemplar dalam satu transaksi.",
            'book_copy_ids.*.required' => 'Pilihan eksemplar tidak boleh kosong.',
            'book_copy_ids.*.integer' => 'Pilihan eksemplar tidak valid.',
            'book_copy_ids.*.distinct' => 'Eksemplar yang sama tidak boleh dipilih dua kali.',
            'book_copy_ids.*.exists' => 'Salah satu eksemplar tidak ditemukan.',
            'due_date.date_format' => 'Tanggal pengembalian tidak valid.',
            'due_date.after_or_equal' => 'Batas pengembalian tidak boleh sebelum hari ini.',
            'notes.string' => 'Catatan harus berupa teks.',
            'notes.max' => 'Catatan maksimal 2000 karakter.',
        ]);

        DB::transaction(function () use ($data, $maxActiveLoans) {
            // Kunci anggota sebelum menghitung kuota agar dua petugas tidak
            // menambah pinjaman anggota yang sama melewati batas bersamaan.
            $member = DB::table('members as m')
                ->join('users as u', 'u.id', '=', 'm.user_id')
                ->where('m.id', $data['member_id'])
                ->where('u.role', 'anggota')
                ->where('u.is_active', 1)
                ->lockForUpdate()
                ->first();

            if (! $member) {
                throw ValidationException::withMessages([
                    'member_id' => 'Pilih siswa yang akunnya aktif.',
                ]);
            }

            // Locking read membaca data terbaru setelah menunggu transaksi lain.
            $activeCount = DB::table('loan_items as li')
                ->join('loans as l', 'l.id', '=', 'li.loan_id')
                ->where('l.member_id', $data['member_id'])
                ->whereNull('li.returned_at')
                ->select('li.id')
                ->lockForUpdate()
                ->get()->count();

            $copyIds = array_map('intval', $data['book_copy_ids']);
            sort($copyIds);

            if ($activeCount + count($copyIds) > $maxActiveLoans) {
                throw ValidationException::withMessages([
                    'book_copy_ids' => "Siswa masih meminjam {$activeCount} eksemplar. Batas pinjaman aktif adalah {$maxActiveLoans} eksemplar.",
                ]);
            }

            // Urutkan kunci agar pilihan yang terbalik tetap dikunci dengan urutan sama.
            $copies = DB::table('book_copies')->whereIn('id', $copyIds)
                ->orderBy('id')->lockForUpdate()->get();
            $books = DB::table('books')->whereIn('id', $copies->pluck('book_id')->unique())
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            if ($copies->count() !== count($copyIds)) {
                throw ValidationException::withMessages(['book_copy_ids' => 'Salah satu eksemplar tidak ditemukan.']);
            }

            foreach ($copies as $copy) {
                $book = $books->get($copy->book_id);
                if ($copy->archived_at !== null || $copy->condition !== 'baik' || ! $book || $book->archived_at !== null) {
                    throw ValidationException::withMessages([
                        'book_copy_ids' => "Eksemplar {$copy->inventory_code} tidak tersedia untuk dipinjam.",
                    ]);
                }
            }

            $borrowed = DB::table('loan_items')->whereIn('book_copy_id', $copyIds)
                ->whereNull('returned_at')->lockForUpdate()->first();
            if ($borrowed) {
                throw ValidationException::withMessages([
                    'book_copy_ids' => 'Salah satu eksemplar masih dipinjam. Muat ulang halaman dan pilih eksemplar lain.',
                ]);
            }

            $now = now();
            $dueDate = $data['due_date'] ?? $now->copy()->startOfDay()
                ->addDays(config('library.loan_days'))->toDateString();
            $loanId = DB::table('loans')->insertGetId([
                'loan_code' => 'PJM-'.Str::uuid(),
                'member_id' => $data['member_id'],
                'created_by' => auth()->id(),
                'borrowed_at' => $now,
                'notes' => $data['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($copies as $copy) {
                ReservationQueue::consume($copy->book_id, $copy->id, (int) $data['member_id']);
                DB::table('loan_items')->insert([
                    'loan_id' => $loanId,
                    'book_copy_id' => $copy->id,
                    'due_date' => $dueDate,
                    'fine_daily_rate' => config('library.fine_daily_rate'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }, 3);

        return redirect()->route('loans.index')
            ->with('success', 'Peminjaman '.count($data['book_copy_ids']).' eksemplar berhasil dicatat.');
    }

    public function index()
    {
        $loans = DB::table('loan_items as li')
            ->join('loans as l', 'l.id', '=', 'li.loan_id')
            ->join('members as m', 'm.id', '=', 'l.member_id')
            ->join('users as u', 'u.id', '=', 'm.user_id')
            ->join('book_copies as bc', 'bc.id', '=', 'li.book_copy_id')
            ->join('books as b', 'b.id', '=', 'bc.book_id')
            ->select(
                'li.id as item_id',
                'li.due_date',
                'li.returned_at',
                'li.return_condition',
                'li.fine_daily_rate', 'li.fine_amount', 'li.replacement_amount',
                'b.replacement_price',
                'l.loan_code',
                'l.borrowed_at',
                'u.name as student_name',
                'b.title as book_title',
                'bc.inventory_code'
            )
            ->orderByDesc('l.borrowed_at')
            ->paginate(10);

        return view('loans.index', compact('loans'));
    }

    public function returnItem(Request $request, int $loanItem)
    {
        $data = $request->validate([
            'return_condition' => ['required', 'in:baik,rusak,hilang'],
            'return_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($loanItem, $data) {
            $item = DB::table('loan_items')->where('id', $loanItem)->first();

            abort_unless($item, 404);

            $loan = DB::table('loans')->where('id', $item->loan_id)->first();
            // Kunci anggota, eksemplar, buku, lalu item sebelum menghitung biaya.
            DB::table('members')->where('id', $loan->member_id)->lockForUpdate()->first();
            $copy = DB::table('book_copies')
                ->where('id', $item->book_copy_id)
                ->lockForUpdate()
                ->first();

            $book = DB::table('books')->where('id', $copy->book_id)->lockForUpdate()->first();

            $item = DB::table('loan_items')
                ->where('id', $loanItem)
                ->lockForUpdate()
                ->first();

            if ($item->returned_at !== null) {
                throw ValidationException::withMessages([
                    'return_condition' => 'Eksemplar ini sudah dikembalikan.',
                ]);
            }

            $now = now();
            $lost = $data['return_condition'] === 'hilang';
            if ($lost && ! $book->replacement_price) {
                throw ValidationException::withMessages([
                    'return_condition' => 'Harga buku belum diisi. Lengkapi harga melalui Kelola Buku sebelum mencatat kehilangan.',
                ]);
            }
            $fine = $lost ? 0 : LoanCharges::lateFine($item, $now);
            $replacement = $lost ? $book->replacement_price : 0;

            DB::table('loan_items')
                ->where('id', $loanItem)
                ->update([
                    'returned_at' => $now,
                    'returned_by' => auth()->id(),
                    'return_condition' => $data['return_condition'],
                    'return_notes' => $data['return_notes'] ?? null,
                    'fine_amount' => $fine,
                    'billing_status' => ($fine + $replacement) > 0 ? 'unpaid' : 'none',
                    'replacement_amount' => $replacement,
                    'updated_at' => $now,
                ]);

            DB::table('loan_renewals')->where('loan_item_id', $loanItem)->where('status', 'pending')->update(['status' => 'rejected', 'decision_note' => 'Pinjaman sudah diselesaikan.', 'reviewed_by' => auth()->id(), 'reviewed_at' => $now, 'updated_at' => $now]);

            DB::table('book_copies')
                ->where('id', $item->book_copy_id)
                ->update([
                    'condition' => $data['return_condition'],
                    'updated_at' => $now,
                ]);
        }, 3);

        return redirect()->route('loans.index')
            ->with('success', $data['return_condition'] === 'hilang' ? 'Kehilangan dicatat. Biaya penggantian sesuai harga buku; eksemplar tidak tersedia untuk dipinjam.' : 'Pengembalian dan perhitungan denda berhasil dicatat.');
    }
}
