<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    protected $fillable = ['counselor_profile_id', 'name', 'issuer', 'year'];
}
