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
                'name' => 'Confirmé',
                'slug' => 'confirme',
                'threshold' => 100,
                'description' => 'Prestataire ayant fait ses preuves avec une qualité constante.',
                'icon' => 'award'
            ],
            [
                'name' => 'Expert',
                'slug' => 'expert',
                'threshold' => 300,
                'description' => 'Elite de la plateforme, reconnu pour son excellence et sa fiabilité.',
                'icon' => 'shield-check'
            ],
        ];

        foreach ($badges as $badge) {
            \App\Models\Badge::updateOrCreate(['slug' => $badge['slug']], $badge);
        }
    }
}
