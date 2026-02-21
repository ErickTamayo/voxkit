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
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('script_text');
        });

        Schema::table('auditions', function (Blueprint $table) {
            $table->dropColumn('script_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->text('script_text')->nullable()->after('category');
        });

        Schema::table('auditions', function (Blueprint $table) {
            $table->text('script_text')->nullable()->after('category');
        });
    }
};
