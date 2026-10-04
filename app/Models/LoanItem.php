<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanItem extends Model
{
    protected $fillable = [
        'loan_id',
        'book_copy_id',
        'due_date',
        'returned_at',
        'returned_by',
        'return_condition',
        'return_notes',
        'fine_daily_rate',
        'fine_amount',
        'replacement_amount',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'returned_at' => 'datetime',
            'fine_daily_rate' => 'integer',
            'fine_amount' => 'integer',
            'replacement_amount' => 'integer',
        ];
    }

    public function payment()
    {
        return $this->hasOne(FinePayment::class);
    }

    public function renewal()
    {
        return $this->hasOne(LoanRenewal::class);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function bookCopy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class);
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }
}
