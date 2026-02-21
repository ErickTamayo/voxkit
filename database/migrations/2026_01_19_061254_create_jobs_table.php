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
        Schema::create('jobs', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignUlid('audition_id')
                ->nullable()
                ->constrained('auditions')
                ->nullOnDelete();

            $table->foreignUlid('client_id')
                ->constrained('contacts')
                ->onDelete('cascade');

            $table->foreignUlid('agent_id')
                ->nullable()
                ->constrained('contacts')
                ->nullOnDelete();

            $table->string('project_title');
            $table->string('brand_name')->nullable();
            $table->string('character_name')->nullable();
            $table->string('category');
            $table->text('script_text')->nullable();
            $table->integer('word_count')->nullable();
            $table->money('contracted_rate');
            $table->string('rate_type');
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->nullable();
            $table->string('session_type');
            $table->dateTime('session_date')->nullable();
            $table->dateTime('delivery_deadline')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->string('status');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
