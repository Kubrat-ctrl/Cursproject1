<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->restrictOnDelete();
            $table->foreignId('trainer_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->string('room', 50);
            $table->unsignedSmallInteger('capacity');
            $table->string('status', 20)->default('scheduled');
            $table->timestamps();

            $table->index(['trainer_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_items');
    }
};
