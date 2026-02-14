<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('grade_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestataire_id')->constrained()->onDelete('cascade');
            $table->foreignId('grade_id')->constrained()->onDelete('cascade');
            $table->foreignId('sys_user_id')->nullable()->constrained('users')->nullOnDelete(); // Admin who assigned it
            $table->string('type')->default('automatic'); // automatic, manual
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('grade_attributions');
    }
};
