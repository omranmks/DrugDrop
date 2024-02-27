<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'name',
        'phone_number',
        'location',
        'status',
        'password',
    ];
    protected $hidden = [
        'id',
        'password',
        'status',
        'updated_at',
        'created_at',
        'otpcode',
        'drugs',
    ];
    protected $casts = [
        'password' => 'hashed',
    ];

    public function otpcode()
    {
        return $this->hasOne(OtpCode::class);
    }

    public function operation()
    {
        return $this->belongsTo(Operation::class);
    }
    public function favorites()
    {
        return $this->belongsToMany(Drug::class, 'favorites');
    }
}
