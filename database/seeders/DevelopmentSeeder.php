<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Enums\AuditionStatus;
use App\Enums\Enums\InvoiceStatus;
use App\Enums\Enums\JobStatus;
use App\Enums\Enums\Recurrence;
use App\Models\Activity;
use App\Models\Agent;
use App\Models\Attachment;
use App\Models\Audition;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\ExpenseDefinition;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Job;
use App\Models\Note;
use App\Models\Platform;
use App\Models\UsageRight;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DevelopmentSeeder extends Seeder
{
    private const TEST_EMAIL = 'test@example.com';

    private const TEST_AUTH_CODE = '123456';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Safety check: Only run in local environment
        if (! app()->environment('local')) {
            $this->command->info('Development seeder can only run in local environment');

            return;
        }

        DB::transaction(function () {
            $this->command->info('Starting development seeder...');

            // Idempotency: Delete existing test user and all related data
            $this->cleanupExistingData();

            // Phase 1: User & Auth
            $user = $this->createUser();
            $this->createBusinessProfile($user);

            // Phase 2: Contact Entities
            [$clients, $agents, $contacts] = $this->createContactEntities($user);

            // Phase 3: Platforms
            $platforms = $this->createPlatforms($user);

            // Phase 4: Auditions
            $auditions = $this->createAuditions($user, $platforms, $contacts);

            // Phase 5: Jobs
            $jobs = $this->createJobs($user, $auditions, $clients, $agents, $contacts);

            // Phase 6: Usage Rights
            $usageRights = $this->createUsageRights($auditions, $jobs);

            // Phase 7: Invoices
            $invoices = $this->createInvoices($user, $jobs, $clients, $contacts);

            // Phase 7.5: Revenue by source seed data
            $this->createRevenueBySourceSeeds($user, $platforms, $clients, $contacts);

            // Phase 8: Expenses
            $this->createExpenses($user);

            // Phase 9: Attachments
            $this->createAttachments($user, $contacts, $auditions, $jobs, $invoices);

            // Phase 10: Activity Actions
            $activities = $this->createActivities($user, $auditions, $jobs, $invoices, $usageRights);

            // Phase 11: Historical Revenue Data
            $this->createHistoricalRevenueData($user, $clients, $contacts);

            // Phase 12: Notes
            $this->createNotes($user);

            $this->command->info('Development seeder completed successfully!');
            $this->command->info('Test user: '.self::TEST_EMAIL);
            $this->command->info('Auth code: '.self::TEST_AUTH_CODE.' (hardcoded in AuthCodeService for local dev)');
        });

        Artisan::call('search:reindex', ['--flush' => true]);
        $this->command->line(Artisan::output());
    }

    private function cleanupExistingData(): void
    {
        $this->command->info('Cleaning up existing test data...');

        // Force delete (bypasses soft deletes) to fully reset state
        User::where('email', self::TEST_EMAIL)->forceDelete();
    }

    private function createUser(): User
    {
        $this->command->info('Creating test user...');

        $user = User::create([
            'email' => self::TEST_EMAIL,
            'name' => 'Test User',
            'email_verified_at' => now(),
        ]);

        // Settings is auto-created via UserObserver
        $this->command->info("Created user: {$user->email} (ID: {$user->id})");

        return $user;
    }

    private function createBusinessProfile(User $user): void
    {
        $this->command->info('Updating business profile...');

        // BusinessProfile is auto-created by UserObserver, so we just update it
        $user->businessProfile->update([
            'business_name' => 'Test Voice Studio',
            'address_street' => '123 Voice Lane',
            'address_city' => 'Los Angeles',
            'address_state' => 'CA',
            'address_country' => 'USA',
            'address_postal' => '90001',
            'phone' => '+1-555-0123',
            'email' => 'billing@testvoicestudio.com',
            'payment_instructions' => 'Payment due within 30 days. Wire transfer or check accepted.',
        ]);
    }

    private function createContactEntities(User $user): array
    {
        $this->command->info('Creating contact entities...');

        $clients = collect();
        $agents = collect();
        $contacts = collect();

        // Create 3 clients
        $clientData = [
            [
                'type' => 'company',
                'industry' => 'Advertising',
                'payment_terms' => 'Net 30',
                'name' => 'Acme Advertising Agency',
                'email' => 'production@acmeads.com',
                'phone' => '+1-555-0201',
                'address_street' => '456 Marketing Blvd',
                'address_city' => 'New York',
                'address_state' => 'NY',
                'address_country' => 'USA',
                'address_postal' => '10001',
            ],
            [
                'type' => 'company',
                'industry' => 'E-Learning',
                'payment_terms' => 'Net 15',
                'name' => 'EduTech Solutions',
                'email' => 'content@edutech.com',
                'phone' => '+1-555-0202',
                'address_street' => null,
                'address_city' => null,
                'address_state' => null,
                'address_country' => null,
                'address_postal' => null,
            ],
            [
                'type' => 'individual',
                'industry' => 'Independent Film',
                'payment_terms' => 'Upon delivery',
                'name' => 'Sarah Johnson',
                'email' => 'sarah.j@filmmaker.com',
                'phone' => null,
                'address_street' => null,
                'address_city' => null,
                'address_state' => null,
                'address_country' => null,
                'address_postal' => null,
            ],
        ];

        foreach ($clientData as $data) {
            $client = Client::create([
                'type' => $data['type'],
                'industry' => $data['industry'],
                'payment_terms' => $data['payment_terms'],
            ]);

            $contact = Contact::create([
                'user_id' => $user->id,
                'contactable_type' => Client::class,
                'contactable_id' => $client->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address_street' => $data['address_street'],
                'address_city' => $data['address_city'],
                'address_state' => $data['address_state'],
                'address_country' => $data['address_country'],
                'address_postal' => $data['address_postal'],
                'last_contacted_at' => now()->subDays(rand(1, 30)),
            ]);

            $clients->push($client);
            $contacts->push($contact);
        }

        // Create 2 agents
        $agentData = [
            [
                'agency_name' => 'Premier Voice Talent Agency',
                'commission_rate' => 1000, // 10% in basis points
                'territories' => ['US', 'CA'],
                'is_exclusive' => false,
                'contract_start' => now()->subYears(2),
                'contract_end' => now()->addYear(),
                'name' => 'Michael Chen',
                'email' => 'michael@premiervoice.com',
                'phone' => '+1-555-0301',
            ],
            [
                'agency_name' => null,
                'commission_rate' => 1500, // 15% in basis points
                'territories' => ['US'],
                'is_exclusive' => true,
                'contract_start' => now()->subYear(),
                'contract_end' => null,
                'name' => 'Lisa Martinez',
                'email' => 'lisa@voiceagent.com',
                'phone' => '+1-555-0302',
            ],
        ];

        foreach ($agentData as $data) {
            $agent = Agent::create([
                'agency_name' => $data['agency_name'],
                'commission_rate' => $data['commission_rate'],
                'territories' => $data['territories'],
                'is_exclusive' => $data['is_exclusive'],
                'contract_start' => $data['contract_start'],
                'contract_end' => $data['contract_end'],
            ]);

            $contact = Contact::create([
                'user_id' => $user->id,
                'contactable_type' => Agent::class,
                'contactable_id' => $agent->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'last_contacted_at' => now()->subDays(rand(1, 60)),
            ]);

            $agents->push($agent);
            $contacts->push($contact);
        }

        $this->command->info("Created {$clients->count()} clients and {$agents->count()} agents");

        return [$clients, $agents, $contacts];
    }

    private function createPlatforms(User $user): \Illuminate\Support\Collection
    {
        $this->command->info('Creating platforms...');

        $platformData = [
            ['name' => 'Voices.com', 'url' => 'https://voices.com', 'username' => 'testuser'],
            ['name' => 'Voice123', 'url' => 'https://voice123.com', 'username' => 'test_voice'],
            ['name' => 'ACX (Audiobook Creation Exchange)', 'url' => 'https://acx.com', 'username' => 'test_acx'],
            ['name' => 'Direct Referrals', 'url' => null, 'username' => null],
        ];

        $platforms = collect();

        foreach ($platformData as $data) {
            $platform = Platform::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'url' => $data['url'],
                'username' => $data['username'],
            ]);

            $platforms->push($platform);
        }

        $this->command->info("Created {$platforms->count()} platforms");

        return $platforms;
    }

    private function createAuditions(User $user, \Illuminate\Support\Collection $platforms, \Illuminate\Support\Collection $contacts): \Illuminate\Support\Collection
    {
        $this->command->info('Creating auditions...');

        $auditions = collect();

        // Mix of statuses for realistic data
        $statuses = [
            AuditionStatus::WON,
            AuditionStatus::WON,
            AuditionStatus::LOST,
            AuditionStatus::LOST,
            AuditionStatus::SUBMITTED,
            AuditionStatus::SUBMITTED,
            AuditionStatus::SHORTLISTED,
            AuditionStatus::RECEIVED,
            AuditionStatus::PREPARING,
            AuditionStatus::CALLBACK,
        ];

        $categories = [
            'commercial',
            'animation',
            'video_game',
            'audiobook',
            'elearning',
            'corporate',
            'narration',
            'promo',
        ];

        $rateTypes = ['flat', 'hourly', 'per_finished_hour', 'per_word'];

        // Create 60 auditions spread across the last 90 days
        for ($i = 0; $i < 60; $i++) {
            // Random submission date in the last 90 days
            $daysAgo = rand(0, 90);
            $submittedAt = now()->subDays($daysAgo);

            // Pick random status
            $status = $statuses[array_rand($statuses)];

            // Pick random source (platform or contact)
            $sourceables = $platforms->merge([$contacts[0]]);
            $sourceable = $sourceables->random();

            // Generate realistic rate based on category
            $category = $categories[array_rand($categories)];
            $rateType = $rateTypes[array_rand($rateTypes)];

            $rateRange = match ($category) {
                'commercial' => [100000, 300000], // $1,000-$3,000
                'video_game' => [150000, 500000], // $1,500-$5,000
                'audiobook' => [25000, 45000], // $250-$450 PFH
                'corporate', 'elearning' => [50000, 150000], // $500-$1,500
                default => [30000, 100000], // $300-$1,000
            };

            $quotedRate = rand($rateRange[0], $rateRange[1]);
            $budgetMin = (int) ($quotedRate * 0.7);
            $budgetMax = (int) ($quotedRate * 1.3);

            // Response deadline
            $responseDeadline = $submittedAt->copy()->addDays(rand(5, 14));

            // Brand names (some null, some present)
            $brandNames = [
                'AutoDrive Motors',
                'SafetyFirst Corp',
                'Epic Games Studio',
                'Netflix Studios',
                'Amazon Originals',
                'Samsung Electronics',
                'Nike Sports',
                'Apple Inc',
                'Microsoft',
                'Disney Animation',
                null,
                null,
                null, // 30% chance of null
            ];

            $brandName = $brandNames[array_rand($brandNames)];

            // Project titles
            $projectTitles = [
                'National TV Commercial',
                'E-Learning Module',
                'Video Game Character',
                'Audiobook Narration',
                'Corporate Training Video',
                'Product Explainer',
                'Documentary Narration',
                'Animated Series Character',
                'Radio Spot',
                'Podcast Introduction',
                'Mobile App Tutorial',
                'Social Media Campaign',
            ];

            $projectTitle = $projectTitles[array_rand($projectTitles)].' #'.($i + 1);

            $audition = Audition::create([
                'user_id' => $user->id,
                'sourceable_type' => get_class($sourceable),
                'sourceable_id' => $sourceable->id,
                'project_title' => $projectTitle,
                'brand_name' => $brandName,
                'category' => $category,
                'status' => $status,
                'rate_type' => $rateType,
                'quoted_rate' => $quotedRate,
                'budget_min' => $budgetMin,
                'budget_max' => $budgetMax,
                'response_deadline' => $responseDeadline,
                'submitted_at' => $submittedAt,
                'word_count' => rand(50, 500),
            ]);

            $auditions->push($audition);
        }

        // Calculate booking rate for info
        $totalAuditions = $auditions->count();
        $wonAuditions = $auditions->filter(fn ($a) => $a->status === AuditionStatus::WON)->count();
        $bookingRate = $totalAuditions > 0 ? round(($wonAuditions / $totalAuditions) * 100, 1) : 0;

        $this->command->info("Created {$auditions->count()} auditions (Booking Rate: {$bookingRate}% - {$wonAuditions} won out of {$totalAuditions})");

        return $auditions;
    }

    private function createJobs(User $user, \Illuminate\Support\Collection $auditions, \Illuminate\Support\Collection $clients, \Illuminate\Support\Collection $agents, \Illuminate\Support\Collection $contacts): \Illuminate\Support\Collection
    {
        $this->command->info('Creating jobs...');

        $jobs = collect();

        // Get won auditions
        $wonAuditions = $auditions->filter(fn ($a) => $a->status === AuditionStatus::WON);

        // Get client contacts
        $clientContacts = $contacts->filter(fn ($c) => $c->contactable_type === Client::class);

        $jobData = [
            [
                'audition' => $wonAuditions[0] ?? null,
                'client_contact' => $clientContacts[0],
                'agent_contact' => $contacts->firstWhere('contactable_type', Agent::class),
                'project_title' => 'National Car Commercial - Final',
                'brand_name' => 'AutoDrive Motors',
                'category' => 'commercial',
                'status' => JobStatus::COMPLETED,
                'contracted_rate' => [
                    'amount_cents' => 150000,
                    'currency' => 'USD',
                ],
                'rate_type' => 'flat',
                'session_date' => now()->subDays(15),
                'delivery_deadline' => now()->subDays(10),
                'delivered_at' => now()->subDays(11),
            ],
            [
                'audition' => $wonAuditions[1] ?? null,
                'client_contact' => $clientContacts[1] ?? $clientContacts[0],
                'agent_contact' => null,
                'project_title' => 'Audiobook: Mystery Thriller - Production',
                'brand_name' => null,
                'category' => 'audiobook',
                'status' => JobStatus::IN_PROGRESS,
                'contracted_rate' => [
                    'amount_cents' => 35000,
                    'currency' => 'CAD',
                ],
                'rate_type' => 'per_finished_hour',
                'session_date' => now()->addDays(2),
                'delivery_deadline' => now()->addDays(30),
                'delivered_at' => null,
                'estimated_hours' => 3.0,
                'actual_hours' => 2.5,
                'word_count' => 27000,
            ],
            [
                'audition' => null, // Direct booking
                'client_contact' => $clientContacts[2] ?? $clientContacts[0],
                'agent_contact' => null,
                'project_title' => 'Podcast Introduction',
                'brand_name' => 'The Daily Brief',
                'category' => 'podcast',
                'status' => JobStatus::DELIVERED,
                'contracted_rate' => [
                    'amount_cents' => 50000,
                    'currency' => 'USD',
                ],
                'rate_type' => 'flat',
                'session_date' => now()->subDays(5),
                'delivery_deadline' => now()->subDays(2),
                'delivered_at' => now()->subDays(3),
            ],
        ];

        foreach ($jobData as $data) {
            $job = Job::create([
                'user_id' => $user->id,
                'audition_id' => $data['audition']?->id,
                'client_id' => $data['client_contact']->id,
                'agent_id' => $data['agent_contact']?->id,
                'project_title' => $data['project_title'],
                'brand_name' => $data['brand_name'],
                'category' => $data['category'],
                'status' => $data['status'],
                'contracted_rate' => $data['contracted_rate'],
                'rate_type' => $data['rate_type'],
                'session_date' => $data['session_date'],
                'delivery_deadline' => $data['delivery_deadline'],
                'delivered_at' => $data['delivered_at'],
                'estimated_hours' => $data['estimated_hours'] ?? null,
                'actual_hours' => $data['actual_hours'] ?? null,
                'word_count' => $data['word_count'] ?? rand(100, 1000),
            ]);

            $jobs->push($job);
        }

        $this->command->info("Created {$jobs->count()} jobs");

        return $jobs;
    }

    private function createUsageRights(\Illuminate\Support\Collection $auditions, \Illuminate\Support\Collection $jobs): \Illuminate\Support\Collection
    {
        $this->command->info('Creating usage rights...');

        $usageRights = collect();
        $count = 0;

        // Create usage rights for 50% of auditions
        foreach ($auditions->random(min(3, $auditions->count())) as $audition) {
            $usageRight = UsageRight::create([
                'usable_type' => Audition::class,
                'usable_id' => $audition->id,
                'type' => 'broadcast',
                'media_types' => ['tv', 'radio', 'digital'],
                'geographic_scope' => 'national',
                'duration_type' => 'fixed',
                'duration_months' => 12,
                'start_date' => now(),
                'expiration_date' => now()->addYear(),
                'exclusivity' => false,
                'ai_rights_granted' => false,
            ]);
            $usageRights->push($usageRight);
            $count++;
        }

        // Create usage rights for all jobs
        foreach ($jobs as $job) {
            $usageRight = UsageRight::create([
                'usable_type' => Job::class,
                'usable_id' => $job->id,
                'type' => 'broadcast',
                'media_types' => ['tv', 'digital', 'social_media'],
                'geographic_scope' => $job->category === 'commercial' ? 'national' : 'worldwide',
                'duration_type' => $job->category === 'audiobook' ? 'perpetual' : 'fixed',
                'duration_months' => $job->category === 'audiobook' ? null : 24,
                'start_date' => $job->session_date,
                'expiration_date' => $job->category === 'audiobook' ? null : $job->session_date?->addMonths(24),
                'exclusivity' => $job->category === 'commercial',
                'exclusivity_category' => $job->category === 'commercial' ? 'automotive' : null,
                'ai_rights_granted' => false,
            ]);
            $usageRights->push($usageRight);
            $count++;
        }

        // Create additional usage rights for a couple of auditions
        foreach ($auditions->take(2) as $audition) {
            $usageRight = UsageRight::create([
                'usable_type' => Audition::class,
                'usable_id' => $audition->id,
                'type' => 'non_broadcast',
                'media_types' => ['digital', 'social_media'],
                'geographic_scope' => 'regional',
                'duration_type' => 'fixed',
                'duration_months' => 6,
                'start_date' => now(),
                'expiration_date' => now()->addMonths(6),
                'exclusivity' => false,
                'ai_rights_granted' => false,
            ]);
            $usageRights->push($usageRight);
            $count++;
        }

        // Create additional usage rights for a couple of jobs
        foreach ($jobs->take(2) as $job) {
            $usageRight = UsageRight::create([
                'usable_type' => Job::class,
                'usable_id' => $job->id,
                'type' => 'non_broadcast',
                'media_types' => ['digital', 'internal'],
                'geographic_scope' => 'worldwide',
                'duration_type' => 'perpetual',
                'duration_months' => null,
                'start_date' => $job->session_date,
                'expiration_date' => null,
                'exclusivity' => false,
                'ai_rights_granted' => false,
            ]);
            $usageRights->push($usageRight);
            $count++;
        }

        $this->command->info("Created {$count} usage rights");

        return $usageRights;
    }

    private function createInvoices(User $user, \Illuminate\Support\Collection $jobs, \Illuminate\Support\Collection $clients, \Illuminate\Support\Collection $contacts): \Illuminate\Support\Collection
    {
        $this->command->info('Creating invoices...');

        $invoices = collect();

        $clientContacts = $contacts->filter(fn ($c) => $c->contactable_type === Client::class);

        $invoiceData = [
            [
                'job' => $jobs[0] ?? null,
                'client_contact' => $clientContacts[0],
                'invoice_number' => 'INV-2025-001',
                'status' => InvoiceStatus::PAID,
                'issued_at' => now()->subDays(6),
                'due_at' => now()->addDays(24),
                'paid_at' => now()->subDays(3),
                'tax_rate' => 0.0825, // 8.25%
                'currency' => 'USD',
                'items' => [
                    ['description' => 'Voice Over - National Car Commercial', 'quantity' => 1, 'unit_price' => 150000],
                    ['description' => 'Studio Session Fee', 'quantity' => 1, 'unit_price' => 20000],
                ],
            ],
            [
                'job' => $jobs[1] ?? null,
                'client_contact' => $clientContacts[1] ?? $clientContacts[0],
                'invoice_number' => 'INV-2025-002',
                'status' => InvoiceStatus::SENT,
                'issued_at' => now()->subDays(5),
                'due_at' => now()->addDays(25),
                'paid_at' => null,
                'tax_rate' => null,
                'currency' => 'CAD',
                'items' => [
                    ['description' => 'Audiobook Recording - 3.0 PFH @ $350', 'quantity' => 3.0, 'unit_price' => 35000],
                ],
            ],
            [
                'job' => null, // Standalone invoice
                'client_contact' => $clientContacts[2] ?? $clientContacts[0],
                'invoice_number' => 'INV-2025-003',
                'status' => InvoiceStatus::DRAFT,
                'issued_at' => now(),
                'due_at' => now()->addDays(30),
                'paid_at' => null,
                'tax_rate' => 0.0700, // 7%
                'currency' => 'USD',
                'items' => [
                    ['description' => 'Voice Over Services', 'quantity' => 2, 'unit_price' => 50000],
                    ['description' => 'Rush Delivery Fee', 'quantity' => 1, 'unit_price' => 15000],
                ],
            ],
        ];

        foreach ($invoiceData as $data) {
            $currency = $data['currency'] ?? 'USD';

            // Calculate subtotal
            $subtotal = collect($data['items'])->sum(function ($item) {
                return (int) ($item['quantity'] * $item['unit_price']);
            });

            // Calculate tax
            $taxAmount = $data['tax_rate'] ? (int) ($subtotal * $data['tax_rate']) : 0;

            // Calculate total
            $total = $subtotal + $taxAmount;

            $invoice = Invoice::create([
                'user_id' => $user->id,
                'job_id' => $data['job']?->id,
                'client_id' => $data['client_contact']->id,
                'invoice_number' => $data['invoice_number'],
                'issued_at' => $data['issued_at'],
                'due_at' => $data['due_at'],
                'subtotal' => [
                    'amount_cents' => $subtotal,
                    'currency' => $currency,
                ],
                'tax_rate' => $data['tax_rate'],
                'tax_amount' => $taxAmount ? [
                    'amount_cents' => $taxAmount,
                    'currency' => $currency,
                ] : null,
                'total' => [
                    'amount_cents' => $total,
                    'currency' => $currency,
                ],
                'status' => $data['status'],
                'paid_at' => $data['paid_at'],
            ]);

            // Create invoice items
            foreach ($data['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => (int) ($item['quantity'] * $item['unit_price']),
                ]);
            }

            $invoices->push($invoice);
        }

        $this->command->info("Created {$invoices->count()} invoices");

        return $invoices;
    }

    private function createRevenueBySourceSeeds(
        User $user,
        \Illuminate\Support\Collection $platforms,
        \Illuminate\Support\Collection $clients,
        \Illuminate\Support\Collection $contacts
    ): void {
        $this->command->info('Creating revenue by source seed data...');

        // Add one more direct client to ensure 10 total sources (platforms + agents + clients)
        $extraClient = Client::create([
            'type' => 'company',
            'industry' => 'Podcasting',
            'payment_terms' => 'Net 30',
        ]);

        $extraClientContact = Contact::create([
            'user_id' => $user->id,
            'contactable_type' => Client::class,
            'contactable_id' => $extraClient->id,
            'name' => 'Signal Ridge Media',
            'email' => 'production@signalridge.fm',
            'phone' => '+1-555-0244',
            'address_street' => '22 Studio Way',
            'address_city' => 'Austin',
            'address_state' => 'TX',
            'address_country' => 'USA',
            'address_postal' => '78701',
            'last_contacted_at' => now()->subDays(9),
        ]);

        $clients->push($extraClient);
        $contacts->push($extraClientContact);

        $clientContacts = $contacts->filter(fn ($c) => $c->contactable_type === Client::class)->values();
        $agentContacts = $contacts->filter(fn ($c) => $c->contactable_type === Agent::class)->values();

        $createInvoice = function (
            ?Job $job,
            Contact $client,
            InvoiceStatus|string $status,
            int $amountCents,
            string $currency,
            string $description,
            ?\Carbon\Carbon $issuedAt = null
        ) use ($user): Invoice {
            $issuedAt = $issuedAt ?? now()->subDays(rand(4, 18));
            $dueAt = $issuedAt->copy()->addDays(30);
            $paidAt = $status === InvoiceStatus::PAID ? $issuedAt->copy()->addDays(rand(2, 12)) : null;

            $invoice = Invoice::create([
                'user_id' => $user->id,
                'job_id' => $job?->id,
                'client_id' => $client->id,
                'invoice_number' => 'INV-TOP-'.strtoupper(\Illuminate\Support\Str::random(8)),
                'issued_at' => $issuedAt,
                'due_at' => $dueAt,
                'subtotal' => [
                    'amount_cents' => $amountCents,
                    'currency' => $currency,
                ],
                'tax_rate' => 0,
                'tax_amount' => null,
                'total' => [
                    'amount_cents' => $amountCents,
                    'currency' => $currency,
                ],
                'status' => $status,
                'paid_at' => $paidAt,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $description,
                'quantity' => 1,
                'unit_price' => $amountCents,
                'amount' => $amountCents,
            ]);

            return $invoice;
        };

        $platformRevenue = [
            ['amount' => 125000, 'status' => InvoiceStatus::PAID, 'title' => 'National TV Spot - Buyout'],
            ['amount' => 98000, 'status' => InvoiceStatus::PAID, 'title' => 'Game Character Pack'],
            ['amount' => 82000, 'status' => InvoiceStatus::SENT, 'title' => 'E-Learning Module'],
            ['amount' => 65000, 'status' => InvoiceStatus::SENT, 'title' => 'Explainer Video'],
        ];

        foreach ($platforms->take(4) as $index => $platform) {
            $seed = $platformRevenue[$index] ?? $platformRevenue[0];
            $clientContact = $clientContacts[$index % $clientContacts->count()];

            $audition = Audition::create([
                'user_id' => $user->id,
                'sourceable_type' => Platform::class,
                'sourceable_id' => $platform->id,
                'project_title' => $seed['title'],
                'brand_name' => $clientContact->name,
                'category' => 'commercial',
                'status' => AuditionStatus::WON,
                'rate_type' => 'flat',
                'quoted_rate' => $seed['amount'],
                'budget_min' => (int) round($seed['amount'] * 0.8),
                'budget_max' => (int) round($seed['amount'] * 1.2),
                'response_deadline' => now()->addDays(5),
                'submitted_at' => now()->subDays(rand(12, 40)),
                'word_count' => rand(120, 350),
            ]);

            $job = Job::create([
                'user_id' => $user->id,
                'audition_id' => $audition->id,
                'client_id' => $clientContact->id,
                'agent_id' => null,
                'project_title' => $seed['title'],
                'brand_name' => $clientContact->name,
                'category' => 'commercial',
                'status' => $seed['status'] === InvoiceStatus::PAID ? JobStatus::COMPLETED : JobStatus::IN_PROGRESS,
                'contracted_rate' => [
                    'amount_cents' => $seed['amount'],
                    'currency' => 'USD',
                ],
                'rate_type' => 'flat',
                'session_date' => now()->subDays(rand(8, 25)),
                'delivery_deadline' => now()->addDays(7),
                'delivered_at' => $seed['status'] === InvoiceStatus::PAID ? now()->subDays(5) : null,
                'word_count' => rand(120, 350),
            ]);

            $createInvoice(
                $job,
                $clientContact,
                $seed['status'],
                $seed['amount'],
                'USD',
                $seed['title']
            );
        }

        $agentRevenue = [
            ['amount' => 88000, 'status' => InvoiceStatus::PAID, 'title' => 'Regional Radio Campaign'],
            ['amount' => 64000, 'status' => InvoiceStatus::SENT, 'title' => 'Animated Short'],
        ];

        foreach ($agentContacts as $index => $agentContact) {
            $seed = $agentRevenue[$index] ?? $agentRevenue[0];
            $clientContact = $clientContacts[$index % $clientContacts->count()];

            $job = Job::create([
                'user_id' => $user->id,
                'audition_id' => null,
                'client_id' => $clientContact->id,
                'agent_id' => $agentContact->id,
                'project_title' => $seed['title'],
                'brand_name' => $clientContact->name,
                'category' => 'commercial',
                'status' => $seed['status'] === InvoiceStatus::PAID ? JobStatus::COMPLETED : JobStatus::IN_PROGRESS,
                'contracted_rate' => [
                    'amount_cents' => $seed['amount'],
                    'currency' => 'USD',
                ],
                'rate_type' => 'flat',
                'session_date' => now()->subDays(rand(6, 20)),
                'delivery_deadline' => now()->addDays(10),
                'delivered_at' => $seed['status'] === InvoiceStatus::PAID ? now()->subDays(3) : null,
                'word_count' => rand(100, 320),
            ]);

            $createInvoice(
                $job,
                $clientContact,
                $seed['status'],
                $seed['amount'],
                'USD',
                $seed['title']
            );
        }

        $directRevenue = [
            ['amount' => 76000, 'status' => InvoiceStatus::PAID, 'title' => 'Product Launch Video'],
            ['amount' => 54000, 'status' => InvoiceStatus::PAID, 'title' => 'Corporate Onboarding'],
            ['amount' => 42000, 'status' => InvoiceStatus::PAID, 'title' => 'Podcast Intro Package'],
            ['amount' => 48000, 'status' => InvoiceStatus::DRAFT, 'title' => 'Social Media Ads'],
        ];

        foreach ($clientContacts->take(4) as $index => $clientContact) {
            $seed = $directRevenue[$index] ?? $directRevenue[0];

            $job = Job::create([
                'user_id' => $user->id,
                'audition_id' => null,
                'client_id' => $clientContact->id,
                'agent_id' => null,
                'project_title' => $seed['title'],
                'brand_name' => $clientContact->name,
                'category' => 'corporate',
                'status' => $seed['status'] === InvoiceStatus::PAID ? JobStatus::COMPLETED : JobStatus::IN_PROGRESS,
                'contracted_rate' => [
                    'amount_cents' => $seed['amount'],
                    'currency' => 'USD',
                ],
                'rate_type' => 'flat',
                'session_date' => now()->subDays(rand(5, 22)),
                'delivery_deadline' => now()->addDays(9),
                'delivered_at' => $seed['status'] === InvoiceStatus::PAID ? now()->subDays(2) : null,
                'word_count' => rand(90, 300),
            ]);

            if ($seed['status'] === InvoiceStatus::DRAFT) {
                continue;
            }

            $createInvoice(
                $job,
                $clientContact,
                $seed['status'],
                $seed['amount'],
                'USD',
                $seed['title']
            );
        }

        // One unbilled active job to ensure in-flight shows from direct clients
        Job::create([
            'user_id' => $user->id,
            'audition_id' => null,
            'client_id' => $clientContacts->last()->id,
            'agent_id' => null,
            'project_title' => 'SaaS Feature Walkthrough',
            'brand_name' => $clientContacts->last()->name,
            'category' => 'corporate',
            'status' => JobStatus::IN_PROGRESS,
            'contracted_rate' => [
                'amount_cents' => 52000,
                'currency' => 'USD',
            ],
            'rate_type' => 'flat',
            'session_date' => now()->addDays(4),
            'delivery_deadline' => now()->addDays(14),
            'delivered_at' => null,
            'word_count' => 260,
        ]);
    }

    private function createExpenses(User $user): void
    {
        $this->command->info('Creating expenses...');

        // Create expense definitions
        $definitions = collect();

        $definitionData = [
            [
                'name' => 'Adobe Creative Cloud',
                'amount' => ['amount_cents' => 5499, 'currency' => 'USD'], // $54.99
                'category' => 'membership',
                'recurrence' => Recurrence::MONTHLY,
                'recurrence_day' => 1,
                'starts_at' => now()->subYear(),
            ],
            [
                'name' => 'Professional Insurance',
                'amount' => ['amount_cents' => 120000, 'currency' => 'USD'], // $1,200
                'category' => 'professional_services',
                'recurrence' => Recurrence::YEARLY,
                'recurrence_day' => 1,
                'starts_at' => now()->startOfYear(),
            ],
            [
                'name' => 'Voices.com Membership',
                'amount' => ['amount_cents' => 49900, 'currency' => 'USD'], // $499
                'category' => 'membership',
                'recurrence' => Recurrence::YEARLY,
                'recurrence_day' => 15,
                'starts_at' => now()->subMonths(6),
            ],
        ];

        foreach ($definitionData as $data) {
            $definition = ExpenseDefinition::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'amount' => $data['amount'],
                'category' => $data['category'],
                'recurrence' => $data['recurrence'],
                'recurrence_day' => $data['recurrence_day'],
                'starts_at' => $data['starts_at'],
                'is_active' => true,
            ]);

            $definitions->push($definition);
        }

        // Create actual expenses
        $expenseData = [
            // From definitions
            ['definition' => $definitions[0], 'date' => now()->subDays(30)],
            ['definition' => $definitions[0], 'date' => now()->subDays(60)],
            ['definition' => $definitions[1], 'date' => now()->startOfYear()],

            // One-off expenses
            [
                'definition' => null,
                'description' => 'New Studio Microphone - Neumann U87',
                'amount' => ['amount_cents' => 349900, 'currency' => 'USD'], // $3,499
                'category' => 'equipment',
                'date' => now()->subDays(45),
            ],
            [
                'definition' => null,
                'description' => 'Marketing - Google Ads Campaign',
                'amount' => ['amount_cents' => 75000, 'currency' => 'USD'], // $750
                'category' => 'marketing',
                'date' => now()->subDays(20),
            ],
            [
                'definition' => null,
                'description' => 'Acoustic Panels',
                'amount' => ['amount_cents' => 45000, 'currency' => 'USD'], // $450
                'category' => 'equipment',
                'date' => now()->subDays(15),
            ],
        ];

        foreach ($expenseData as $data) {
            if ($data['definition'] ?? null) {
                Expense::create([
                    'user_id' => $user->id,
                    'expense_definition_id' => $data['definition']->id,
                    'description' => $data['definition']->name,
                    'amount' => $data['definition']->amount,
                    'category' => $data['definition']->category,
                    'date' => $data['date'],
                ]);
            } else {
                Expense::create([
                    'user_id' => $user->id,
                    'description' => $data['description'],
                    'amount' => $data['amount'],
                    'category' => $data['category'],
                    'date' => $data['date'],
                ]);
            }
        }

        $this->command->info("Created {$definitions->count()} expense definitions and ".count($expenseData).' expenses');
    }

    private function createAttachments(User $user, \Illuminate\Support\Collection $contacts, \Illuminate\Support\Collection $auditions, \Illuminate\Support\Collection $jobs, \Illuminate\Support\Collection $invoices): void
    {
        $this->command->info('Creating attachments...');

        $count = 0;

        // Attachments for contacts
        foreach ($contacts->take(2) as $contact) {
            Attachment::create([
                'user_id' => $user->id,
                'attachable_type' => Contact::class,
                'attachable_id' => $contact->id,
                'filename' => 'contract_'.uniqid().'.pdf',
                'original_filename' => 'signed_contract.pdf',
                'mime_type' => 'application/pdf',
                'size' => 245678,
                'disk' => 'local',
                'path' => 'attachments/contacts/contract_'.uniqid().'.pdf',
                'category' => 'contract',
            ]);
            $count++;
        }

        // Attachments for auditions
        foreach ($auditions->take(3) as $audition) {
            Attachment::create([
                'user_id' => $user->id,
                'attachable_type' => Audition::class,
                'attachable_id' => $audition->id,
                'filename' => 'audition_'.uniqid().'.mp3',
                'original_filename' => 'my_audition.mp3',
                'mime_type' => 'audio/mpeg',
                'size' => 1245678,
                'disk' => 'local',
                'path' => 'attachments/auditions/audition_'.uniqid().'.mp3',
                'category' => 'recording',
            ]);
            $count++;
        }

        // Attachments for jobs
        foreach ($jobs->take(2) as $job) {
            Attachment::create([
                'user_id' => $user->id,
                'attachable_type' => Job::class,
                'attachable_id' => $job->id,
                'filename' => 'final_'.uniqid().'.wav',
                'original_filename' => 'final_mix.wav',
                'mime_type' => 'audio/wav',
                'size' => 5678912,
                'disk' => 'local',
                'path' => 'attachments/jobs/final_'.uniqid().'.wav',
                'category' => 'recording',
            ]);
            $count++;
        }

        // Attachments for invoices
        foreach ($invoices->take(2) as $invoice) {
            Attachment::create([
                'user_id' => $user->id,
                'attachable_type' => Invoice::class,
                'attachable_id' => $invoice->id,
                'filename' => 'invoice_'.uniqid().'.pdf',
                'original_filename' => $invoice->invoice_number.'.pdf',
                'mime_type' => 'application/pdf',
                'size' => 123456,
                'disk' => 'local',
                'path' => 'attachments/invoices/invoice_'.uniqid().'.pdf',
                'category' => 'invoice_pdf',
            ]);
            $count++;
        }

        $this->command->info("Created {$count} attachments");
    }

    private function createActivities(User $user, \Illuminate\Support\Collection $auditions, \Illuminate\Support\Collection $jobs, \Illuminate\Support\Collection $invoices, \Illuminate\Support\Collection $usageRights): \Illuminate\Support\Collection
    {
        $this->command->info('Creating activities...');

        // Additional entities whose state intentionally satisfies each activity trigger.
        $extraClient = Client::create([
            'type' => 'company',
            'industry' => 'Technology',
            'payment_terms' => 'Net 30',
        ]);

        $extraClientContact = Contact::create([
            'user_id' => $user->id,
            'contactable_type' => Client::class,
            'contactable_id' => $extraClient->id,
            'name' => 'Orbit Labs',
            'email' => 'casting@orbitlabs.com',
            'phone' => '+1-555-0410',
            'address_street' => '900 Launch Ave',
            'address_city' => 'Seattle',
            'address_state' => 'WA',
            'address_country' => 'USA',
            'address_postal' => '98101',
            'last_contacted_at' => now()->subDays(4),
        ]);

        $extraPlatform = Platform::create([
            'user_id' => $user->id,
            'name' => 'Casting Pilot',
            'url' => 'https://castingpilot.example',
            'username' => 'test_casting',
        ]);

        $extraAudition = Audition::create([
            'user_id' => $user->id,
            'sourceable_type' => Platform::class,
            'sourceable_id' => $extraPlatform->id,
            'project_title' => 'Smart Home Assistant - Demo',
            'brand_name' => 'Orbit Labs',
            'category' => 'commercial',
            'status' => AuditionStatus::RECEIVED,
            'rate_type' => 'flat',
            'quoted_rate' => 125000,
            'budget_min' => 90000,
            'budget_max' => 160000,
            'response_deadline' => now()->addHours(6),
            'submitted_at' => now()->subDays(1),
            'word_count' => 140,
        ]);

        $extraJobSession = Job::create([
            'user_id' => $user->id,
            'audition_id' => $extraAudition->id,
            'client_id' => $extraClientContact->id,
            'agent_id' => null,
            'project_title' => 'Smart Home Assistant - Production',
            'brand_name' => 'Orbit Labs',
            'category' => 'commercial',
            'status' => JobStatus::IN_PROGRESS,
            'contracted_rate' => [
                'amount_cents' => 175000,
                'currency' => 'USD',
            ],
            'rate_type' => 'flat',
            'session_date' => now()->addHours(4),
            'delivery_deadline' => now()->addDays(4),
            'delivered_at' => null,
            'estimated_hours' => 2.0,
            'actual_hours' => null,
            'word_count' => 220,
        ]);

        $extraJobDelivery = Job::create([
            'user_id' => $user->id,
            'audition_id' => null,
            'client_id' => $extraClientContact->id,
            'agent_id' => null,
            'project_title' => 'Product Explainer - Final',
            'brand_name' => 'Orbit Labs',
            'category' => 'corporate',
            'status' => JobStatus::IN_PROGRESS,
            'contracted_rate' => [
                'amount_cents' => 120000,
                'currency' => 'USD',
            ],
            'rate_type' => 'flat',
            'session_date' => now()->subDay(),
            'delivery_deadline' => now()->addHours(10),
            'delivered_at' => null,
            'estimated_hours' => 1.5,
            'actual_hours' => null,
            'word_count' => 180,
        ]);

        $extraJobRevision = Job::create([
            'user_id' => $user->id,
            'audition_id' => null,
            'client_id' => $extraClientContact->id,
            'agent_id' => null,
            'project_title' => 'Campaign Pickup Lines',
            'brand_name' => 'Orbit Labs',
            'category' => 'commercial',
            'status' => JobStatus::REVISION,
            'contracted_rate' => [
                'amount_cents' => 90000,
                'currency' => 'USD',
            ],
            'rate_type' => 'flat',
            'session_date' => now()->subHours(6),
            'delivery_deadline' => now()->addDays(2),
            'delivered_at' => now()->subHours(1),
            'estimated_hours' => 1.0,
            'actual_hours' => 1.25,
            'word_count' => 120,
        ]);

        $extraInvoiceItems = [
            ['description' => 'Voice Over - Smart Home Assistant', 'quantity' => 1, 'unit_price' => 175000],
            ['description' => 'Mixing & Mastering', 'quantity' => 1, 'unit_price' => 25000],
        ];

        $extraInvoiceSubtotal = collect($extraInvoiceItems)->sum(function ($item) {
            return (int) ($item['quantity'] * $item['unit_price']);
        });
        $extraInvoiceTaxRate = 0.0825;
        $extraInvoiceTaxAmount = (int) ($extraInvoiceSubtotal * $extraInvoiceTaxRate);
        $extraInvoiceTotal = $extraInvoiceSubtotal + $extraInvoiceTaxAmount;

        $extraInvoiceDueSoon = Invoice::create([
            'user_id' => $user->id,
            'job_id' => $extraJobDelivery->id,
            'client_id' => $extraClientContact->id,
            'invoice_number' => 'INV-2026-004',
            'issued_at' => now()->subDay(),
            'due_at' => now()->addDays(3),
            'subtotal' => [
                'amount_cents' => $extraInvoiceSubtotal,
                'currency' => 'USD',
            ],
            'tax_rate' => $extraInvoiceTaxRate,
            'tax_amount' => [
                'amount_cents' => $extraInvoiceTaxAmount,
                'currency' => 'USD',
            ],
            'total' => [
                'amount_cents' => $extraInvoiceTotal,
                'currency' => 'USD',
            ],
            'status' => InvoiceStatus::DRAFT,
            'paid_at' => null,
        ]);

        foreach ($extraInvoiceItems as $item) {
            InvoiceItem::create([
                'invoice_id' => $extraInvoiceDueSoon->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'amount' => (int) ($item['quantity'] * $item['unit_price']),
            ]);
        }

        $extraInvoiceOverdue = Invoice::create([
            'user_id' => $user->id,
            'job_id' => $extraJobRevision->id,
            'client_id' => $extraClientContact->id,
            'invoice_number' => 'INV-2026-005',
            'issued_at' => now()->subDays(14),
            'due_at' => now()->subDays(2),
            'subtotal' => [
                'amount_cents' => 95000,
                'currency' => 'USD',
            ],
            'tax_rate' => 0.0000,
            'tax_amount' => [
                'amount_cents' => 0,
                'currency' => 'USD',
            ],
            'total' => [
                'amount_cents' => 95000,
                'currency' => 'USD',
            ],
            'status' => InvoiceStatus::SENT,
            'paid_at' => null,
        ]);

        InvoiceItem::create([
            'invoice_id' => $extraInvoiceOverdue->id,
            'description' => 'Revision Session',
            'quantity' => 1,
            'unit_price' => 95000,
            'amount' => 95000,
        ]);

        UsageRight::create([
            'usable_type' => Job::class,
            'usable_id' => $extraJobRevision->id,
            'type' => 'non_broadcast',
            'media_types' => ['digital', 'social_media'],
            'geographic_scope' => 'national',
            'duration_type' => 'fixed',
            'duration_months' => 12,
            'start_date' => now(),
            'expiration_date' => now()->addDays(10),
            'exclusivity' => false,
            'exclusivity_category' => null,
            'ai_rights_granted' => false,
        ]);

        /** @var ActivityService $activityService */
        $activityService = app(ActivityService::class);
        $activityService->syncForUser($user->fresh());

        // Keep mixed action states for UI/demo coverage while staying trigger-valid.
        $snoozedExample = Activity::query()
            ->where('user_id', $user->id)
            ->where('targetable_type', Audition::class)
            ->where('targetable_id', $extraAudition->id)
            ->where('trigger', 'audition_response_due')
            ->whereNull('action')
            ->first();
        if ($snoozedExample) {
            $snoozedExample->update([
                'action' => 'snoozed',
                'snoozed_until' => now()->addDays(3),
            ]);
        }

        $archivedExample = Activity::query()
            ->where('user_id', $user->id)
            ->where('targetable_type', Job::class)
            ->where('targetable_id', $extraJobDelivery->id)
            ->where('trigger', 'job_delivery_due')
            ->whereNull('action')
            ->first();
        if ($archivedExample) {
            $archivedExample->update([
                'action' => 'archived',
                'snoozed_until' => null,
            ]);
        }

        $activities = Activity::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at')
            ->get();

        $this->command->info('Created '.$activities->count().' activities');

        return $activities;
    }

    private function createNotes(User $user): void
    {
        $this->command->info('Creating notes...');

        // Load full user-owned datasets so late-phase seed records also receive notes.
        $auditions = Audition::query()
            ->where('user_id', $user->id)
            ->get();
        $jobs = Job::query()
            ->where('user_id', $user->id)
            ->get();
        $contacts = Contact::query()
            ->where('user_id', $user->id)
            ->get();
        $invoices = Invoice::query()
            ->where('user_id', $user->id)
            ->get();
        $platforms = Platform::query()
            ->where('user_id', $user->id)
            ->get();
        $usageRights = UsageRight::query()
            ->whereHasMorph('usable', [Audition::class, Job::class], fn ($query) => $query->where('user_id', $user->id))
            ->get();
        $activities = Activity::query()
            ->where('user_id', $user->id)
            ->get();
        $expenseDefinitions = ExpenseDefinition::query()
            ->where('user_id', $user->id)
            ->get();
        $expenses = Expense::query()
            ->where('user_id', $user->id)
            ->get();

        $activeCount = 0;
        $deletedCount = 0;

        // Notes for ALL auditions
        foreach ($auditions as $audition) {
            // Active note
            $audition->notes()->create([
                'user_id' => $user->id,
                'content' => 'Follow up on this audition next week. Client seemed very interested.',
            ]);
            $activeCount++;

            // Deleted note
            $deletedNote = $audition->notes()->create([
                'user_id' => $user->id,
                'content' => 'This note was deleted and should not appear in queries.',
            ]);
            $deletedNote->delete();
            $deletedCount++;
        }

        // Notes for ALL jobs
        foreach ($jobs as $job) {
            // Active note
            $job->notes()->create([
                'user_id' => $user->id,
                'content' => 'Client prefers morning recording sessions. Use natural, conversational tone.',
            ]);
            $activeCount++;

            // Deleted note
            $deletedNote = $job->notes()->create([
                'user_id' => $user->id,
                'content' => 'Old job note that was removed.',
            ]);
            $deletedNote->delete();
            $deletedCount++;
        }

        // Notes for ALL contacts
        foreach ($contacts as $contact) {
            // Active note
            $contact->notes()->create([
                'user_id' => $user->id,
                'content' => 'Great to work with. Very responsive and clear communication.',
            ]);
            $activeCount++;

            // Deleted note
            $deletedNote = $contact->notes()->create([
                'user_id' => $user->id,
                'content' => 'Outdated contact information - removed.',
            ]);
            $deletedNote->delete();
            $deletedCount++;
        }

        // Notes for ALL invoices
        foreach ($invoices as $invoice) {
            // Active note
            $invoice->notes()->create([
                'user_id' => $user->id,
                'content' => 'Payment pending. Sent reminder email on '.now()->subDays(2)->format('Y-m-d').'.',
            ]);
            $activeCount++;

            // Deleted note
            $deletedNote = $invoice->notes()->create([
                'user_id' => $user->id,
                'content' => 'Outdated payment information - removed.',
            ]);
            $deletedNote->delete();
            $deletedCount++;
        }

        // Notes for ALL platforms
        foreach ($platforms as $platform) {
            // Active note
            $platform->notes()->create([
                'user_id' => $user->id,
                'content' => 'Account performing well. Response rate is approximately 35%.',
            ]);
            $activeCount++;

            // Deleted note
            $deletedNote = $platform->notes()->create([
                'user_id' => $user->id,
                'content' => 'Old platform credentials - removed.',
            ]);
            $deletedNote->delete();
            $deletedCount++;
        }

        // Notes for ALL usage rights
        foreach ($usageRights as $usageRight) {
            // Active note
            $usageRight->notes()->create([
                'user_id' => $user->id,
                'content' => 'Set renewal reminder for 60 days before expiration. Client wants to extend.',
            ]);
            $activeCount++;

            // Deleted note
            $deletedNote = $usageRight->notes()->create([
                'user_id' => $user->id,
                'content' => 'Previous renewal discussion notes - archived.',
            ]);
            $deletedNote->delete();
            $deletedCount++;
        }

        // Notes for ALL activities
        foreach ($activities as $activity) {
            // Active note
            $activity->notes()->create([
                'user_id' => $user->id,
                'content' => 'Discussed with client. Will follow up after the weekend.',
            ]);
            $activeCount++;

            // Deleted note
            $deletedNote = $activity->notes()->create([
                'user_id' => $user->id,
                'content' => 'Old action item note - completed.',
            ]);
            $deletedNote->delete();
            $deletedCount++;
        }

        // Notes for ALL expense definitions
        foreach ($expenseDefinitions as $expenseDefinition) {
            // Active note
            $expenseDefinition->notes()->create([
                'user_id' => $user->id,
                'content' => 'Review pricing annually. May qualify for team discount.',
            ]);
            $activeCount++;

            // Deleted note
            $deletedNote = $expenseDefinition->notes()->create([
                'user_id' => $user->id,
                'content' => 'Old pricing structure notes - outdated.',
            ]);
            $deletedNote->delete();
            $deletedCount++;
        }

        // Notes for ALL expenses
        foreach ($expenses as $expense) {
            // Active note
            $expense->notes()->create([
                'user_id' => $user->id,
                'content' => 'Claimed as business expense for tax deduction.',
            ]);
            $activeCount++;

            // Deleted note
            $deletedNote = $expense->notes()->create([
                'user_id' => $user->id,
                'content' => 'Receipt attachment note - removed after filing.',
            ]);
            $deletedNote->delete();
            $deletedCount++;
        }

        $this->command->info("Created {$activeCount} active notes across all entity types");
        $this->command->info("Created {$deletedCount} soft-deleted notes for testing");
    }

    private function createHistoricalRevenueData(User $user, \Illuminate\Support\Collection $clients, \Illuminate\Support\Collection $contacts): void
    {
        $this->command->info('Creating historical revenue data (12 months)...');

        $clientContacts = $contacts->filter(fn ($c) => $c->contactable_type === Client::class);
        if ($clientContacts->isEmpty()) {
            return;
        }

        $endDate = now()->subMonths(3)->endOfMonth();
        $startDate = $endDate->copy()->subMonths(11)->startOfMonth();
        $months = \Carbon\CarbonPeriod::create($startDate, '1 month', $endDate);

        $historicalJobsCount = 0;
        $historicalInvoicesCount = 0;

        foreach ($months as $date) {
            // 1 job per month
            $jobsInMonth = 1;

            for ($i = 0; $i < $jobsInMonth; $i++) {
                // Randomize date within the month
                $jobDate = $date->copy()->addDays(rand(1, 28));

                // Random Currency (Weighted: 85% USD, 15% CAD)
                $currencyRand = rand(1, 100);
                $currency = match (true) {
                    $currencyRand <= 85 => 'USD',
                    default => 'CAD'
                };

                $rate = rand(12000, 35000); // $120 - $350

                // Create Job
                $job = Job::create([
                    'user_id' => $user->id,
                    'client_id' => $clientContacts->random()->id,
                    'project_title' => 'Historical Project '.\Illuminate\Support\Str::random(5),
                    'category' => 'commercial',
                    'status' => JobStatus::COMPLETED,
                    'contracted_rate' => [
                        'amount_cents' => $rate,
                        'currency' => $currency,
                    ],
                    'rate_type' => 'flat',
                    'session_date' => $jobDate,
                    'delivery_deadline' => $jobDate->copy()->addDays(5),
                    'delivered_at' => $jobDate->copy()->addDays(3),
                    'word_count' => rand(100, 500),
                ]);

                // Create PAID Invoice for this job
                $invoice = Invoice::create([
                    'user_id' => $user->id,
                    'job_id' => $job->id,
                    'client_id' => $job->client_id,
                    'invoice_number' => 'INV-HIST-'.$jobDate->format('ymd').'-'.$i,
                    'status' => InvoiceStatus::PAID,
                    'issued_at' => $jobDate->copy()->addDays(3),
                    'due_at' => $jobDate->copy()->addDays(33),
                    'paid_at' => $jobDate->copy()->addDays(rand(4, 20)), // Paid 4-20 days after issue
                    'subtotal' => [
                        'amount_cents' => $rate,
                        'currency' => $currency,
                    ],
                    'tax_rate' => 0,
                    'tax_amount' => null,
                    'total' => [
                        'amount_cents' => $rate,
                        'currency' => $currency,
                    ],
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => 'Voice Over Services',
                    'quantity' => 1,
                    'unit_price' => $rate,
                    'amount' => $rate,
                ]);

                $historicalJobsCount++;
                $historicalInvoicesCount++;
            }
        }

        $this->command->info("Created {$historicalJobsCount} historical jobs and {$historicalInvoicesCount} historical invoices.");
    }
}
