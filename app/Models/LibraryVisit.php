<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class LibraryVisit extends Model
{
    protected $fillable = ['member_id', 'created_by', 'student_name', 'member_code', 'class_name', 'visit_date', 'visited_at'];

    protected function casts(): array
    {
        return ['visit_date' => 'date', 'visited_at' => 'datetime'];
    }

    protected function visitDate(): Attribute
    {
        // Persist a calendar date in both MySQL DATE and SQLite text storage.
        return Attribute::make(set: fn ($value) => Carbon::parse($value)->toDateString());
    }
}
