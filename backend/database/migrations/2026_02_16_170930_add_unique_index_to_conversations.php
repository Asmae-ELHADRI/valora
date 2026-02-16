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
        Schema::table('conversations', function (Blueprint $table) {
            // Add unique index to prevent duplicate conversations
            // This ensures that for any pair of users with the same context,
            // only one conversation exists
            $table->unique(['sender_id', 'receiver_id', 'related_type', 'related_id'], 'unique_conversation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique('unique_conversation');
        });
    }
};
