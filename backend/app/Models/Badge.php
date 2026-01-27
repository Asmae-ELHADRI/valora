<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    protected $fillable = ['name', 'slug', 'threshold', 'icon', 'description'];

    /**
     * Get the providers associated with this badge.
     */
    public function prestataires()
    {
        return $this->belongsToMany(Prestataire::class);
    }
}
