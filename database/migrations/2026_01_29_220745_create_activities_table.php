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
        Schema::create('activities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->ulidMorphs('targetable'); // targetable_type, targetable_id
            $table->string('trigger'); // audition_response_due, job_session_upcoming, etc.
            $table->string('action')->nullable(); // snoozed, archived, or null if no action taken yet
            $table->timestamp('snoozed_until')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index for efficient querying
            $table->index(['user_id', 'action']);
            $table->index(['user_id', 'snoozed_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
