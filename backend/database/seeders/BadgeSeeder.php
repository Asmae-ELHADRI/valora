<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $badges = [
            [
                'name' => 'Certifié Valora',
                'slug' => 'certifie',
                'threshold' => 100,
                'description' => 'Prestataire officiel certifié par la plateforme après 10 missions réussies.',
                'icon' => 'check-circle'
            ],
            [
                'name' => 'Confirmé',
                'slug' => 'confirme',
                'threshold' => 250,
                'description' => 'Prestataire ayant fait ses preuves avec une qualité constante sur plus de 25 missions.',
                'icon' => 'award'
            ],
            [
                'name' => 'Expert',
                'slug' => 'expert',
                'threshold' => 500,
                'description' => 'Elite de la plateforme, reconnu pour son excellence sur plus de 50 missions.',
                'icon' => 'shield-check'
            ],
        ];

        foreach ($badges as $badge) {
            \App\Models\Badge::updateOrCreate(['slug' => $badge['slug']], $badge);
        }
    }
}
