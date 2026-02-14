<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use App\Models\Complaint;
use Illuminate\Support\Facades\Hash;

class ReportedUserSeeder extends Seeder
{
    public function run()
    {
        // 1. Create User 'Sara'
        $sara = User::firstOrCreate(
            ['email' => 'sara@test.valora.com'],
            [
                'name' => 'Sara',
                'password' => Hash::make('password123'),
                'role' => 'client',
                'is_active' => true,
                'phone' => '0600000000',
                'address' => 'Casablanca, Maroc'
            ]
        );

        // 2. Create Client Profile
        // Client table might be empty or just linking user_id
        if (!Client::where('user_id', $sara->id)->exists()) {
             Client::create(['user_id' => $sara->id]);
        }

        // 3. Create a User to report her (if not exists)
        $reporter = User::firstOrCreate(
            ['email' => 'reporter@test.valora.com'],
            [
                'name' => 'Jean (Reporter)',
                'password' => Hash::make('password123'),
                'role' => 'prestataire'
            ]
        );

        // 4. Create Complaint (Signalement)
        Complaint::create([
            'reporter_id' => $reporter->id,
            'reported_id' => $sara->id,
            'reason' => 'Comportement inapproprié',
            'description' => 'La cliente a été impolie lors de la dernière intervention.',
            'status' => 'pending', // En attente de modération
            'priority' => 'high',
            'subject_type' => 'user', // Polymorphic subject if needed, but reporter/reported are fields
            'subject_id' => $sara->id
        ]);
    }
}
