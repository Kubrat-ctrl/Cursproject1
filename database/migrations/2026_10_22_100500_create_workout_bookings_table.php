<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Промежуточная таблица связи «многие ко многим»
     * между клиентами (users) и занятиями (schedule_items).
     */
    public function up(): void
    {
        Schema::create('workout_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('booked');
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'schedule_item_id']);
            $table->index(['schedule_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_bookings');
    }
};
