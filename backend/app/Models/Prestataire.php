<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\SystemSetting;

use App\Models\ServiceRequest;

class Prestataire extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'grade_id', // Added
        'missions_count', // Added
        'photo', 'cv_url', 'cin', 'birth_date', 'certified_at', 'details', 'city',
        'hourly_rate',
        'category_id',
        'skills',
        'description',
        'achievements', // Added
        'experience',
        'diplomas',
        'availabilities',
        'is_available',
        'is_visible',
        'is_completed',
        'photo',
        'rating',
        'certified_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'availabilities' => 'array',
        'achievements' => 'array', // Added
        'is_available' => 'boolean',
        'is_visible' => 'boolean',
        'is_completed' => 'boolean',
    ];

    protected $appends = ['photo_url', 'current_grade', 'completed_missions_count', 'pro_score', 'is_certified'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class);
    }
    
    // Valid New Relationship
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function categories()
    {
        return $this->belongsToMany(ServiceCategory::class, 'category_prestataire', 'prestataire_id', 'service_category_id');
    }

    public function getCompletedMissionsCountAttribute()
    {
        return ServiceRequest::where('user_id', $this->user_id) 
            ->where('status', 'completed')
            ->count();
    }
    
    public function getCurrentGradeAttribute()
    {
        return $this->grade ? $this->grade->name : 'Aucun';
    }

    public function getProScoreAttribute()
    {
        $missionWeight = SystemSetting::get('score_weight_mission', 10);
        $ratingWeight = SystemSetting::get('score_weight_rating', 20);
        
        $missionsScore = $this->completed_missions_count * $missionWeight;
        $qualityScore = ($this->rating ?: 0) * $ratingWeight;
        
        return $missionsScore + $qualityScore;
    }

    public function getIsCertifiedAttribute()
    {
        return $this->certified_at !== null;
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
