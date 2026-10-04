<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $fillable = [
        'user_id',
        'member_code',
        'nis',
        'school_class_id',
        'phone',
        'gender',
        'age',
    ];

    protected function casts(): array
    {
        return ['age' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
