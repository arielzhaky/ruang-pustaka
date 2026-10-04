<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BookReservation extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['ready_at' => 'datetime', 'expires_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function bookCopy()
    {
        return $this->belongsTo(BookCopy::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->where('status', 'waiting')->orWhere(fn ($q) => $q->where('status', 'ready')->where('expires_at', '>', now())));
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === 'ready' && $this->expires_at->lte(now())) {
            return 'Kedaluwarsa';
        }

        return ['waiting' => 'Mengantre', 'ready' => 'Siap diambil', 'fulfilled' => 'Sudah dipinjam', 'cancelled' => 'Dibatalkan', 'expired' => 'Kedaluwarsa'][$this->status] ?? $this->status;
    }
}
