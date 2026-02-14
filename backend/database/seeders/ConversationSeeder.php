<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\Hash;

class ConversationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure Rania exists (Provider)
        $rania = User::firstOrCreate(
            ['email' => 'rania@test.valora.com'],
            [
                'name' => 'Rania',
                'password' => Hash::make('password123'),
                'role' => 'prestataire',
                'email_verified_at' => now(),
            ]
        );

        // Ensure Sara exists (Client)
        $sara = User::firstOrCreate(
            ['email' => 'sara@test.valora.com'],
            [
                'name' => 'Sara',
                'password' => Hash::make('password123'),
                'role' => 'client',
                'email_verified_at' => now(),
            ]
        );

        // Create a conversation (Message from Rania to Sara)
        Message::create([
            'sender_id' => $rania->id,
            'receiver_id' => $sara->id,
            'content' => 'Bonjour Sara, je suis disponible pour votre mission de demain.',
            'created_at' => now()->subMinutes(10),
            'read_at' => null,
        ]);

        // Create a reply from Sara to Rania
        Message::create([
            'sender_id' => $sara->id,
            'receiver_id' => $rania->id,
            'content' => 'Merci Rania, c\'est parfait. À demain !',
            'created_at' => now()->subMinutes(5),
            'read_at' => null,
        ]);

        $this->command->info('Conversation created between Rania ('.$rania->email.') and Sara ('.$sara->email.')');
    }
}
