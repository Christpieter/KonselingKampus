<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    protected $fillable = ['counselor_id', 'day_of_week', 'start_time', 'end_time'];

    public const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public function dayName(): string
    {
        return self::DAYS[$this->day_of_week] ?? '-';
    }
}
