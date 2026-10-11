<?php

namespace App\Support;

use App\Models\Book;

class BookDetails
{
    public static function data(Book $book): array
    {
        abort_if($book->archived_at !== null, 404);
        $book->load('category');
        $availableCount = $book->copies()->available()->count();
        // Tidak menyertakan identitas siswa pada detail koleksi.
        $copies = $book->copies()->whereNull('archived_at')
            ->withExists(['loanItems as is_borrowed' => fn ($query) => $query->whereNull('returned_at')])
            ->withExists(['reservations as is_reserved' => fn ($q) => $q->where('status', 'ready')->where('expires_at', '>', now())])
            ->orderBy('inventory_code')->orderBy('id')
            ->paginate(10, ['*'], 'copies_page')->withQueryString();

        $canReserve = $book->copies()->where('import_unavailable', false)->whereNull('archived_at')->where('condition', 'baik')->exists();

        return compact('book', 'availableCount', 'copies', 'canReserve');
    }
}
