<?php

namespace App\Services;

use App\Models\BookReservation;
use Illuminate\Validation\ValidationException;

class ReservationQueue
{
    // Pemanggil harus mengunci baris buku lebih dahulu dalam transaksi.
    public static function expire(int $bookId): void
    {
        BookReservation::where('book_id', $bookId)->where('status', 'ready')->where('expires_at', '<=', now())
            ->update(['status' => 'expired', 'active_key' => null, 'updated_at' => now()]);
    }

    public static function consume(int $bookId, int $copyId, int $memberId): void
    {
        self::expire($bookId);
        $held = BookReservation::where('book_copy_id', $copyId)->where('status', 'ready')->lockForUpdate()->first();
        if ($held && $held->member_id !== $memberId) {
            throw ValidationException::withMessages(['book_copy_ids' => 'Eksemplar ini disiapkan untuk siswa lain yang melakukan reservasi.']);
        }
        $reservation = $held;
        if (! $held) {
            if (BookReservation::where('book_id', $bookId)->where('member_id', $memberId)->where('status', 'ready')->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['book_copy_ids' => 'Gunakan kode eksemplar yang sudah disiapkan pada reservasi siswa ini.']);
            }
            // Reservasi siap sudah memiliki eksemplar sendiri; eksemplar bebas mengikuti antrean menunggu.
            $first = BookReservation::where('book_id', $bookId)->where('status', 'waiting')->orderBy('id')->lockForUpdate()->first();
            if ($first && $first->member_id !== $memberId) {
                throw ValidationException::withMessages(['book_copy_ids' => 'Judul ini memiliki antrean reservasi. Layani siswa paling awal atau batalkan antreannya terlebih dahulu.']);
            }
            $reservation = $first;
        }
        if ($reservation) {
            $reservation->update(['status' => 'fulfilled', 'active_key' => null, 'book_copy_id' => $copyId, 'completed_at' => now(), 'handled_by' => auth()->id()]);
        }
    }
}
