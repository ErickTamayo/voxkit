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
        Schema::table('auditions', function (Blueprint $table) {
            $table->unsignedBigInteger('current_version')->nullable();
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('current_version')->nullable();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('current_version')->nullable();
        });

        Schema::table('usage_rights', function (Blueprint $table) {
            $table->unsignedBigInteger('current_version')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auditions', function (Blueprint $table) {
            $table->dropColumn('current_version');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('current_version');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('current_version');
        });

        Schema::table('usage_rights', function (Blueprint $table) {
            $table->dropColumn('current_version');
        });
    }
};
