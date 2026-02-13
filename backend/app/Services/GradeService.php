<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\Prestataire;

class GradeService
{
    /**
     * Check and assign the highest eligible grade to a provider.
     */
    public function checkAndAssignGrade(Prestataire $prestataire)
    {
        // Calculate seniority in months
        $seniorityMonths = $prestataire->created_at->diffInMonths(now());
        
        // Fetch all grades ordered by thresholds (descending to find highest match first)
        $grades = Grade::orderBy('missions_threshold', 'desc')
                       ->orderBy('rating_threshold', 'desc')
                       ->get();

        foreach ($grades as $grade) {
            if (
                $prestataire->missions_count >= $grade->missions_threshold &&
                $prestataire->pro_score >= $grade->rating_threshold &&
                $seniorityMonths >= $grade->seniority_threshold_months
            ) {
                // If current grade is different, update it
                if ($prestataire->grade_id !== $grade->id) {
                    $prestataire->update(['grade_id' => $grade->id]);
                    // You could trigger a notification here
                    return $grade;
                }
                return null; // Already at correct grade
            }
        }

        return null;
    }

    /**
     * Force assign a grade manually (Admin action).
     */
    public function forceAssignGrade(Prestataire $prestataire, int $gradeId)
    {
        $prestataire->update(['grade_id' => $gradeId]);
        return Grade::find($gradeId);
    }
}
