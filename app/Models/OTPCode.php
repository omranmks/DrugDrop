<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OTPCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pin_code',
        'type',
    ];
    
    protected $casts = [
        'pin_code' => 'hashed',
    ];
}
