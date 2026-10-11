<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintQueueItem extends Model
{
    protected $fillable = ['user_id', 'member_id', 'book_copy_id'];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function bookCopy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class);
    }
}
