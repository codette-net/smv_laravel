<?php

use App\Enums\ApplicationMode;
use App\Enums\CompanyStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\Application;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use App\Notifications\NewApplicationNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-08-19 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function applicableVacancy(ApplicationMode $mode = ApplicationMode::Internal, array $attributes = []): Vacancy
{
    return Vacancy::factory()->create([
        'company_id' => Company::factory()->state(['status' => CompanyStatus::Active]),
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Manual,
        'is_filled' => false,
        'published_at' => now()->subDay(),
        'deadline_at' => now()->addWeek(),
        'expires_at' => now()->addMonth(),
        'application_mode' => $mode,
        ...$attributes,
    ]);
}

test('the vacancy detail renders one CTA according to the selected application mode', function () {
    $external = applicableVacancy(ApplicationMode::External, ['application_url' => 'https://example.test/apply', 'application_email' => 'ignored@example.test']);
    $email = applicableVacancy(ApplicationMode::Email, ['application_url' => 'https://example.test/ignored', 'application_email' => 'mail@example.test']);
    $internal = applicableVacancy(ApplicationMode::Internal, ['application_url' => 'https://example.test/ignored', 'application_email' => 'ignored@example.test']);

    $this->get(route('vacancies.show', $external))->assertSee('https://example.test/apply', false)->assertDontSee(route('applications.create', $external), false);
    $this->get(route('vacancies.show', $email))->assertSee('mailto:mail@example.test', false)->assertSee('Solliciteer via e-mail')->assertDontSee('https://example.test/ignored', false);
    $this->get(route('vacancies.show', $internal))->assertSee(route('applications.create', $internal), false)->assertDontSee('https://example.test/ignored', false);
});

test('internal application form is public only for current internally configured vacancies', function () {
    $internal = applicableVacancy();
    $external = applicableVacancy(ApplicationMode::External, ['application_url' => 'https://example.test/apply']);
    $expired = applicableVacancy(ApplicationMode::Internal, ['expires_at' => Carbon::parse('2026-08-18 12:00:00')]);

    $this->get(route('applications.create', $internal))->assertOk()->assertSee('Solliciteer op');
    $this->get(route('applications.create', $external))->assertNotFound();
    $this->get(route('applications.create', $expired))->assertNotFound();

    foreach ([
        ['status' => VacancyStatus::Draft],
        ['is_filled' => true],
        ['deadline_at' => Carbon::parse('2026-08-18 12:00:00')],
        ['published_at' => Carbon::parse('2026-08-20 12:00:00')],
    ] as $attributes) {
        $this->get(route('applications.create', applicableVacancy(ApplicationMode::Internal, $attributes)))->assertNotFound();
    }
});

test('a valid internal application is stored privately and redirects to confirmation', function () {
    Storage::fake('local');
    Notification::fake();
    $vacancy = applicableVacancy();

    $response = $this->post(route('applications.store', $vacancy), [
        'candidate_name' => 'Sanne Sollicitant',
        'candidate_email' => 'sanne@example.com',
        'candidate_phone' => '0612345678',
        'candidate_location' => 'Utrecht',
        'linkedin_url' => 'https://www.linkedin.com/in/sanne',
        'motivation' => 'Ik licht mijn motivatie graag toe in een gesprek.',
        'cv' => UploadedFile::fake()->create('cv.pdf', 120, 'application/pdf'),
    ]);

    $response->assertRedirect(route('applications.success', $vacancy));
    $application = Application::query()->sole();

    expect($application->vacancy->is($vacancy))->toBeTrue()
        ->and($application->candidate_name)->toBe('Sanne Sollicitant')
        ->and($application->cv_path)->not->toBeNull();
    Storage::disk('local')->assertExists($application->cv_path);
    Notification::assertSentOnDemand(NewApplicationNotification::class);

    $this->get(route('applications.success', $vacancy))->assertOk()->assertSee('Bedankt voor je sollicitatie');
});

test('internal application validation rejects invalid required and uploaded values', function () {
    $vacancy = applicableVacancy();

    $this->from(route('applications.create', $vacancy))
        ->post(route('applications.store', $vacancy), ['candidate_email' => 'ongeldig', 'motivation' => str_repeat('a', 5001), 'cv' => UploadedFile::fake()->create('cv.exe', 100, 'application/octet-stream')])
        ->assertRedirect(route('applications.create', $vacancy))
        ->assertSessionHasErrors(['candidate_name', 'candidate_email', 'motivation', 'cv']);
});

test('only administrators can review applications', function () {
    collect(['admin', 'editor', 'employer', 'candidate'])->each(fn (string $role) => Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
    $application = Application::factory()->create(['vacancy_id' => applicableVacancy()]);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $employer = User::factory()->create();
    $employer->assignRole('employer');
    $candidate = User::factory()->create();
    $candidate->assignRole('candidate');
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $this->actingAs($admin)->get(ApplicationResource::getUrl())->assertSuccessful();
    $this->actingAs($editor)->get(ApplicationResource::getUrl())->assertForbidden();
    $this->actingAs($employer)->get(ApplicationResource::getUrl())->assertForbidden();
    $this->actingAs($candidate)->get(ApplicationResource::getUrl())->assertForbidden();
    expect(ApplicationResource::canView($application))->toBeFalse();
});
