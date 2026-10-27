<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->decimal('price', 10, 2);
            $table->unsignedSmallInteger('duration_days');
            $table->unsignedSmallInteger('visits_limit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_types');
    }
};
