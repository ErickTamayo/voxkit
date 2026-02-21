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
        Schema::create('invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignUlid('job_id')
                ->nullable()
                ->constrained('jobs')
                ->nullOnDelete();

            $table->foreignUlid('client_id')
                ->constrained('contacts')
                ->onDelete('cascade');

            $table->string('invoice_number');
            $table->date('issued_at');
            $table->date('due_at');
            $table->money('subtotal');
            $table->decimal('tax_rate', 6, 4)->nullable();
            $table->moneyNullable('tax_amount');
            $table->money('total');
            $table->string('status');
            $table->date('paid_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
