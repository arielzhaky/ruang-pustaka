<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookCopy extends Model
{
    protected $fillable = [
        'book_id',
        'inventory_code',
        'shelf_location',
        'condition',
        'import_unavailable',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'import_unavailable' => 'boolean',
        ];
    }

    // Digunakan katalog dan detail agar jumlah tersedia dihitung dengan aturan sama.
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('import_unavailable', false)->whereNull('archived_at')->where('condition', 'baik')
            ->whereHas('book', fn ($books) => $books->whereNull('archived_at'))
            ->whereDoesntHave('reservations', fn ($holds) => $holds->where('status', 'ready')->where('expires_at', '>', now()))
            ->whereDoesntHave('loanItems', fn ($items) => $items->whereNull('returned_at'));
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(BookReservation::class);
    }

    // Eksemplar ini merupakan bagian dari satu judul buku.
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function loanItems(): HasMany
    {
        return $this->hasMany(LoanItem::class);
    }
}
