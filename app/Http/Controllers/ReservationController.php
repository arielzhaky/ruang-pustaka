<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BookReservation;
use App\Models\LoanItem;
use App\Models\Member;
use App\Services\ReservationQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['page' => ['nullable', 'integer', 'min:1'], 'status' => ['nullable', 'in:active,history']]);
        $query = BookReservation::with(['book', 'member.user', 'bookCopy']);
        if ($request->user()->role !== 'petugas') {
            abort_unless($request->user()->role === 'anggota', 403);
            $query->whereHas('member', fn ($q) => $q->where('user_id', $request->user()->id));
        }
        if ($request->input('status', 'active') === 'active') {
            $query->active();
        } else {
            $query->where(fn ($q) => $q->whereNotIn('status', ['waiting', 'ready'])->orWhere(fn ($q) => $q->where('status', 'ready')->where('expires_at', '<=', now())));
        }
        $reservations = $query->orderBy('id')->paginate(15)->withQueryString();
        foreach ($reservations as $reservation) {
            $reservation->queue_position = $reservation->status === 'waiting'
                ? BookReservation::where('book_id', $reservation->book_id)->where('status', 'waiting')->where('id', '<=', $reservation->id)->count() : null;
        }

        return view('reservations.index', compact('reservations'));
    }

    public function store(Request $request, Book $book)
    {
        abort_unless($request->user()->role === 'anggota', 403);
        DB::transaction(function () use ($request, $book) {
            $member = Member::where('user_id', $request->user()->id)->lockForUpdate()->first();
            abort_unless($member, 403, 'Profil anggota belum tersedia.');
            $book = Book::lockForUpdate()->findOrFail($book->id);
            abort_if($book->archived_at, 404);
            ReservationQueue::expire($book->id);
            if (BookReservation::active()->where('member_id', $member->id)->where('book_id', $book->id)->exists()) {
                return;
            }
            if (! $book->copies()->where('import_unavailable', false)->whereNull('archived_at')->where('condition', 'baik')->exists()) {
                throw ValidationException::withMessages(['reservation' => 'Reservasi hanya tersedia untuk judul dengan eksemplar fisik berkondisi baik.']);
            }
            if (BookReservation::active()->where('member_id', $member->id)->count() >= config('library.max_reservations')) {
                throw ValidationException::withMessages(['reservation' => 'Batas reservasi aktif tercapai. Batalkan atau selesaikan reservasi sebelumnya.']);
            }
            if (LoanItem::whereNull('returned_at')->whereHas('loan', fn ($q) => $q->where('member_id', $member->id))->whereHas('bookCopy', fn ($q) => $q->where('book_id', $book->id))->exists()) {
                throw ValidationException::withMessages(['reservation' => 'Kamu masih meminjam judul ini. Kembalikan terlebih dahulu.']);
            }
            BookReservation::create(['member_id' => $member->id, 'book_id' => $book->id, 'active_key' => $member->id.':'.$book->id]);
        }, 3);

        return redirect()->route('reservations.index')->with('success', 'Reservasi tercatat. Pantau status siap diambil di halaman ini.');
    }

    public function ready(Request $request, BookReservation $reservation)
    {
        DB::transaction(function () use ($request, $reservation) {
            $member = Member::with('user')->lockForUpdate()->findOrFail($reservation->member_id);
            if (! $member->user?->is_active) {
                throw ValidationException::withMessages(['reservation' => 'Akun siswa sudah tidak aktif. Batalkan reservasinya.']);
            }
            // Kunci eksemplar sebelum judul, sejalan dengan transaksi peminjaman.
            $copy = BookCopy::where('book_id', $reservation->book_id)->available()->orderBy('id')->lockForUpdate()->first();
            $book = Book::lockForUpdate()->findOrFail($reservation->book_id);
            ReservationQueue::expire($book->id);
            $row = BookReservation::lockForUpdate()->findOrFail($reservation->id);
            $first = BookReservation::where('book_id', $book->id)->where('status', 'waiting')->orderBy('id')->lockForUpdate()->first();
            $copyBusy = $copy && (LoanItem::where('book_copy_id', $copy->id)->whereNull('returned_at')->lockForUpdate()->first()
                || BookReservation::where('book_copy_id', $copy->id)->where('status', 'ready')->where('expires_at', '>', now())->lockForUpdate()->first());
            if ($copyBusy || $row->status !== 'waiting' || $first?->id !== $row->id || ! $copy || $book->archived_at) {
                throw ValidationException::withMessages(['reservation' => 'Siapkan reservasi paling awal ketika eksemplar tersedia dan buku belum diarsipkan.']);
            }
            if (LoanItem::whereNull('returned_at')->whereHas('loan', fn ($q) => $q->where('member_id', $member->id))->count() >= config('library.max_active_loans')) {
                throw ValidationException::withMessages(['reservation' => 'Kuota pinjaman siswa masih penuh.']);
            }
            $row->update(['status' => 'ready', 'book_copy_id' => $copy->id, 'ready_at' => now(), 'expires_at' => now()->addDays(config('library.reservation_hold_days')), 'handled_by' => $request->user()->id]);
        }, 3);

        return back()->with('success', 'Buku siap diambil. Eksemplar ditahan sampai batas pengambilan.');
    }

    public function cancel(Request $request, BookReservation $reservation)
    {
        abort_unless($request->user()->role === 'petugas' || $reservation->member->user_id === $request->user()->id, 404);
        DB::transaction(function () use ($reservation) {
            Member::whereKey($reservation->member_id)->lockForUpdate()->first();
            Book::whereKey($reservation->book_id)->lockForUpdate()->first();
            ReservationQueue::expire($reservation->book_id);
            $row = BookReservation::lockForUpdate()->findOrFail($reservation->id);
            if (in_array($row->status, ['waiting', 'ready'])) {
                $row->update(['status' => 'cancelled', 'active_key' => null]);
            }
        }, 3);

        return back()->with('success', 'Reservasi sudah tidak aktif.');
    }
}
