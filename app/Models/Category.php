<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    public function scopeFilter($query, $filterCategory = null)
    {
        $query->when(
            $filterCategory ?? false,
            fn ($query, $name) =>
            $query->where(
                fn ($query) =>
                $query->where(
                    fn ($query) => $query
                        ->where('en_name', 'like', '%' . $name . '%')
                        ->orWhere('ar_name', 'like', '%' . $name . '%')
                )
            )
        );
    }
    protected $hidden = ['updated_at', 'created_at', 'pivot'];

    public function drugs()
    {
        return $this->belongsToMany(Drug::class);
    }
}
