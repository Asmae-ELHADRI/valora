<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'icon', 'description'];

    public function scopeAlphabetical($query)
    {
        return $query->orderBy('name', 'asc');
    }

    public function serviceOffers()
    {
        return $this->hasMany(ServiceOffer::class, 'category_id');
    }
}
