<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->string('group')->default('general');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Seed default badge settings
        DB::table('system_settings')->insert([
            [
                'key' => 'badge_threshold_confirmed',
                'value' => '100',
                'type' => 'integer',
                'group' => 'badges',
                'description' => 'Points nécessaires pour le badge Confirmé',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'badge_threshold_expert',
                'value' => '300',
                'type' => 'integer',
                'group' => 'badges',
                'description' => 'Points nécessaires pour le badge Expert',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'score_weight_mission',
                'value' => '10',
                'type' => 'integer',
                'group' => 'badges',
                'description' => 'Points par mission terminée',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'score_weight_rating',
                'value' => '20',
                'type' => 'integer',
                'group' => 'badges',
                'description' => 'Points par étoile de notation (moyenne)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
