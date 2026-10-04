<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinePayment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'paid_at' => 'datetime'];
    }

    public function loanItem()
    {
        return $this->belongsTo(LoanItem::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
