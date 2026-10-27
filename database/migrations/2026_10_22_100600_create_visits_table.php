<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_booking_id')->constrained()->cascadeOnDelete();
            $table->dateTime('checked_in_at');
            $table->timestamps();

            // Связь 1:1: на одну запись приходится не больше одной отметки.
            $table->unique('workout_booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
