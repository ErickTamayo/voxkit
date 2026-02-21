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
        Schema::create('auditions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->onDelete('cascade');

            $table->string('sourceable_type');
            $table->ulid('sourceable_id');
            $table->index(['sourceable_type', 'sourceable_id']);

            $table->string('source_reference')->nullable();
            $table->string('project_title');
            $table->string('brand_name')->nullable();
            $table->string('character_name')->nullable();
            $table->string('category');
            $table->text('script_text')->nullable();
            $table->integer('word_count')->nullable();
            $table->bigInteger('budget_min')->nullable();
            $table->bigInteger('budget_max')->nullable();
            $table->bigInteger('quoted_rate')->nullable();
            $table->string('rate_type');
            $table->dateTime('response_deadline')->nullable();
            $table->dateTime('project_deadline')->nullable();
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
        Schema::dropIfExists('auditions');
    }
};
