<?php

use App\Enums\ApplicationMode;
use App\Enums\ApplicationStatus;
use App\Enums\CompanyStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Applications\Pages\EditApplication;
use App\Models\Application;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function trackingUser(string $role = 'candidate'): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => $role]);
    $user->assignRole($role);

    return $user;
}

function trackingVacancy(array $attributes = [], array $companyAttributes = []): Vacancy
{
    return Vacancy::factory()->for(Company::factory()->state([
        'status' => CompanyStatus::Active,
        ...$companyAttributes,
    ]))->create([
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Manual,
        'application_mode' => ApplicationMode::Internal,
        'is_filled' => false,
        'published_at' => now()->subDay(),
        'deadline_at' => now()->addWeek(),
        'expires_at' => now()->addMonth(),
        ...$attributes,
    ]);
}

test('an authenticated internal application is linked from the session and not client input', function () {
    Notification::fake();
    $candidate = trackingUser();
    $other = trackingUser();
    $vacancy = trackingVacancy();

    $this->actingAs($candidate)->post(route('applications.store', $vacancy), [
        'candidate_id' => $other->id,
        'candidate_name' => 'Kandidaat',
        'candidate_email' => 'kandidaat@example.test',
        'motivation' => 'Een geldige motivatie voor deze functie.',
    ])->assertRedirect(route('applications.success', $vacancy));

    expect(Application::query()->sole()->candidate->is($candidate))->toBeTrue();
});

test('guest applications remain supported but are not claimed by matching email', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'zelfde@example.test']);
    $vacancy = trackingVacancy();

    $this->post(route('applications.store', $vacancy), [
        'candidate_name' => 'Gast',
        'candidate_email' => 'zelfde@example.test',
        'motivation' => 'Een geldige motivatie voor deze functie.',
    ])->assertRedirect();

    expect(Application::query()->sole()->candidate_id)->toBeNull();

    $this->actingAs($user)->get(route('account.applications'))
        ->assertOk()
        ->assertSee('U heeft nog geen sollicitaties via SMV')
        ->assertDontSee($vacancy->title);
});

test('candidate and employer accounts can see only their own internal applications', function (string $role) {
    $user = trackingUser($role);
    $other = trackingUser();
    $ownVacancy = trackingVacancy(['title' => 'Eigen publieke sollicitatie']);
    $otherVacancy = trackingVacancy(['title' => 'Sollicitatie van een ander']);
    Application::factory()->for($ownVacancy)->create(['candidate_id' => $user->id, 'status' => ApplicationStatus::New]);
    $otherApplication = Application::factory()->for($otherVacancy)->create(['candidate_id' => $other->id]);

    $this->actingAs($user)->get(route('account.applications', ['application' => $otherApplication->id]))
        ->assertOk()
        ->assertSee('Eigen publieke sollicitatie')
        ->assertSee('Ontvangen')
        ->assertDontSee('Sollicitatie van een ander');
})->with(['candidate', 'employer']);

test('guests cannot access the private applications account page', function () {
    $this->get(route('account.applications'))->assertRedirect(route('login'));
});

test('candidate status deliberately hides private application fields and internal wording', function () {
    $candidate = trackingUser();
    $vacancy = trackingVacancy(['title' => 'Veilige vacaturetitel']);
    Application::factory()->for($vacancy)->create([
        'candidate_id' => $candidate->id,
        'candidate_email' => 'privé@example.test',
        'motivation' => 'Vertrouwelijke motivatie',
        'linkedin_url' => 'https://example.test/private-profile',
        'cv_path' => 'applications/private-cv.pdf',
        'status' => ApplicationStatus::Contacted,
    ]);

    $this->actingAs($candidate)->get(route('account.applications'))
        ->assertOk()
        ->assertSee('Veilige vacaturetitel')
        ->assertSee('Status')
        ->assertSee('In behandeling')
        ->assertDontSee('Contact opgenomen')
        ->assertDontSee('privé@example.test')
        ->assertDontSee('Vertrouwelijke motivatie')
        ->assertDontSee('private-profile')
        ->assertDontSee('private-cv.pdf');
});

test('every internal workflow status has a deliberate readable candidate label', function (ApplicationStatus $status, string $label) {
    expect($status->candidateLabel())->toBe($label)
        ->and($status->candidateBadgeVariant())->not->toBeEmpty();
})->with([
    'new' => [ApplicationStatus::New, 'Ontvangen'],
    'reviewed' => [ApplicationStatus::Reviewed, 'In behandeling'],
    'contacted' => [ApplicationStatus::Contacted, 'In behandeling'],
    'rejected' => [ApplicationStatus::Rejected, 'Afgewezen'],
    'hired' => [ApplicationStatus::Hired, 'Aangenomen'],
]);

