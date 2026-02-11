<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\SystemSetting;

class Prestataire extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cin',
        'birth_date',
        'city',
        'hourly_rate',
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
        'certified_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'availabilities' => 'array',
        'is_available' => 'boolean',
        'is_visible' => 'boolean',
    ];

    protected $appends = ['photo_url', 'badge_level', 'completed_missions_count', 'pro_score', 'current_badges', 'is_certified'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function categories()
    {
        return $this->belongsToMany(ServiceCategory::class, 'category_prestataire', 'prestataire_id', 'service_category_id');
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'prestataire_badge');
    }

    public function getCurrentBadgesAttribute()
    {
        return $this->badges->pluck('name');
    }

    public function getCompletedMissionsCountAttribute()
    {
        return ServiceRequest::where('user_id', $this->user_id)
            ->where('status', 'completed')
            ->count();
    }

    public function getProScoreAttribute()
    {
        $missionWeight = SystemSetting::get('score_weight_mission', 10);
        $ratingWeight = SystemSetting::get('score_weight_rating', 20);
        
        $missionsScore = $this->completed_missions_count * $missionWeight;
        $qualityScore = ($this->rating ?: 0) * $ratingWeight;
        
        return $missionsScore + $qualityScore;
    }

    public function getBadgeLevelAttribute()
    {
        $highestBadge = $this->badges()->orderByDesc('threshold')->first();
        return $highestBadge ? $highestBadge->name : 'Débutant';
    }

    public function getIsCertifiedAttribute()
    {
        return $this->certified_at !== null || $this->completed_missions_count >= 10;
    }

    public function getPhotoUrlAttribute()
    {
        if ($this->photo) {
            if (str_starts_with($this->photo, 'http')) {
                return $this->photo;
            }
            return url('storage/' . $this->photo);
        }
        return null;
    }
}
