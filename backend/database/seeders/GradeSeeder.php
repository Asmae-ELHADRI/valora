<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Grade;
use App\Models\Prestataire;

class GradeSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Standard Grades
        $grades = [
            [
                'name' => 'Or',
                'slug' => 'or',
                'missions_threshold' => 25,
                'rating_threshold' => 4.8,
                'seniority_threshold_months' => 12,
                'color' => 'text-yellow-400',
                'bg_color' => 'bg-yellow-400/10'
            ],
            [
                'name' => 'Argent',
                'slug' => 'argent',
                'missions_threshold' => 15,
                'rating_threshold' => 4.5,
                'seniority_threshold_months' => 6,
                'color' => 'text-slate-400',
                'bg_color' => 'bg-slate-400/10'
            ],
            [
                'name' => 'Bronze',
                'slug' => 'bronze',
                'missions_threshold' => 5,
                'rating_threshold' => 4.0,
                'seniority_threshold_months' => 1,
                'color' => 'text-amber-600',
                'bg_color' => 'bg-amber-600/10'
            ]
        ];

        foreach ($grades as $g) {
            Grade::updateOrCreate(['slug' => $g['slug']], $g);
        }

        // 2. Create 'Rania' User if not exists
        $user = User::firstOrCreate(
            ['email' => 'rania@test.valora.com'],
            [
                'name' => 'Rania',
                'password' => bcrypt('password123'),
                'role' => 'prestataire'
            ]
        );

        // 3. Create Prestataire Profile for Rania
        $prestataire = Prestataire::updateOrCreate(
            ['user_id' => $user->id],
            [
                 // Ensure missions_count makes her eligible for Argent (15+) but not Or (25+) initially
                 // Let's set it to 20 so she gets Argent automatically
                'missions_count' => 20,
                'description' => 'Prestataire modèle',
                'experience' => '2 ans'
            ]
        );
        
        // Note: The controller syncGrades() will assign her 'Argent' automatically when visited.
    }
}
