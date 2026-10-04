<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Book extends Model
{
    protected $fillable = [
        'category_id',
        'isbn',
        'title',
        'author',
        'publisher',
        'publication_year',
        'description',
        'cover_path',
        'replacement_price',
        'archived_at',
        'source_key',
        'source_url',
    ];

    protected function casts(): array
    {
        return [
            'publication_year' => 'integer',
            'replacement_price' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    // Satu judul buku termasuk dalam satu kategori.
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Satu judul buku dapat memiliki banyak eksemplar.
    public function copies(): HasMany
    {
        return $this->hasMany(BookCopy::class);
    }

    public function loanItems(): HasManyThrough
    {
        return $this->hasManyThrough(LoanItem::class, BookCopy::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'book_user')->withTimestamps();
    }
}
