<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BookReservation;
use App\Models\LoanItem;
use App\Models\LoanRenewal;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RenewalController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['status' => ['nullable', 'in:pending,approved,rejected'], 'page' => ['nullable', 'integer', 'min:1']]);
        $query = LoanRenewal::with(['loanItem.bookCopy.book', 'loanItem.loan.member.user']);
        if ($request->user()->role !== 'petugas') {
            abort_unless($request->user()->role === 'anggota', 403);
            $query->whereHas('loanItem.loan.member', fn ($q) => $q->where('user_id', $request->user()->id));
        }
        $renewals = $query->when($request->status, fn ($q, $s) => $q->where('status', $s))->latest('id')->paginate(15)->withQueryString();

        return view('renewals.index', compact('renewals'));
    }

    private function lockedItem(LoanItem $item): LoanItem
    {
        Member::whereKey($item->loan->member_id)->lockForUpdate()->first();
        $copy = BookCopy::whereKey($item->book_copy_id)->lockForUpdate()->firstOrFail();
        Book::whereKey($copy->book_id)->lockForUpdate()->firstOrFail();

        return LoanItem::with(['bookCopy.book', 'loan.member.user'])->lockForUpdate()->findOrFail($item->id);
    }

    private function check(LoanItem $item): void
    {
        if ($item->returned_at || $item->due_date->lt(today()) || $item->bookCopy->archived_at || $item->bookCopy->book->archived_at || ! $item->loan->member->user->is_active) {
            throw ValidationException::withMessages(['renewal' => 'Perpanjangan hanya berlaku untuk pinjaman aktif yang belum terlambat, siswa aktif, dan buku belum diarsipkan.']);
        }
        if (BookReservation::active()->where('book_id', $item->bookCopy->book_id)->lockForUpdate()->first()) {
            throw ValidationException::withMessages(['renewal' => 'Buku sedang direservasi siswa lain. Perpanjangan belum dapat disetujui.']);
        }
    }

    public function store(Request $request, LoanItem $loanItem)
    {
        abort_unless($request->user()->role === 'anggota' && $loanItem->loan->member->user_id === $request->user()->id, 404);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        DB::transaction(function () use ($loanItem, $data) {
            $item = $this->lockedItem($loanItem);
            $this->check($item);
            $existing = $item->renewal()->first();
            if ($existing && $existing->status !== 'rejected') {
                throw ValidationException::withMessages(['renewal' => 'Sudah ada permintaan atau pinjaman ini sudah pernah diperpanjang.']);
            }
            $item->renewal()->updateOrCreate(['loan_item_id' => $item->id], ['status' => 'pending', 'reason' => $data['reason'] ?? null,
                'original_due_date' => $item->due_date, 'requested_due_date' => $item->due_date->copy()->addDays(config('library.renewal_days')),
                'reviewed_by' => null, 'reviewed_at' => null, 'decision_note' => null]);
        }, 3);

        return redirect()->route('renewals.index')->with('success', 'Permintaan dikirim ke petugas. Batas kembali tetap berlaku sampai disetujui.');
    }

    public function review(Request $request, LoanRenewal $renewal)
    {
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'decision_note' => ['required_if:decision,rejected', 'nullable', 'string', 'max:500']]);
        DB::transaction(function () use ($request, $renewal, $data) {
            $item = $this->lockedItem($renewal->loanItem);
            $row = LoanRenewal::lockForUpdate()->findOrFail($renewal->id);
            if ($row->status !== 'pending') {
                throw ValidationException::withMessages(['renewal' => 'Permintaan ini sudah ditinjau.']);
            }
            if ($data['decision'] === 'approved') {
                $this->check($item);
                if (! $item->due_date->isSameDay($row->original_due_date)) {
                    throw ValidationException::withMessages(['renewal' => 'Batas kembali telah berubah. Tolak permintaan lalu ajukan ulang.']);
                }
                $item->update(['due_date' => $row->requested_due_date]);
            }
            $row->update(['status' => $data['decision'], 'decision_note' => $data['decision_note'] ?? null, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        }, 3);

        return back()->with('success', 'Keputusan perpanjangan berhasil disimpan.');
    }
}
