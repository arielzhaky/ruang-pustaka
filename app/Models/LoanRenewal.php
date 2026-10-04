<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRenewal extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['original_due_date' => 'date', 'requested_due_date' => 'date', 'reviewed_at' => 'datetime'];
    }

    public function loanItem()
    {
        return $this->belongsTo(LoanItem::class);
    }
}
