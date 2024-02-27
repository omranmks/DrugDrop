<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'event_name',
        'message',
    ];
    protected $hidden = [
        'user_id', 'id', 'updated_at'
    ];
    public function getCreatedAtAttribute($date)
    {
        $time = new DateTime($date);
        return  $time->format('Y-m-d');
    }
}
