<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'requirements',
        'estimated_duration',
        'material_required',
        'budget',
        'location',
        'desired_date',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
