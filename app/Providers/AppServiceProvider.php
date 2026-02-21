<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Activity;
use App\Models\Agent;
use App\Models\Attachment;
use App\Models\Audition;
use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\ExpenseDefinition;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Job;
use App\Models\Platform;
use App\Models\Settings;
use App\Models\UsageRight;
use App\Models\User;
use App\Observers\AuditionObserver;
use App\Observers\ContactObserver;
use App\Observers\InvoiceObserver;
use App\Observers\JobObserver;
use App\Observers\UsageRightObserver;
use App\Observers\UserObserver;
use App\Policies\ContactablePolicy;
use App\Policies\InvoiceItemPolicy;
use App\Policies\OwnedByUserPolicy;
use App\Policies\UsageRightPolicy;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Blueprint macro for monetary columns
        Blueprint::macro('money', function (string $name) {
            /** @var Blueprint $this */
            $this->bigInteger("{$name}_cents");
            $this->string("{$name}_currency", 3)->default('USD');
        });
        Blueprint::macro('moneyNullable', function (string $name) {
            /** @var Blueprint $this */
            $this->bigInteger("{$name}_cents")->nullable();
            $this->string("{$name}_currency", 3)->nullable();
        });

        User::observe(UserObserver::class);
        Audition::observe(AuditionObserver::class);
        Contact::observe(ContactObserver::class);
        Invoice::observe(InvoiceObserver::class);
        Job::observe(JobObserver::class);
        UsageRight::observe(UsageRightObserver::class);

        $ownedModels = [
            Attachment::class,
            Audition::class,
            BusinessProfile::class,
            Contact::class,
            Expense::class,
            ExpenseDefinition::class,
            Activity::class,
            Invoice::class,
            Job::class,
            Platform::class,
            Settings::class,
        ];

        foreach ($ownedModels as $model) {
            Gate::policy($model, OwnedByUserPolicy::class);
        }

        Gate::policy(Agent::class, ContactablePolicy::class);
        Gate::policy(Client::class, ContactablePolicy::class);
        Gate::policy(UsageRight::class, UsageRightPolicy::class);
        Gate::policy(InvoiceItem::class, InvoiceItemPolicy::class);
    }
}
