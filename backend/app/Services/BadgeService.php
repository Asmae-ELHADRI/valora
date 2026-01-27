<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Prestataire;

class BadgeService
{
    /**
     * Synchronize badges for a prestataire based on their current pro_score.
     */
    public function syncBadges(Prestataire $prestataire)
    {
        $score = $prestataire->pro_score;
        $eligibleBadges = Badge::where('threshold', '<=', $score)->get();
        
        $prestataire->badges()->sync($eligibleBadges->pluck('id'));
        
        return $eligibleBadges;
    }
}
