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
        Schema::table('prestataires', function (Blueprint $table) {
            if (!Schema::hasColumn('prestataires', 'city')) {
                $table->string('city')->nullable();
            }
            if (!Schema::hasColumn('prestataires', 'hourly_rate')) {
                $table->decimal('hourly_rate', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('prestataires', 'category_id')) {
                $table->foreignId('category_id')->nullable()->constrained('service_categories')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestataires', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['city', 'hourly_rate', 'category_id']);
        });
    }
};
