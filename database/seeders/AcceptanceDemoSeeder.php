<?php

namespace Database\Seeders;

use App\Actions\Orders\ReconcileRefund;
use App\Actions\Placements\GrantPaidOrderPlacementCredits;
use App\Enums\AdvertisingPackage;
use App\Enums\ApplicationMode;
use App\Enums\ApplicationStatus;
use App\Enums\CategoryType;
use App\Enums\CompanyStatus;
use App\Enums\ImportEmployerIdentityType;
use App\Enums\ImportExecutionOrigin;
use App\Enums\ImportFormat;
use App\Enums\ImportLogLevel;
use App\Enums\ImportMissingVacancyPolicy;
use App\Enums\ImportPublicationPolicy;
use App\Enums\ImportScheduleFrequency;
use App\Enums\ImportStatus;
use App\Enums\ImportTransport;
use App\Enums\ImportType;
use App\Enums\OrderOperationStatus;
use App\Enums\OrderOperationType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SalaryBasis;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Application;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Company;
use App\Models\Import;
use App\Models\ImportLog;
use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use App\Models\ImportSource;
use App\Models\ImportSourceEmployer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderOperation;
use App\Models\OutboundApplyClick;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyDetailView;
use App\Payments\PaymentOperationResult;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class AcceptanceDemoSeeder extends Seeder
{
    public const PASSWORD = 'acceptance-demo-password';

    private const EMAIL_DOMAIN = 'smv-acceptance.test';

    private const FIXTURE_PREFIX = 'acceptance-';

    public function run(): void
    {
        $this->guardEnvironment();
        $this->seedRoles();
        $this->call(CategorySeeder::class);
        $this->call(PackageSeeder::class);

        $users = $this->seedUsers();

        if (! BlogPost::query()->exists()) {
            $this->call(BlogContentSeeder::class);
        }

        DB::transaction(function () use ($users): void {
            $companies = $this->seedCompanies($users);
            $sources = $this->seedImportSources($users['admin'], $companies);
            $runs = $this->seedImportHistory($sources, $users['admin']);
            $vacancies = $this->seedVacancies($companies, $sources, $runs);

            $this->seedAccountHistory($users['candidate'], $companies, $vacancies);
            $this->seedOutboundClicks($vacancies);
            $this->seedVacancyDetailViews($vacancies);
            $this->seedOrders($users['employer'], $companies['complete'], $vacancies, $users['admin']);
        });
    }

    public static function password(): string
    {
        return (string) env('ACCEPTANCE_DEMO_PASSWORD', self::PASSWORD);
    }

    /** @return array<string, User> */
    private function seedUsers(): array
    {
        $definitions = [
            'candidate' => ['name' => 'Noor Kandidaat', 'role' => 'candidate'],
            'employer' => ['name' => 'Milan Werkgever', 'role' => 'employer'],
            'editor' => ['name' => 'Eva Redactie', 'role' => 'editor'],
            'admin' => ['name' => 'Sam Beheer', 'role' => 'admin'],
            'super_admin' => ['name' => 'Alex Superbeheer', 'role' => 'super-admin'],
        ];

        $users = [];

        foreach ($definitions as $key => $definition) {
            $user = User::query()->firstOrNew([
                'email' => "{$key}@".self::EMAIL_DOMAIN,
            ]);
            $user->fill([
                'name' => $definition['name'],
                'role' => $definition['role'],
                'password' => Hash::make(self::password()),
                'email_verified_at' => now(),
            ]);
            $user->email = "{$key}@".self::EMAIL_DOMAIN;
            $user->save();
            $user->syncRoles([$definition['role']]);
            $users[$key] = $user;
        }

        return $users;
    }

    /** @param array<string, User> $users */
    private function seedCompanies(array $users): array
    {
        $definitions = [
            'complete' => [
                'name' => 'Noorderlicht Sales Studio',
                'user_id' => $users['employer']->id,
                'tagline' => 'Groei met aandacht voor mens en markt.',
                'description' => 'Een fictieve salesstudio voor acceptatie en demo’s met een volledig publiek bedrijfsprofiel.',
                'email' => 'contact@noorderlicht-sales.'.self::EMAIL_DOMAIN,
                'website' => 'https://noorderlicht-sales.'.self::EMAIL_DOMAIN,
                'location' => 'Utrecht',
                'logo' => 'images/smv-logo.svg',
                'status' => CompanyStatus::Active,
                'is_featured' => true,
                'commercial_priority' => 5,
            ],
            'minimal' => [
                'name' => 'Kompas Growth Lab',
                'user_id' => $users['employer']->id,
                'tagline' => 'Een compact fictief team.',
                'description' => 'Een bewust minimaal bedrijfsprofiel voor het testen van ontbrekende profielvelden.',
                'email' => 'hello@kompas-growth.'.self::EMAIL_DOMAIN,
                'location' => 'Antwerpen',
                'status' => CompanyStatus::Active,
                'is_featured' => false,
                'commercial_priority' => -5,
            ],
            'imported' => [
                'name' => 'Rivierenland Commerce Group',
                'tagline' => 'Fictieve werkgever voor feedscenario’s.',
                'description' => 'Een fictieve onderneming die aan meerdere veilige acceptatie-imports is gekoppeld.',
                'email' => 'feed@rivierenland-commerce.'.self::EMAIL_DOMAIN,
                'website' => 'https://rivierenland-commerce.'.self::EMAIL_DOMAIN,
                'location' => 'Breda',
                'status' => CompanyStatus::Active,
                'is_featured' => false,
                'commercial_priority' => 0,
            ],
            'empty' => [
                'name' => 'Legehaven Interim',
                'tagline' => 'Fictief bedrijf zonder actuele publieke vacatures.',
                'description' => 'Een fictief bedrijf dat bewust geen actuele publieke vacatures heeft.',
                'email' => 'team@legehaven-interim.'.self::EMAIL_DOMAIN,
                'location' => 'Rotterdam',
                'status' => CompanyStatus::Active,
                'is_featured' => false,
                'commercial_priority' => 0,
            ],
        ];

        $companies = [];

        foreach ($definitions as $key => $definition) {
            $slug = self::FIXTURE_PREFIX."company-{$key}";
            $company = Company::withTrashed()->firstOrNew(['slug' => $slug]);
            $company->fill([...$definition, 'slug' => $slug]);
            $company->save();

            if ($company->trashed()) {
                $company->restore();
            }

            $companies[$key] = $company;
        }

        return $companies;
    }

    /** @param array<string, Company> $companies */
    private function seedImportSources(User $admin, array $companies): array
    {
        $sources = [
            'trusted' => $this->upsertSource('trusted', [
                'company_id' => $companies['imported']->id,
                'name' => 'Acceptance — trusted fixture feed',
                'transport' => ImportTransport::Http,
                'format' => ImportFormat::Xml,
                'endpoint_url' => 'https://acceptance.invalid/feeds/trusted.xml',
                'is_active' => true,
                'publication_policy' => ImportPublicationPolicy::AutoPublish,
                'missing_vacancy_policy' => ImportMissingVacancyPolicy::AutoOffline,
                'automatic_import_enabled' => true,
                'schedule_frequency' => ImportScheduleFrequency::Daily,
                'approved_at' => now()->subDays(4),
                'approved_by' => $admin->id,
                'configuration' => $this->sourceConfiguration(),
                'selection_rules' => [],
            ]),
            'review' => $this->upsertSource('review', [
                'company_id' => $companies['imported']->id,
                'name' => 'Acceptance — review-first fixture feed',
                'transport' => ImportTransport::Upload,
                'format' => ImportFormat::Xml,
                'endpoint_url' => null,
                'is_active' => true,
                'publication_policy' => ImportPublicationPolicy::ReviewRequired,
                'missing_vacancy_policy' => ImportMissingVacancyPolicy::ReviewRequired,
                'automatic_import_enabled' => false,
                'configuration' => $this->sourceConfiguration(),
                'selection_rules' => [],
            ]),
            'manual' => $this->upsertSource('manual', [
                'company_id' => $companies['empty']->id,
                'name' => 'Acceptance — manual upload only',
                'transport' => ImportTransport::Upload,
                'format' => ImportFormat::Json,
                'endpoint_url' => null,
                'is_active' => true,
                'publication_policy' => ImportPublicationPolicy::ReviewRequired,
                'missing_vacancy_policy' => ImportMissingVacancyPolicy::ReviewRequired,
                'automatic_import_enabled' => false,
                'configuration' => ['acceptance_fixture' => true],
                'selection_rules' => [],
            ]),
            'monitoring' => $this->upsertSource('monitoring', [
                'company_id' => $companies['minimal']->id,
                'name' => 'Acceptance — monitoring history fixture',
                'transport' => ImportTransport::Http,
                'format' => ImportFormat::Xml,
                'endpoint_url' => 'https://acceptance.invalid/feeds/monitoring.xml',
                'is_active' => true,
                'publication_policy' => ImportPublicationPolicy::ReviewRequired,
                'missing_vacancy_policy' => ImportMissingVacancyPolicy::ReviewRequired,
                'automatic_import_enabled' => true,
                'schedule_frequency' => ImportScheduleFrequency::Hourly,
                'approved_at' => now()->subDays(3),
                'approved_by' => $admin->id,
                'configuration' => ['acceptance_fixture' => true],
                'selection_rules' => [],
            ]),
        ];

        foreach ($sources as $source) {
            $mapping = $this->upsertMapping($source);
            $source->forceFill(['default_mapping' => ['mapping_id' => $mapping->id]])->save();
        }

        $this->upsertEmployerMapping($sources['trusted'], $companies['imported'], 'trusted-employer-001', $admin);
        $this->upsertEmployerMapping($sources['review'], $companies['imported'], 'review-employer-001', $admin);

        return $sources;
    }

    /** @param array<string, mixed> $attributes */
    private function upsertSource(string $key, array $attributes): ImportSource
    {
        $slug = self::FIXTURE_PREFIX."source-{$key}";
        $source = ImportSource::withTrashed()->firstOrNew(['slug' => $slug]);
        $source->fill([...$attributes, 'slug' => $slug, 'type' => ImportType::xml]);
        $source->save();

        if ($source->trashed()) {
            $source->restore();
        }

        return $source;
    }

    private function sourceConfiguration(): array
    {
        return [
            'acceptance_fixture' => true,
            'employer_resolution' => 'source_scoped',
            'snapshot_reconciliation' => [
                'enabled' => true,
                'allow_empty' => false,
            ],
        ];
    }

    private function upsertMapping(ImportSource $source): ImportMapping
    {
        $mapping = ImportMapping::query()->firstOrNew([
            'import_source_id' => $source->id,
            'name' => 'Acceptance fixture mapping',
        ]);
        $mapping->fill(['is_active' => true, 'is_default' => true]);
        $mapping->save();

        $fields = [
            'source_reference' => ['source_reference'],
            'employer.external_id' => ['employer.external_id'],
            'employer.name' => ['employer.name'],
            'employer.source_url' => ['employer.source_url'],
            'vacancy.title' => ['title'],
            'vacancy.description' => ['description'],
            'vacancy.location' => ['location'],
            'vacancy.application_mode' => ['application_mode'],
            'vacancy.application_url' => ['application_url'],
            'vacancy.application_email' => ['application_email'],
            'vacancy.source_requisition_id' => ['requisitionid'],
            'vacancy.source_salary_text' => ['salary'],
            'vacancy.source_product_code' => ['productcode'],
        ];

        foreach ($fields as $destination => $paths) {
            ImportMappingField::query()->updateOrCreate(
                ['import_mapping_id' => $mapping->id, 'destination_key' => $destination],
                ['operation' => 'direct', 'source_paths' => $paths, 'position' => array_search($destination, array_keys($fields), true)],
            );
        }

        return $mapping->fresh('fields');
    }

    private function upsertEmployerMapping(ImportSource $source, Company $company, string $externalId, User $admin): void
    {
        $mapping = ImportSourceEmployer::query()->firstOrNew([
            'import_source_id' => $source->id,
            'external_employer_id' => $externalId,
        ]);
        $mapping->fill([
            'company_id' => $company->id,
            'identity_type' => ImportEmployerIdentityType::ProviderId,
            'external_employer_id' => $externalId,
            'source_name' => $company->name,
            'source_url' => "https://{$source->slug}.invalid/employers/{$externalId}",
            'is_active' => true,
        ]);
        $mapping->save();
        $mapping->forceFill(['reviewed_by' => $admin->id, 'reviewed_at' => now()])->save();
    }

    /** @param array<string, ImportSource> $sources */
    private function seedImportHistory(array $sources, User $admin): array
    {
        $runs = [];
        $runs['trusted_previous'] = $this->upsertImport($sources['trusted'], 'trusted-previous', [
            'status' => ImportStatus::Completed,
            'execution_origin' => ImportExecutionOrigin::Scheduled,
            'total_rows' => 10,
            'imported_rows' => 10,
            'updated_rows' => 0,
            'mapping' => ['snapshot' => ['state' => 'verified_complete', 'reconciliation_enabled' => true]],
            'started_at' => now()->subDays(3)->setTime(8, 0),
            'finished_at' => now()->subDays(3)->setTime(8, 2),
        ], ImportLogLevel::Info, 'Acceptance fixture previous verified run.');
        $runs['trusted_latest'] = $this->upsertImport($sources['trusted'], 'trusted-latest', [
            'status' => ImportStatus::Completed,
            'execution_origin' => ImportExecutionOrigin::Scheduled,
            'total_rows' => 4,
            'imported_rows' => 1,
            'updated_rows' => 3,
            'missing_rows' => 1,
            'restored_rows' => 1,
            'mapping' => ['snapshot' => ['state' => 'verified_complete', 'reconciliation_enabled' => true]],
            'started_at' => now()->subDay()->setTime(8, 0),
            'finished_at' => now()->subDay()->setTime(8, 2),
        ], ImportLogLevel::Info, 'Acceptance fixture healthy verified run.');
        $runs['review_verified'] = $this->upsertImport($sources['review'], 'review-verified', [
            'status' => ImportStatus::Completed,
            'execution_origin' => ImportExecutionOrigin::Manual,
            'total_rows' => 3,
            'imported_rows' => 3,
            'mapping' => ['snapshot' => ['state' => 'verified_complete', 'reconciliation_enabled' => true]],
            'started_at' => now()->subDays(2)->setTime(12, 0),
            'finished_at' => now()->subDays(2)->setTime(12, 2),
        ], ImportLogLevel::Info, 'Acceptance fixture previous review-source verification.');
        $runs['review_incomplete'] = $this->upsertImport($sources['review'], 'review-incomplete', [
            'status' => ImportStatus::Incomplete,
            'execution_origin' => ImportExecutionOrigin::Manual,
            'total_rows' => 2,
            'skipped_rows' => 1,
            'failed_rows' => 1,
            'mapping' => ['snapshot' => ['state' => 'snapshot_record_unresolved', 'reconciliation_enabled' => true]],
            'started_at' => now()->subHours(8),
            'finished_at' => now()->subHours(8)->addMinutes(1),
        ], ImportLogLevel::Warning, 'Acceptance fixture run requires review.');
        $runs['monitoring_failed'] = $this->upsertImport($sources['monitoring'], 'monitoring-failed', [
            'status' => ImportStatus::Failed,
            'execution_origin' => ImportExecutionOrigin::Scheduled,
            'total_rows' => 0,
            'failed_rows' => 1,
            'mapping' => ['snapshot' => ['state' => 'read_or_parse_failed']],
            'started_at' => now()->subHours(4),
            'finished_at' => now()->subHours(4)->addMinute(),
        ], ImportLogLevel::Error, 'Acceptance fixture failed run.');
        $runs['monitoring_incomplete'] = $this->upsertImport($sources['monitoring'], 'monitoring-incomplete', [
            'status' => ImportStatus::Incomplete,
            'execution_origin' => ImportExecutionOrigin::Scheduled,
            'total_rows' => 3,
            'failed_rows' => 1,
            'mapping' => ['snapshot' => ['state' => 'snapshot_mapping_invalid']],
            'started_at' => now()->subHours(3),
            'finished_at' => now()->subHours(3)->addMinute(),
        ], ImportLogLevel::Warning, 'Acceptance fixture incomplete run.');
        $runs['monitoring_skipped'] = $this->upsertImport($sources['monitoring'], 'monitoring-skipped', [
            'status' => ImportStatus::Skipped,
            'execution_origin' => ImportExecutionOrigin::Scheduled,
            'mapping' => ['snapshot' => ['state' => 'concurrency_rejected']],
            'started_at' => now()->subHour(),
            'finished_at' => now()->subHour(),
        ], ImportLogLevel::Warning, 'Acceptance fixture lock-contention run.');

        $sources['trusted']->forceFill(['last_imported_at' => $runs['trusted_latest']->finished_at])->save();
        $sources['review']->forceFill(['last_imported_at' => $runs['review_incomplete']->finished_at])->save();
        $sources['monitoring']->forceFill(['last_imported_at' => $runs['monitoring_skipped']->finished_at])->save();

        return $runs;
    }

    /** @param array<string, mixed> $attributes */
    private function upsertImport(ImportSource $source, string $key, array $attributes, ImportLogLevel $level, string $message): Import
    {
        $filename = self::FIXTURE_PREFIX."{$key}.xml";
        $import = Import::withTrashed()->firstOrNew([
            'import_source_id' => $source->id,
            'filename' => $filename,
        ]);
        $import->fill([
            'type' => ImportType::xml,
            'filename' => $filename,
            'status' => ImportStatus::Pending,
            'execution_origin' => ImportExecutionOrigin::Manual,
            'total_rows' => 0,
            'imported_rows' => 0,
            'updated_rows' => 0,
            'skipped_rows' => 0,
            'failed_rows' => 0,
            'missing_rows' => 0,
            'restored_rows' => 0,
            'mapping' => [],
            ...$attributes,
        ]);
        $import->save();

        if ($import->trashed()) {
            $import->restore();
        }

        $import->importLogs()->delete();
        ImportLog::create([
            'import_id' => $import->id,
            'level' => $level,
            'message' => $message,
            'context' => ['fixture' => true, 'source' => $source->slug],
        ]);

        return $import->fresh();
    }

    /** @param array<string, Company> $companies
     * @param  array<string, ImportSource>  $sources
     * @param  array<string, Import>  $runs
     */
    private function seedVacancies(array $companies, array $sources, array $runs): array
    {
        $categories = $this->categoryIds();
        $vacancies = [];

        $vacancies['internal'] = $this->upsertVacancy('manual-internal', [
            'company_id' => $companies['complete']->id,
            'title' => 'Accountmanager interne sollicitaties',
            'slug' => self::FIXTURE_PREFIX.'vacancy-manual-internal',
            'location' => 'Utrecht',
            'application_mode' => ApplicationMode::Internal,
            'status' => VacancyStatus::Active,
            'published_at' => now()->subDays(8),
            'deadline_at' => now()->addDays(30),
            'expires_at' => now()->addDays(60),
            'salary_min' => 3800,
            'salary_max' => 5200,
            'salary_currency' => 'EUR',
            'salary_period' => 'month',
            'salary_basis' => SalaryBasis::GrossFullTimeEquivalent,
            'is_featured' => true,
            'placement_package' => AdvertisingPackage::Superior,
            'commercial_priority_adjustment' => 5,
            'description' => 'Een stabiele fictieve vacature voor de interne sollicitatieflow en kandidaatgeschiedenis.',
            'source' => VacancySource::Manual,
        ], $categories['internal'], ['acceptance', 'internal']);

        $vacancies['external'] = $this->upsertVacancy('manual-external', [
            'company_id' => $companies['complete']->id,
            'title' => 'Business developer externe sollicitatielink',
            'slug' => self::FIXTURE_PREFIX.'vacancy-manual-external',
            'location' => 'Amsterdam',
            'application_mode' => ApplicationMode::External,
            'application_url' => 'https://careers.smv-acceptance.test/apply/external?utm_source=smv&utm_medium=acceptance',
            'status' => VacancyStatus::Active,
            'published_at' => now()->subDays(6),
            'deadline_at' => now()->addDays(20),
            'expires_at' => now()->addDays(55),
            'rate_min' => 85,
            'rate_max' => 110,
            'rate_currency' => 'EUR',
            'rate_period' => 'hour',
            'placement_package' => AdvertisingPackage::Standard,
            'commercial_priority_adjustment' => 0,
            'description' => 'Een fictieve vacature met een gecontroleerde externe sollicitatielink voor click tracking.',
            'source' => VacancySource::Manual,
        ], $categories['external'], ['acceptance', 'external']);

        $vacancies['email'] = $this->upsertVacancy('manual-email', [
            'company_id' => $companies['minimal']->id,
            'title' => 'Marketingcoördinator per e-mail',
            'slug' => self::FIXTURE_PREFIX.'vacancy-manual-email',
            'location' => 'Antwerpen',
            'application_mode' => ApplicationMode::Email,
            'application_email' => 'apply@kompas-growth.'.self::EMAIL_DOMAIN,
            'status' => VacancyStatus::Pending,
            'deadline_at' => now()->addDays(25),
            'commercial_priority_adjustment' => 10,
            'description' => 'Een fictieve vacature met e-mailbestemming voor moderatie en destination-tests.',
            'source' => VacancySource::Manual,
        ], $categories['email'], ['acceptance', 'email']);

        $vacancies['draft'] = $this->upsertVacancy('manual-draft', [
            'company_id' => $companies['empty']->id,
            'title' => 'Conceptvacature zonder publicatie',
            'slug' => self::FIXTURE_PREFIX.'vacancy-manual-draft',
            'location' => 'Rotterdam',
            'application_mode' => ApplicationMode::Internal,
            'status' => VacancyStatus::Draft,
            'commercial_priority_adjustment' => -10,
            'description' => 'Een fictieve conceptvacature voor het redactionele publicatieproces.',
            'source' => VacancySource::Manual,
        ], $categories['draft'], ['acceptance', 'draft']);

        $vacancies['archived'] = $this->upsertVacancy('manual-archived', [
            'company_id' => $companies['complete']->id,
            'title' => 'Gearchiveerde campagnevacature',
            'slug' => self::FIXTURE_PREFIX.'vacancy-manual-archived',
            'location' => 'Den Haag',
            'application_mode' => ApplicationMode::Internal,
            'status' => VacancyStatus::Archived,
            'description' => 'Een fictieve gearchiveerde vacature voor lifecycle- en staffiltering.',
            'source' => VacancySource::Manual,
        ], $categories['archived'], ['acceptance', 'archived']);

        $vacancies['expired'] = $this->upsertVacancy('manual-expired', [
            'company_id' => $companies['complete']->id,
            'title' => 'Verlopen salesfunctie',
            'slug' => self::FIXTURE_PREFIX.'vacancy-manual-expired',
            'location' => 'Eindhoven',
            'application_mode' => ApplicationMode::External,
            'application_url' => 'https://careers.smv-acceptance.test/apply/expired',
            'status' => VacancyStatus::Expired,
            'published_at' => now()->subDays(40),
            'deadline_at' => now()->subDays(5),
            'expires_at' => now()->subDays(5),
            'description' => 'Een fictieve verlopen vacature voor publieke lifecycle-regressies.',
            'source' => VacancySource::Manual,
        ], $categories['expired'], ['acceptance', 'expired']);

        $vacancies['filled'] = $this->upsertVacancy('manual-filled', [
            'company_id' => $companies['complete']->id,
            'title' => 'Vervulde accountmanagementrol',
            'slug' => self::FIXTURE_PREFIX.'vacancy-manual-filled',
            'location' => 'Breda',
            'application_mode' => ApplicationMode::Internal,
            'status' => VacancyStatus::Active,
            'is_filled' => true,
            'published_at' => now()->subDays(12),
            'deadline_at' => now()->subDays(1),
            'description' => 'Een fictieve vervulde vacature die niet meer publiek mag verschijnen.',
            'source' => VacancySource::Manual,
        ], $categories['filled'], ['acceptance', 'filled']);

        $vacancies['import_active'] = $this->upsertImportedVacancy('import-active', $companies['imported'], $sources['trusted'], $runs['trusted_latest'], [
            'title' => 'Imported actieve sales consultant',
            'slug' => self::FIXTURE_PREFIX.'vacancy-import-active',
            'status' => VacancyStatus::Active,
            'application_mode' => ApplicationMode::External,
            'application_url' => 'https://careers.smv-acceptance.test/import/active',
            'last_seen_at' => now()->subDay(),
            'commercial_priority_adjustment' => 6,
        ], $categories['imported']);

        $vacancies['import_review'] = $this->upsertImportedVacancy('import-review', $companies['imported'], $sources['review'], $runs['review_verified'], [
            'title' => 'Imported vacature wacht op review',
            'slug' => self::FIXTURE_PREFIX.'vacancy-import-review',
            'status' => VacancyStatus::Pending,
            'application_mode' => ApplicationMode::Email,
            'application_email' => 'review@rivierenland-commerce.'.self::EMAIL_DOMAIN,
            'last_seen_at' => now()->subHours(8),
        ], $categories['imported']);

        $vacancies['import_missing'] = $this->upsertImportedVacancy('import-missing', $companies['imported'], $sources['review'], $runs['review_verified'], [
            'title' => 'Imported vacature ontbreekt voor review',
            'slug' => self::FIXTURE_PREFIX.'vacancy-import-missing',
            'status' => VacancyStatus::Active,
            'application_mode' => ApplicationMode::External,
            'application_url' => 'https://careers.smv-acceptance.test/import/missing',
            'missing_since' => now()->subDays(2),
            'last_seen_at' => now()->subDays(2),
        ], $categories['imported']);

        $vacancies['import_offline'] = $this->upsertImportedVacancy('import-offline', $companies['imported'], $sources['trusted'], $runs['trusted_previous'], [
            'title' => 'Imported vacature automatisch offline',
            'slug' => self::FIXTURE_PREFIX.'vacancy-import-offline',
            'status' => VacancyStatus::Archived,
            'application_mode' => ApplicationMode::External,
            'application_url' => 'https://careers.smv-acceptance.test/import/offline',
            'missing_since' => now()->subDays(5),
            'source_offlined_at' => now()->subDays(4),
            'last_seen_at' => now()->subDays(5),
        ], $categories['imported']);

        $vacancies['import_restored'] = $this->upsertImportedVacancy('import-restored', $companies['imported'], $sources['trusted'], $runs['trusted_latest'], [
            'title' => 'Imported vacature opnieuw verschenen',
            'slug' => self::FIXTURE_PREFIX.'vacancy-import-restored',
            'status' => VacancyStatus::Active,
            'application_mode' => ApplicationMode::External,
            'application_url' => 'https://careers.smv-acceptance.test/import/restored',
            'missing_since' => null,
            'source_offlined_at' => null,
            'last_seen_at' => now()->subDay(),
        ], $categories['imported']);

        $vacancies['import_copy'] = $this->upsertImportedVacancy('import-copy', $companies['imported'], $sources['trusted'], $runs['trusted_latest'], [
            'title' => 'Imported vacature voor handmatige kopie',
            'slug' => self::FIXTURE_PREFIX.'vacancy-import-copy',
            'status' => VacancyStatus::Active,
            'application_mode' => ApplicationMode::External,
            'application_url' => 'https://careers.smv-acceptance.test/import/copy',
            'last_seen_at' => now()->subDay(),
        ], $categories['imported']);

        $vacancies['placement_pending'] = $this->upsertVacancy('placement-pending', [
            'company_id' => $companies['complete']->id,
            'title' => 'Betaalde plaatsing wacht op beoordeling',
            'slug' => self::FIXTURE_PREFIX.'vacancy-placement-pending',
            'location' => 'Utrecht',
            'application_mode' => ApplicationMode::Internal,
            'status' => VacancyStatus::Pending,
            'placement_package' => AdvertisingPackage::Superior,
            'description' => 'Een betaalde fictieve Superior-plaatsing die bewust nog niet is gepubliceerd.',
            'source' => VacancySource::Manual,
        ], $categories['internal'], ['acceptance', 'placement']);

        $vacancies['placement_superior_active'] = $this->upsertVacancy('placement-superior-active', [
            'company_id' => $companies['complete']->id,
            'title' => 'Actieve betaalde Superior-plaatsing',
            'slug' => self::FIXTURE_PREFIX.'vacancy-placement-superior-active',
            'location' => 'Utrecht',
            'application_mode' => ApplicationMode::Internal,
            'status' => VacancyStatus::Active,
            'published_at' => now()->subDays(3),
            'deadline_at' => now()->addDays(30),
            'expires_at' => now()->addDays(90),
            'placement_package' => AdvertisingPackage::Superior,
            'description' => 'Een actieve fictieve Superior-plaatsing met betaald pakketvoordeel.',
            'source' => VacancySource::Manual,
        ], $categories['internal'], ['acceptance', 'placement']);

        $vacancies['placement_expired'] = $this->upsertVacancy('placement-expired', [
            'company_id' => $companies['complete']->id,
            'title' => 'Verlopen betaald plaatsingsrecht',
            'slug' => self::FIXTURE_PREFIX.'vacancy-placement-expired',
            'location' => 'Utrecht',
            'application_mode' => ApplicationMode::Internal,
            'status' => VacancyStatus::Active,
            'published_at' => now()->subDays(90),
            'deadline_at' => null,
            'expires_at' => null,
            'placement_package' => AdvertisingPackage::Standard,
            'description' => 'Een fictieve betaalde plaatsing waarvan de plaatsingsduur is verstreken.',
            'source' => VacancySource::Manual,
        ], $categories['internal'], ['acceptance', 'placement']);

        $vacancies['placement_refund_review'] = $this->upsertVacancy('placement-refund-review', [
            'company_id' => $companies['complete']->id,
            'title' => 'Terugbetaalde actieve plaatsing vereist controle',
            'slug' => self::FIXTURE_PREFIX.'vacancy-placement-refund-review',
            'location' => 'Utrecht',
            'application_mode' => ApplicationMode::Internal,
            'status' => VacancyStatus::Active,
            'published_at' => now()->subDays(2),
            'deadline_at' => now()->addDays(30),
            'expires_at' => now()->addDays(90),
            'placement_package' => AdvertisingPackage::Superior,
            'description' => 'Fictieve actieve plaatsing waarvan de financiële terugbetaling is bevestigd en de commerciële afhandeling controle vereist.',
            'source' => VacancySource::Manual,
        ], $categories['internal'], ['acceptance', 'placement', 'refund-review']);

        return $vacancies;
    }

    /** @return array<string, list<int>> */
    private function categoryIds(): array
    {
        return [
            'internal' => $this->categories([
                CategoryType::employment_type->value => 'fulltime',
                CategoryType::workplace->value => 'hybride',
                CategoryType::sector->value => 'saas',
                CategoryType::function_area->value => 'sales',
                CategoryType::experience->value => 'senior',
            ]),
            'external' => $this->categories([
                CategoryType::employment_type->value => 'fulltime',
                CategoryType::workplace->value => 'remote',
                CategoryType::sector->value => 'fintech',
                CategoryType::function_area->value => 'business-development',
                CategoryType::experience->value => 'medior',
            ]),
            'email' => $this->categories([
                CategoryType::employment_type->value => 'parttime',
                CategoryType::workplace->value => 'op-locatie',
                CategoryType::sector->value => 'retail',
                CategoryType::function_area->value => 'marketing',
                CategoryType::experience->value => 'medior',
            ]),
            'draft' => $this->categories([
                CategoryType::employment_type->value => 'fulltime',
                CategoryType::workplace->value => 'remote',
                CategoryType::sector->value => 'it',
                CategoryType::function_area->value => 'marketing',
                CategoryType::experience->value => 'starter',
            ]),
            'archived' => $this->categories([
                CategoryType::employment_type->value => 'parttime',
                CategoryType::workplace->value => 'op-locatie',
                CategoryType::sector->value => 'horeca',
                CategoryType::function_area->value => 'communicatie',
                CategoryType::experience->value => 'junior',
            ]),
            'expired' => $this->categories([
                CategoryType::employment_type->value => 'freelance',
                CategoryType::workplace->value => 'hybride',
                CategoryType::sector->value => 'zakelijke-dienstverlening',
                CategoryType::function_area->value => 'accountmanagement',
                CategoryType::experience->value => 'senior',
            ]),
            'filled' => $this->categories([
                CategoryType::employment_type->value => 'stage',
                CategoryType::workplace->value => 'op-locatie',
                CategoryType::sector->value => 'e-commerce',
                CategoryType::function_area->value => 'accountmanagement',
                CategoryType::experience->value => 'junior',
            ]),
            'imported' => $this->categories([
                CategoryType::employment_type->value => 'fulltime',
                CategoryType::workplace->value => 'hybride',
                CategoryType::sector->value => 'it',
                CategoryType::function_area->value => 'sales',
                CategoryType::experience->value => 'medior',
            ]),
        ];
    }

    /** @param array<string, string> $definitions */
    private function categories(array $definitions): array
    {
        return collect($definitions)
            ->map(fn (string $slug, string $type): ?int => Category::query()->where('type', $type)->where('slug', $slug)->value('id'))
            ->filter()
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $attributes
     * @param  list<int>  $categoryIds
     * @param  list<string>  $tags
     */
    private function upsertVacancy(string $key, array $attributes, array $categoryIds, array $tags): Vacancy
    {
        $reference = self::FIXTURE_PREFIX.$key;
        $vacancy = Vacancy::withTrashed()->where('reference', $reference)->first() ?? new Vacancy;
        $vacancy->fill([
            'reference' => $reference,
            'description' => 'Fictieve acceptatievacature.',
            'application_mode' => ApplicationMode::Internal,
            'status' => VacancyStatus::Draft,
            'source' => VacancySource::Manual,
            ...$attributes,
        ]);
        $vacancy->slug = $attributes['slug'] ?? self::FIXTURE_PREFIX.'vacancy-'.Str::slug($key);
        $vacancy->save();

        if ($vacancy->trashed()) {
            $vacancy->restore();
        }

        $vacancy->categories()->sync($categoryIds);
        $vacancy->syncTags($tags);

        return $vacancy->fresh();
    }

    /** @param array<string, mixed> $attributes
     * @param  list<int>  $categoryIds
     */
    private function upsertImportedVacancy(string $key, Company $company, ImportSource $source, Import $run, array $attributes, array $categoryIds): Vacancy
    {
        $sourceReference = self::FIXTURE_PREFIX.$key;
        $vacancy = Vacancy::withTrashed()
            ->where('import_source_id', $source->id)
            ->where('source_reference', $sourceReference)
            ->first() ?? new Vacancy;
        $vacancy->fill([
            'company_id' => $company->id,
            'import_source_id' => $source->id,
            'reference' => $sourceReference,
            'source' => VacancySource::Import,
            'source_reference' => $sourceReference,
            'source_requisition_id' => "REQ-{$key}",
            'advertised_state' => 'Utrecht',
            'advertised_country' => 'NL',
            'advertised_postal_code' => '3511AA',
            'workplace_city' => 'Amersfoort',
            'workplace_country' => 'NL',
            'source_salary_text' => '€ 4.000 - € 5.500 bruto per maand',
            'source_language' => 'nl-NL',
            'source_product_code' => 'ACCEPTANCE-PRODUCT',
            'description' => 'Een fictieve geïmporteerde vacature voor acceptatie en lifecycle-tests.',
            'application_mode' => ApplicationMode::Internal,
            'status' => VacancyStatus::Pending,
            'last_seen_import_id' => $run->id,
            'last_seen_at' => now()->subDay(),
            ...$attributes,
        ]);
        $vacancy->slug = $attributes['slug'] ?? self::FIXTURE_PREFIX.'vacancy-'.$key;
        $vacancy->save();

        if ($vacancy->trashed()) {
            $vacancy->restore();
        }

        $vacancy->categories()->sync($categoryIds);
        $vacancy->syncTags(['acceptance', 'imported']);

        return $vacancy->fresh();
    }

    /** @param array<string, User> $users
     * @param  array<string, Company>  $companies
     * @param  array<string, Vacancy>  $vacancies
     */
    private function seedAccountHistory(User $candidate, array $companies, array $vacancies): void
    {
        $candidate->savedVacancies()->syncWithoutDetaching([$vacancies['internal']->id]);
        $candidate->savedVacancies()->syncWithoutDetaching([$vacancies['external']->id]);
        $candidate->savedCompanies()->syncWithoutDetaching([$companies['complete']->id]);
        $candidate->savedCompanies()->syncWithoutDetaching([$companies['imported']->id]);

        $application = Application::withTrashed()->firstOrNew([
            'candidate_id' => $candidate->id,
            'vacancy_id' => $vacancies['internal']->id,
        ]);
        $application->fill([
            'candidate_name' => $candidate->name,
            'candidate_email' => $candidate->email,
            'candidate_phone' => '+31 6 12345678',
            'candidate_location' => 'Utrecht',
            'motivation' => 'Dit is een fictieve acceptatiesollicitatie voor de interne flow.',
            'status' => ApplicationStatus::Reviewed,
        ]);
        $application->save();

    }

    /** @param array<string, Vacancy> $vacancies */
    private function seedOutboundClicks(array $vacancies): void
    {
        $events = [
            [$vacancies['external'], now()->subDays(20)->setTime(9, 0)],
            [$vacancies['external'], now()->subDays(7)->setTime(10, 30)],
            [$vacancies['import_active'], now()->subDays(6)->setTime(11, 15)],
            [$vacancies['import_copy'], now()->subDays(2)->setTime(14, 45)],
            [$vacancies['import_restored'], now()->subDay()->setTime(16, 20)],
        ];

        foreach ($events as [$vacancy, $clickedAt]) {
            $click = OutboundApplyClick::query()
                ->where('vacancy_id', $vacancy->id)
                ->where('clicked_at', $clickedAt)
                ->first() ?? new OutboundApplyClick;
            $click->forceFill(['vacancy_id' => $vacancy->id, 'clicked_at' => $clickedAt])->save();
        }
    }

    /** @param array<string, Vacancy> $vacancies */
    private function seedVacancyDetailViews(array $vacancies): void
    {
        $events = [
            [$vacancies['external'], now()->subDays(20)->setTime(8, 45)],
            [$vacancies['external'], now()->subDays(20)->setTime(9, 5)],
            [$vacancies['external'], now()->subDays(7)->setTime(10, 0)],
            [$vacancies['external'], now()->subDays(7)->setTime(10, 15)],
            [$vacancies['external'], now()->subDays(7)->setTime(10, 20)],
            [$vacancies['internal'], now()->subDays(12)->setTime(13, 0)],
            [$vacancies['internal'], now()->subDays(5)->setTime(14, 0)],
            [$vacancies['internal'], now()->subDays(5)->setTime(14, 5)],
            [$vacancies['import_active'], now()->subDays(6)->setTime(10, 30)],
            [$vacancies['import_active'], now()->subDays(6)->setTime(10, 45)],
            [$vacancies['import_active'], now()->subDays(3)->setTime(11, 0)],
            [$vacancies['import_active'], now()->subDays(3)->setTime(11, 15)],
        ];

        foreach ($events as [$vacancy, $viewedAt]) {
            $view = VacancyDetailView::query()
                ->where('vacancy_id', $vacancy->id)
                ->where('viewed_at', $viewedAt)
                ->first() ?? new VacancyDetailView;
            $view->forceFill(['vacancy_id' => $vacancy->id, 'viewed_at' => $viewedAt])->save();
        }
    }

    /** @param array<string, Vacancy> $vacancies */
    private function seedOrders(User $employer, Company $company, array $vacancies, User $operator): void
    {
        $standard = Package::query()->where('slug', AdvertisingPackage::Standard->value)->firstOrFail();
        $superior = Package::query()->where('slug', AdvertisingPackage::Superior->value)->firstOrFail();
        $definitions = [
            ['reference' => 'SMV-ACCEPTANCE-DRAFT', 'status' => OrderStatus::Draft, 'package' => $standard, 'vacancy' => $vacancies['draft']],
            ['reference' => 'SMV-ACCEPTANCE-PENDING', 'status' => OrderStatus::PendingPayment, 'package' => $superior, 'vacancy' => $vacancies['internal']],
            ['reference' => 'SMV-ACCEPTANCE-CANCELLABLE', 'status' => OrderStatus::PendingPayment, 'package' => $standard, 'vacancy' => null],
            ['reference' => 'SMV-ACCEPTANCE-PAID', 'status' => OrderStatus::Paid, 'package' => $standard, 'vacancy' => $vacancies['external']],
            ['reference' => 'SMV-ACCEPTANCE-CREDIT-STANDARD', 'status' => OrderStatus::Paid, 'package' => $standard, 'vacancy' => null],
            ['reference' => 'SMV-ACCEPTANCE-CREDIT-SUPERIOR', 'status' => OrderStatus::Paid, 'package' => $superior, 'vacancy' => null],
            ['reference' => 'SMV-ACCEPTANCE-PLACEMENT-PENDING', 'status' => OrderStatus::Paid, 'package' => $superior, 'vacancy' => $vacancies['placement_pending']],
            ['reference' => 'SMV-ACCEPTANCE-PLACEMENT-SUPERIOR', 'status' => OrderStatus::Paid, 'package' => $superior, 'vacancy' => $vacancies['placement_superior_active']],
            ['reference' => 'SMV-ACCEPTANCE-PLACEMENT-EXPIRED', 'status' => OrderStatus::Paid, 'package' => $standard, 'vacancy' => $vacancies['placement_expired']],
            ['reference' => 'SMV-ACCEPTANCE-REFUNDED', 'status' => OrderStatus::Paid, 'package' => $standard, 'vacancy' => null],
            ['reference' => 'SMV-ACCEPTANCE-REFUND-REVIEW', 'status' => OrderStatus::Paid, 'package' => $superior, 'vacancy' => $vacancies['placement_refund_review']],
        ];

        foreach ($definitions as $definition) {
            $price = $definition['package']->price_cents;
            $vatRate = $definition['package']->vat_rate_basis_points;
            $vat = intdiv(($price * $vatRate) + 5000, 10_000);
            $order = Order::withTrashed()->firstOrNew(['order_number' => $definition['reference']]);
            $hasGrantedCredits = $order->exists && $order->placementCredits()->exists();
            $order->fill([
                'user_id' => $employer->id,
                'company_id' => $company->id,
                'status' => $definition['status'],
                ...($hasGrantedCredits ? [] : [
                    'subtotal_cents' => $price,
                    'vat_cents' => $vat,
                    'total_cents' => $price + $vat,
                    'currency' => $definition['package']->currency,
                    'vat_rate_basis_points' => $vatRate,
                ]),
            ]);
            $order->save();

            if ($order->trashed()) {
                $order->restore();
            }

            $orderItem = OrderItem::withTrashed()->firstOrNew([
                'order_id' => $order->id,
                'product_code' => $definition['package']->slug,
                'vacancy_id' => $definition['vacancy']?->id,
            ]);
            $orderItem->fill($hasGrantedCredits ? [] : [
                'package_id' => $definition['package']->id,
                'title_snapshot' => $definition['package']->name,
                'package_type_snapshot' => $definition['package']->package_type->value,
                'placement_quantity_snapshot' => $definition['package']->placement_quantity,
                'vacancy_duration_days_snapshot' => $definition['package']->vacancy_duration_days,
                'commercial_priority_snapshot' => $definition['package']->commercial_priority,
                'entitlement_snapshot' => [
                    'features' => $definition['package']->features ?? [],
                    'includes_featured' => $definition['package']->includes_featured,
                    'includes_social_campaign' => $definition['package']->includes_social_campaign,
                    'includes_newsletter' => $definition['package']->includes_newsletter,
                ],
                'price_cents' => $price,
                'currency' => $definition['package']->currency,
                'vat_rate_basis_points' => $vatRate,
                'quantity' => 1,
                'subtotal_cents' => $price,
                'vat_cents' => $vat,
                'total_cents' => $price + $vat,
            ]);
            $orderItem->save();

            if ($orderItem->trashed()) {
                $orderItem->restore();
            }
        }

        $pending = Order::query()->where('order_number', 'SMV-ACCEPTANCE-PENDING')->firstOrFail();
        $paid = Order::query()->where('order_number', 'SMV-ACCEPTANCE-PAID')->firstOrFail();

        $attempts = [
            ['key' => 'acceptance-payment-failed', 'order' => $pending, 'status' => PaymentStatus::Failed, 'reference' => 'fake-acceptance-failed'],
            ['key' => 'acceptance-payment-cancelled', 'order' => $pending, 'status' => PaymentStatus::Cancelled, 'reference' => 'fake-acceptance-cancelled'],
            ['key' => 'acceptance-payment-paid', 'order' => $paid, 'status' => PaymentStatus::Paid, 'reference' => 'fake-acceptance-paid'],
        ];

        foreach (collect($definitions)->where('status', OrderStatus::Paid)->where('reference', '!=', 'SMV-ACCEPTANCE-PAID') as $definition) {
            $order = Order::query()->where('order_number', $definition['reference'])->firstOrFail();
            $key = Str::lower(str_replace('SMV-', '', $definition['reference']));
            $attempts[] = [
                'key' => "acceptance-payment-{$key}",
                'order' => $order,
                'status' => PaymentStatus::Paid,
                'reference' => "fake-{$key}",
            ];
        }

        foreach ($attempts as $attempt) {
            $payment = Payment::withTrashed()->firstOrNew(['idempotency_key' => $attempt['key']]);
            $payment->fill([
                'order_id' => $attempt['order']->id,
                'provider' => 'fake',
                'provider_payment_id' => $attempt['reference'],
                'provider_reference' => $attempt['reference'],
                'status' => $attempt['status'],
                'amount_cents' => $attempt['order']->total_cents,
                'currency' => $attempt['order']->currency,
                'paid_at' => $attempt['status'] === PaymentStatus::Paid ? now()->subHour() : null,
                'completed_at' => now()->subHour(),
                'metadata' => ['fixture' => true, 'scenario' => $attempt['status']->value],
            ]);
            $payment->save();

            if ($payment->trashed()) {
                $payment->restore();
            }
        }

        Order::query()
            ->where('order_number', 'like', 'SMV-ACCEPTANCE-%')
            ->where('status', OrderStatus::Paid->value)
            ->each(fn (Order $order) => app(GrantPaidOrderPlacementCredits::class)->execute($order));

        $this->seedRefundOperations($operator);
    }

    private function seedRefundOperations(User $operator): void
    {
        foreach ([
            ['order' => 'SMV-ACCEPTANCE-REFUNDED', 'reason' => 'Fictieve volledige terugbetaling met ongebruikt plaatsingsrecht.'],
            ['order' => 'SMV-ACCEPTANCE-REFUND-REVIEW', 'reason' => 'Fictieve volledige terugbetaling met actieve plaatsing voor handmatige controle.'],
        ] as $definition) {
            $order = Order::query()->where('order_number', $definition['order'])->firstOrFail();
            $payment = $order->payments()->where('status', PaymentStatus::Paid->value)->firstOrFail();
            $operation = OrderOperation::query()->firstOrNew([
                'order_id' => $order->id,
                'type' => OrderOperationType::Refund,
            ]);
            $operation->fill([
                'payment_id' => $payment->id,
                'operator_user_id' => $operator->id,
                'status' => OrderOperationStatus::Pending,
                'reason' => $definition['reason'],
                'amount_cents' => $payment->amount_cents,
                'currency' => $payment->currency,
                'provider' => 'fake',
                'provider_reference' => 'fake-refund-'.Str::lower(str_replace('SMV-ACCEPTANCE-', '', $definition['order'])),
                'provider_status' => 'pending',
                'idempotency_key' => 'acceptance-refund-'.Str::lower(str_replace('SMV-ACCEPTANCE-', '', $definition['order'])),
                'requested_at' => now()->subMinutes(30),
                'completed_at' => null,
                'metadata' => ['fixture' => true],
            ]);
            $operation->save();

            app(ReconcileRefund::class)->execute($operation, new PaymentOperationResult(
                status: OrderOperationStatus::Succeeded,
                provider: 'fake',
                providerReference: $operation->provider_reference,
                providerStatus: 'succeeded',
                metadata: ['fixture' => true],
            ));
        }
    }

    private function seedRoles(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['candidate', 'employer', 'editor', 'admin', 'super-admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        foreach (['orders.cancel', 'orders.refund'] as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
            Role::query()
                ->whereIn('name', ['super-admin', 'admin', 'editor'])
                ->get()
                ->each(fn (Role $role) => $role->givePermissionTo($permission));
        }
    }

    private function guardEnvironment(): void
    {
        if (! app()->environment(['local', 'testing', 'staging'])) {
            throw new RuntimeException('AcceptanceDemoSeeder is limited to local, testing, or staging environments.');
        }
    }
}
