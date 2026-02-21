<?php

declare(strict_types=1);

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
        Schema::table('exchange_rates', function (Blueprint $table) {
            // Add composite index for efficient currency + date queries
            // Optimizes: WHERE currency_code = X AND effective_date <= Y ORDER BY effective_date DESC
            $table->index(['currency_code', 'effective_date'], 'idx_currency_effective_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropIndex('idx_currency_effective_date');
        });
    }
};
