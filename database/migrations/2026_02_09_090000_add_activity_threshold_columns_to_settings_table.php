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
        Schema::table('settings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('activity_audition_response_due_hours')
                ->default(48)
                ->after('language');
            $table->unsignedSmallInteger('activity_job_session_upcoming_hours')
                ->default(24)
                ->after('activity_audition_response_due_hours');
            $table->unsignedSmallInteger('activity_job_delivery_due_hours')
                ->default(24)
                ->after('activity_job_session_upcoming_hours');
            $table->unsignedSmallInteger('activity_invoice_due_soon_days')
                ->default(7)
                ->after('activity_job_delivery_due_hours');
            $table->unsignedSmallInteger('activity_usage_rights_expiring_days')
                ->default(30)
                ->after('activity_invoice_due_soon_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn([
                'activity_audition_response_due_hours',
                'activity_job_session_upcoming_hours',
                'activity_job_delivery_due_hours',
                'activity_invoice_due_soon_days',
                'activity_usage_rights_expiring_days',
            ]);
        });
    }
};
