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
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->default('text-gray-500'); 
            $table->string('bg_color')->default('bg-gray-500/20');
            $table->string('border_color')->default('border-gray-500/30');
            $table->string('icon')->default('shield');
            
            // Thresholds
            $table->integer('missions_threshold')->default(0);
            $table->decimal('rating_threshold', 3, 2)->default(0.00);
            $table->integer('seniority_threshold_months')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
