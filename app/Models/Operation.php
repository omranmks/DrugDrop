<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operation extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'total_price', 'invoice', 'is_paid'];
    protected $hidden   = ['invoice', 'user_id', 'updated_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function getCreatedAtAttribute($date)
    {
        $time = new DateTime($date);
        return  $time->format('Y-m-d');
    }
}
