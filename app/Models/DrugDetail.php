<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrugDetail extends Model
{
    use HasFactory;
    public function scopeFilter($query, $filterSearch = null)
    {
        $query->when(
            $filterSearch ?? false,
            fn ($query, $name) =>
            $query->where(fn ($query) =>
            $query->where('scientific_name', 'like', '%' . $name . '%')
                ->orWhere('trade_name', 'like', '%' . $name . '%')
                ->orWhere('company', 'like', '%' . $name . '%'))
        );
    }
    protected $fillable = [
        'drug_id',
        'trade_name',
        'scientific_name',
        'company',
        'dose_unit',
        'description',
        'lang_code',
    ];

    protected $hidden = [
        'id',
        'drug_id',
        'lang_code',
        'description',
        'created_at',
        'updated_at',
    ];
    public function scopeByLang($query, $langCode)
    {
        return $query->where('lang_code', $langCode);
    }
    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }
}
