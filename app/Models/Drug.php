<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Drug extends Model
{
    use HasFactory;

    protected $fillable = [
        'tag_id',
        'quantity',
        'price',
        'dose',
        'img_url',
        'expiry_date',
    ];

    protected $hidden = ['created_at', 'updated_at', 'pivot'];

    public function merge($drugDetails)
    {
        $this->trade_name = $drugDetails->trade_name;
        $this->scientific_name = $drugDetails->scientific_name;
        $this->company = $drugDetails->company;
        $this->dose_unit = $drugDetails->dose_unit;
    }
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
    public function drug_details()
    {
        return $this->hasMany(DrugDetail::class);
    }
    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }
    public function sale()
    {
        return $this->hasOne(Sales::class);
    }
    public function favorites()
    {
        return $this->belongsToMany(User::class, 'favorites');
    }
}
