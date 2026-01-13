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
            'Plomberie',
            'Électricité',
            'Ménage',
            'Jardinage',
            'Cours particuliers',
            'Informatique',
            'Transport',
            'Déménagement',
        ];

        foreach ($categories as $category) {
            ServiceCategory::create([
                'name' => $category,
                'slug' => Str::slug($category),
                'icon' => 'default-icon',
            ]);
        }
    }
}
