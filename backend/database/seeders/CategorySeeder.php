<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Construction & Rénovation
            'Plomberie',
            'Électricité',
            'Maçonnerie',
            'Peinture & Décoration',
            'Menuiserie',
            'Carrelage',
            'Chauffage & Climatisation',
            'Isolation',
            'Toiture & Couverture',
            'Vitrerie',
            
            // Services à domicile
            'Ménage & Nettoyage',
            'Jardinage & Paysagisme',
            'Garde d\'enfants',
            'Aide à domicile',
            'Repassage',
            'Cuisine à domicile',
            
            // Services professionnels
            'Informatique & Dépannage',
            'Cours particuliers',
            'Coaching & Formation',
            'Traduction',
            'Rédaction & Correction',
            'Comptabilité',
            'Conseil juridique',
            'Marketing & Communication',
            'Design graphique',
            'Développement web',
            
            // Transport & Logistique
            'Déménagement',
            'Transport de marchandises',
            'Livraison',
            'Coursier',
            
            // Événementiel
            'Traiteur',
            'Photographie',
            'Vidéographie',
            'Animation',
            'DJ & Musicien',
            'Décoration événementielle',
            
            // Bien-être & Santé
            'Coiffure à domicile',
            'Esthétique & Beauté',
            'Massage',
            'Fitness & Sport',
            'Diététique',
            
            // Automobile
            'Mécanique auto',
            'Carrosserie',
            'Dépannage auto',
            'Nettoyage auto',
            
            // Animaux
            'Toilettage',
            'Garde d\'animaux',
            'Promenade de chiens',
            'Éducation canine',
        ];

        foreach ($categories as $category) {
            ServiceCategory::updateOrCreate(
                ['slug' => Str::slug($category)],
                [
                    'name' => $category,
                    'icon' => 'default-icon',
                ]
            );
        }
    }
}
