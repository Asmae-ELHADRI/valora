<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_offer_id',
        'user_id',
        'created_by_id',
        'message',
        'status',
        'is_read',
    ];

    public function serviceOffer()
    {
        return $this->belongsTo(ServiceOffer::class);
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
