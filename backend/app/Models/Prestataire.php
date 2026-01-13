<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Prestataire extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'skills',
        'description',
        'experience',
        'diplomas',
        'availabilities',
        'is_available',
        'is_visible',
        'photo',
        'rating',
    ];

    protected $casts = [
        'availabilities' => 'array',
        'is_available' => 'boolean',
        'is_visible' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class);
    }
}
