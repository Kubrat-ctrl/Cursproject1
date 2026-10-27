<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_type_id')->constrained()->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->decimal('price_paid', 10, 2);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['user_id', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
