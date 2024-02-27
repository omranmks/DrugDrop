<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'user_id',
        'title',
        'report'
    ];
    // protected $casts = [
    //     'url' => 'hashed',
    // ];
    public function getCreatedAtAttribute($date)
    {
        $time = new DateTime($date);
        return  $time->format('Y-m-d');
    }
}
