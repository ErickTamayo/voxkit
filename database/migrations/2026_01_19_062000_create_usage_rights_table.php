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
        Schema::create('usage_rights', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('usable_type');
            $table->ulid('usable_id');
            $table->index(['usable_type', 'usable_id']);

            $table->string('type');
            $table->json('media_types');
            $table->string('geographic_scope');
            $table->string('duration_type');
            $table->integer('duration_months')->nullable();
            $table->date('start_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->boolean('exclusivity')->default(false);
            $table->string('exclusivity_category')->nullable();
            $table->boolean('ai_rights_granted')->default(false);
            $table->boolean('renewal_reminder_sent')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_rights');
    }
};
