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
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
        });

        Schema::table('prestataires', function (Blueprint $table) {
            $table->json('availabilities')->nullable()->after('diplomas');
            $table->boolean('is_visible')->default(true)->after('is_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'phone', 'address']);
        });

        Schema::table('prestataires', function (Blueprint $table) {
            $table->dropColumn(['availabilities', 'is_visible']);
        });
    }
};
