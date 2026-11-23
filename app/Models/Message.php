<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['booking_id', 'sender_id', 'content', 'read_at'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /** Pesan yang belum dibaca oleh $user (dikirim oleh pihak lain). */
    public function scopeUnreadFor(Builder $query, User $user): Builder
    {
        return $query->where('sender_id', '!=', $user->id)
            ->whereNull('read_at');
    }
}