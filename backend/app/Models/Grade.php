<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = [
        'name', 'slug', 'color', 'bg_color', 'border_color', 'icon',
        'missions_threshold', 'rating_threshold', 'seniority_threshold_months'
    ];

    public function prestataires()
    {
        return $this->hasMany(Prestataire::class);
    }
}
