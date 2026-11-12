<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('balance', 12, 2)->default(0);
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');                      // topup | purchase | refund
            $table->decimal('amount', 12, 2);            // + пополнение, − списание
            $table->decimal('balance_after', 12, 2);
            $table->string('method')->nullable();        // card | kaspi | apple_pay | google_pay
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('balance');
        });
    }
};