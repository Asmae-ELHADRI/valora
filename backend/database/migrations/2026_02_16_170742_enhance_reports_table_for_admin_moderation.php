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
        Schema::table('reports', function (Blueprint $table) {
            // Admin moderation fields
            $table->enum('admin_action', ['pending', 'ignored', 'warned', 'suspended', 'deleted'])->default('pending')->after('status');
            $table->timestamp('admin_action_at')->nullable()->after('admin_action');
            $table->text('admin_notes')->nullable()->after('admin_action_at');
            
            // Add indexes for performance
            $table->index(['status', 'created_at']);
            $table->index('admin_action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['admin_action']);
            $table->dropColumn(['admin_action', 'admin_action_at', 'admin_notes']);
        });
    }
};
