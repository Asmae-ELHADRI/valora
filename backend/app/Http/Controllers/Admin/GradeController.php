<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Grade;
use App\Models\User;
use App\Models\Prestataire;

class GradeController extends Controller
{
    public function index()
    {
        return response()->json(Grade::orderBy('missions_threshold', 'asc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:grades,slug',
            'missions_threshold' => 'required|integer',
            'rating_threshold' => 'required|numeric',
            'seniority_threshold_months' => 'required|integer',
            'color' => 'nullable|string',
            'bg_color' => 'nullable|string',
            'border_color' => 'nullable|string',
        ]);

        $grade = Grade::create($validated);
        return response()->json($grade, 201);
    }

    public function update(Request $request, $id)
    {
        $grade = Grade::findOrFail($id);
        $grade->update($request->all());
        return response()->json($grade);
    }

    public function destroy($id)
    {
        Grade::destroy($id);
        return response()->json(['message' => 'Grade deleted']);
    }

    // --- Attribution Logic ---

    /**
     * Get recent grade attributions from history.
     */
    public function attributions()
    {
        // Auto-sync before fetching to ensure data is fresh
        $this->syncGrades();

        $attributions = \App\Models\GradeAttribution::with(['prestataire.user', 'grade', 'assigner'])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->map(function($attr) {
                return [
                    'id' => $attr->id,
                    'user' => $attr->prestataire->user->name ?? 'Utilisateur Inconnu',
                    'user_id' => $attr->prestataire->user->id ?? null,
                    'prestataire_id' => $attr->prestataire_id,
                    'user_photo' => $attr->prestataire->user->photo,
                    'grade' => $attr->grade->name,
                    'grade_id' => $attr->grade_id,
                    'grade_color' => $attr->grade->color,
                    'date' => $attr->created_at->diffForHumans(),
                    'type' => $attr->type === 'automatic' ? 'Automatique' : 'Manuel',
                    'status' => 'valid'
                ];
            });

        return response()->json($attributions);
    }

    /**
     * Manually assign a grade to a user (prestataire).
     */
    public function assign(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'grade_id' => 'required|exists:grades,id'
        ]);

        $user = User::findOrFail($validated['user_id']);
        $prestataire = $user->prestataire;

        if (!$prestataire) {
            return response()->json(['message' => 'User is not a prestataire'], 400);
        }

        // Update Grade
        $prestataire->grade_id = $validated['grade_id'];
        $prestataire->save();

        // Log Attribution
        \App\Models\GradeAttribution::create([
            'prestataire_id' => $prestataire->id,
            'grade_id' => $validated['grade_id'],
            'sys_user_id' => auth()->id(), // Admin
            'type' => 'manual'
        ]);

        return response()->json(['message' => 'Grade assigned successfully']);
    }

    /**
     * Sync all grades based on missions count.
     * Logic: Bronze (5+), Silver (15+), Gold (25+)
     */
    public function syncGrades()
    {
        $grades = Grade::orderBy('missions_threshold', 'desc')->get(); // Highest first (Gold -> Silver -> Bronze)
        $prestataires = Prestataire::with('grade')->get();

        foreach ($prestataires as $prestataire) {
            // Find fitting grade
            $eligibleGrade = null;
            foreach ($grades as $grade) {
                if ($prestataire->missions_count >= $grade->missions_threshold) {
                    $eligibleGrade = $grade;
                    break; 
                }
            }

            // If eligible and different from current, update
            if ($eligibleGrade && $prestataire->grade_id !== $eligibleGrade->id) {
                $prestataire->grade_id = $eligibleGrade->id;
                $prestataire->save();

                // Log auto attribution
                \App\Models\GradeAttribution::create([
                    'prestataire_id' => $prestataire->id,
                    'grade_id' => $eligibleGrade->id,
                    'type' => 'automatic'
                ]);
            }
        }
        
        return response()->json(['message' => 'Grades synced']);
    }

    /**
     * Revoke a grade attribution.
     */
    public function revoke(Request $request)
    {
        $validated = $request->validate([
            'attribution_id' => 'required|exists:grade_attributions,id'
        ]);

        $attribution = \App\Models\GradeAttribution::findOrFail($validated['attribution_id']);
        
        // Reset user grade to null or previous? 
        // For simplicity, set to null
        $prestataire = $attribution->prestataire;
        $prestataire->grade_id = null;
        $prestataire->save();

        $attribution->delete(); // Or soft delete? 
        // Hard delete for now as "Révoquer" usually means "Cancel"

        return response()->json(['message' => 'Attribution revoked']);
    }
}