test('an administrator can update the existing workflow status and the candidate sees its public mapping', function () {
    $candidate = trackingUser();
    $admin = trackingUser('admin');
    $application = Application::factory()->for(trackingVacancy())->create([
        'candidate_id' => $candidate->id,
        'status' => ApplicationStatus::New,
    ]);

    $this->actingAs($admin);
    Livewire::test(EditApplication::class, ['record' => $application->getKey()])
        ->fillForm(['status' => ApplicationStatus::Reviewed->value])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($application->fresh()->status)->toBe(ApplicationStatus::Reviewed);

    $this->actingAs($candidate)->get(route('account.applications'))
        ->assertOk()
        ->assertSee('In behandeling')
        ->assertDontSee('Beoordeeld');
});

test('non-administrative panel and public users cannot edit application status', function (string $role) {
    $user = trackingUser($role);
    $application = Application::factory()->for(trackingVacancy())->create();

    $this->actingAs($user)
        ->get(ApplicationResource::getUrl('edit', ['record' => $application]))
        ->assertForbidden();
})->with(['editor', 'employer', 'candidate']);

test('unavailable vacancy states retain the application status without leaking vacancy or company content', function (array $changes) {
    $candidate = trackingUser();
    $vacancy = trackingVacancy(['title' => 'Verborgen functietitel']);
    $companyName = $vacancy->company->name;
    Application::factory()->for($vacancy)->create([
        'candidate_id' => $candidate->id,
        'status' => ApplicationStatus::Rejected,
    ]);

    $vacancy->update($changes);

    $this->actingAs($candidate)->get(route('account.applications'))
        ->assertOk()
        ->assertSee('Vacature niet meer beschikbaar')
        ->assertSee('Afgewezen')
        ->assertDontSee('Verborgen functietitel')
        ->assertDontSee($companyName);
})->with([
    'filled' => [['is_filled' => true]],
    'expired' => [['expires_at' => now()->subMinute()]],
    'draft' => [['status' => VacancyStatus::Draft]],
    'archived' => [['status' => VacancyStatus::Archived]],
]);

test('a soft deleted vacancy remains a private understandable application history row', function () {
    $candidate = trackingUser();
    $vacancy = trackingVacancy(['title' => 'Verwijderde functietitel']);
    Application::factory()->for($vacancy)->create(['candidate_id' => $candidate->id]);
    $vacancy->delete();

    $this->actingAs($candidate)->get(route('account.applications'))
        ->assertOk()
        ->assertSee('Vacature niet meer beschikbaar')
        ->assertDontSee('Verwijderde functietitel');
});

test('a hidden company makes the application context private without removing its status', function () {
    $candidate = trackingUser();
    $vacancy = trackingVacancy(['title' => 'Verborgen bedrijfsfunctie']);
    $company = $vacancy->company;
    Application::factory()->for($vacancy)->create([
        'candidate_id' => $candidate->id,
        'status' => ApplicationStatus::Hired,
    ]);
    $company->update(['status' => CompanyStatus::Pending]);

    $this->actingAs($candidate)->get(route('account.applications'))
        ->assertOk()
        ->assertSee('Vacature niet meer beschikbaar')
        ->assertSee('Aangenomen')
        ->assertDontSee('Verborgen bedrijfsfunctie')
        ->assertDontSee($company->name);
});

test('external and email application destinations never create internal application records', function (ApplicationMode $mode, array $attributes) {
    $vacancy = trackingVacancy(['application_mode' => $mode, ...$attributes]);

    $this->post(route('applications.store', $vacancy), [
        'candidate_name' => 'Niet opgeslagen',
        'candidate_email' => 'niet@example.test',
        'motivation' => 'Deze aanvraag mag niet worden opgeslagen.',
    ])->assertNotFound();

    expect(Application::query()->count())->toBe(0);
})->with([
    'external' => [ApplicationMode::External, ['application_url' => 'https://example.test/apply']],
    'email' => [ApplicationMode::Email, ['application_email' => 'jobs@example.test']],
]);

test('my applications is paginated newest first and linked from the account overview', function () {
    $candidate = trackingUser();
    $vacancy = trackingVacancy();
    Application::factory()->count(13)->for($vacancy)->create(['candidate_id' => $candidate->id]);

    $this->actingAs($candidate)->get(route('account.applications'))
        ->assertOk()
        ->assertViewHas('applications', fn ($applications) => $applications->total() === 13
            && $applications->perPage() === 12
            && $applications->lastPage() === 2);

    $this->get(route('account.index'))
        ->assertOk()
        ->assertSee('Mijn sollicitaties')
        ->assertSee(route('account.applications'), false);
});

test('my applications eager loads vacancy context without per-row queries', function () {
    $candidate = trackingUser();
    $createApplications = function (int $count) use ($candidate): void {
        Application::factory()->count($count)->create([
            'candidate_id' => $candidate->id,
            'vacancy_id' => fn () => trackingVacancy()->id,
        ]);
    };

    $createApplications(2);
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->actingAs($candidate)->get(route('account.applications'))->assertOk();
    $smallListQueries = count(DB::getQueryLog());

    $createApplications(6);
    DB::flushQueryLog();
    $this->get(route('account.applications'))->assertOk();
    $largeListQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($largeListQueries)->toBeLessThanOrEqual($smallListQueries + 1);
});
