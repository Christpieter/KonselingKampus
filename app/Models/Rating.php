<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    protected $fillable = ['booking_id', 'student_id', 'counselor_id', 'stars', 'comment'];

    public function student(): BelongsTo { return $this->belongsTo(User::class, 'student_id'); }
}
