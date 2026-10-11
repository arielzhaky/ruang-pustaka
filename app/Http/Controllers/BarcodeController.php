<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BookReservation;
use App\Models\LoanItem;
use App\Models\Member;
use App\Support\LibraryBarcode;
use App\Support\LoanCharges;
use Illuminate\Http\Request;

class BarcodeController extends Controller
{
    public function index()
    {
        return view('barcodes.scan');
    }

    public function lookup(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:100'], 'kind' => ['required', 'in:any,member,copy'], 'member_id' => ['nullable', 'integer', 'min:1']]);
        $code = trim($data['code']);
        $code = preg_replace('/^\]C0/', '', $code);
        $parsed = LibraryBarcode::parse($code);
        if ($parsed && $data['kind'] !== 'any' && $data['kind'] !== $parsed['kind']) {
            return $this->json(['message' => $data['kind'] === 'member' ? 'Gunakan barcode kartu siswa pada kolom ini.' : 'Gunakan barcode eksemplar pada kolom ini.'], 422);
        }
        $member = null;
        $copy = null;
        if ($parsed) {
            if ($parsed['kind'] === 'member') {
                $member = Member::with(['user', 'schoolClass'])->find($parsed['id']);
            } else {
                $copy = BookCopy::with('book')->find($parsed['id']);
            }
        } else {
            // Kode anggota/inventaris lama tetap bisa diketik atau dibaca alat scan.
            if (in_array($data['kind'], ['any', 'member'])) {
                $member = Member::with(['user', 'schoolClass'])->where('member_code', $code)->first();
            }
            if (in_array($data['kind'], ['any', 'copy'])) {
                $copy = BookCopy::with('book')->where('inventory_code', $code)->first();
            }
        }
        if ($member && $member->user?->role !== 'anggota') {
            $member = null;
        }
        if ($member && $copy) {
            return $this->json(['message' => 'Kode ini dipakai siswa dan eksemplar. Pilih jenis data yang ingin dicari.'], 409);
        }
        if ($member) {
            $items = LoanItem::whereHas('loan', fn ($q) => $q->where('member_id', $member->id))->whereNull('returned_at');

            return $this->json(['kind' => 'member', 'id' => $member->id, 'barcode' => LibraryBarcode::member($member), 'name' => $member->user->name,
                'member_code' => $member->member_code, 'nis' => $member->nis, 'class_name' => $member->schoolClass?->name,
                'gender' => $member->gender, 'age' => $member->age, 'phone' => $member->phone, 'email' => $member->user->email,
                'active' => $member->user->is_active, 'active_count' => (clone $items)->count(), 'late_count' => (clone $items)->whereDate('due_date', '<', today())->count(),
                'urls' => ['edit' => route('members.edit', $member), 'card' => route('members.card', $member), 'borrow' => route('loans.create', ['member_id' => $member->id])]]);
        }
        if (! $copy) {
            return $this->json(['message' => 'Barcode tidak ditemukan. Periksa kartu/label atau masukkan kode anggota/kode inventaris.'], 404);
        }
        $item = $copy->loanItems()->with('loan.member.user')->whereNull('returned_at')->first();
        $hold = BookReservation::with('member.user')->where('book_copy_id', $copy->id)->where('status', 'ready')->where('expires_at', '>', now())->first();
        $waiting = BookReservation::where('book_id', $copy->book_id)->where('status', 'waiting')->orderBy('id')->first();
        $selectedMember = (int) ($data['member_id'] ?? 0);
        $status = 'available';
        $reason = null;
        if ($copy->archived_at || $copy->book->archived_at) {
            $status = 'archived';
            $reason = 'Eksemplar atau judul sudah diarsipkan/nonaktif.';
        } elseif ($copy->import_unavailable) {
            $status = 'unavailable';
            $reason = 'Belum tersedia menurut data impor. Periksa eksemplar melalui menu Stok / Eksemplar.';
        } elseif ($copy->condition !== 'baik') {
            $status = $copy->condition;
            $reason = 'Kondisi eksemplar '.$copy->condition.'.';
        } elseif ($item) {
            $status = 'borrowed';
            $reason = 'Eksemplar masih dipinjam.';
        } elseif ($hold) {
            $status = 'reserved';
            if ($hold->member_id !== $selectedMember) {
                $reason = 'Eksemplar ini disiapkan untuk '.$hold->member->user->name.'.';
            }
        }
        if (! $reason && ! $hold && $waiting && $waiting->member_id !== $selectedMember) {
            $reason = 'Judul ini memiliki antrean reservasi. Layani siswa paling awal terlebih dahulu.';
        }
        if (! $reason && ! $hold && $selectedMember && BookReservation::where('book_id', $copy->book_id)->where('member_id', $selectedMember)->where('status', 'ready')->where('expires_at', '>', now())->exists()) {
            $reason = 'Gunakan eksemplar yang sudah disiapkan untuk reservasi siswa ini.';
        }

        return $this->json(['kind' => 'copy', 'id' => $copy->id, 'barcode' => LibraryBarcode::copy($copy), 'title' => $copy->book->title, 'author' => $copy->book->author,
            'inventory_code' => $copy->inventory_code, 'shelf_location' => $copy->shelf_location, 'condition' => $copy->condition,
            'status' => $status, 'can_borrow' => $reason === null, 'borrow_reason' => $reason, 'replacement_price' => $copy->book->replacement_price,
            'loan' => $item ? ['id' => $item->id, 'loan_code' => $item->loan->loan_code, 'student_name' => $item->loan->member->user->name,
                'member_code' => $item->loan->member->member_code, 'due_date' => $item->due_date->format('d/m/Y'), 'late_days' => LoanCharges::lateDays($item),
                'estimated_fine' => LoanCharges::lateFine($item), 'return_url' => route('loans.items.return', $item)] : null,
            'hold' => $hold ? ['student_name' => $hold->member->user->name, 'expires_at' => $hold->expires_at->format('d/m/Y H:i')] : null,
            'urls' => ['book' => route('books.show', $copy->book), 'copies' => route('copies.index', $copy->book), 'label' => route('barcodes.copies.print', ['book' => $copy->book, 'copy_id' => $copy->id])]]);
    }

    public function memberCard(Member $member)
    {
        $member->load(['user', 'schoolClass']);
        abort_unless($member->user?->role === 'anggota', 404);

        return response()->view('members.card', ['studentProfile' => $member, 'staffCard' => true])
            ->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    public function copyLabels(Request $request, Book $book)
    {
        $data = $request->validate(['copy_id' => ['nullable', 'integer', 'min:1'], 'page' => ['nullable', 'integer', 'min:1']]);
        $query = $book->copies()->orderBy('inventory_code')->orderBy('id');
        if ($data['copy_id'] ?? null) {
            $query->whereKey($data['copy_id']);
        } else {
            // Label massal hanya untuk eksemplar aktif, paling banyak 100 per halaman.
            $query->whereNull('archived_at');
        }
        $copies = $query->paginate(100)->withQueryString();
        if (($data['copy_id'] ?? null) && $copies->isEmpty()) {
            abort(404);
        }

        return view('barcodes.copy-labels', compact('book', 'copies'));
    }

    private function json(array $data, int $status = 200)
    {
        return response()->json($data, $status)->header('Cache-Control', 'no-store, private');
    }
}
