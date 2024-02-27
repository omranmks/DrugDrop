<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sales extends Model
{
    use HasFactory;

    protected $fillable = [
        'drug_id',
        'sale_date',
        'quantity',
    ];

    public $timestamps = false;

    public function drug(){
        return $this->belongsTo(Drug::class);
    }
    public function getSaleDateAttribute($date)
    {
        $time = new DateTime($date);
        return  $time->format('Y-m-d');
    }
}
