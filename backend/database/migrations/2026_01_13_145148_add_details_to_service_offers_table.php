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
        Schema::table('service_offers', function (Blueprint $table) {
            $table->text('requirements')->nullable()->after('description');
            $table->string('estimated_duration')->nullable()->after('requirements');
            $table->text('material_required')->nullable()->after('estimated_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_offers', function (Blueprint $table) {
            $table->dropColumn(['requirements', 'estimated_duration', 'material_required']);
        });
    }
};
