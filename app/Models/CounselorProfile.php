<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CounselorProfile extends Model
{
    protected $fillable = ['user_id', 'graduate_field', 'education', 'specializations', 'bio', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class);
    }

    public function specializationList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $this->specializations))));
    }

    public function scopeApproved($q)
    {
        return $q->where('status', 'approved')
            ->whereHas('user', fn ($u) => $u->where('is_active', true));
    }
}
