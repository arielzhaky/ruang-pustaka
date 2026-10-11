<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $hidden = ['login_qr_token', 'login_qr_hash'];

    protected $fillable = [
        'user_id',
        'member_code',
        'nis',
        'school_class_id',
        'phone',
        'gender',
        'age',
        'senayan_data',
    ];

    protected function casts(): array
    {
        return ['senayan_data' => 'array', 'age' => 'integer', 'login_qr_token' => 'encrypted'];
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
