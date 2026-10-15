<?php

use App\Enums\AdvertisingPackage;
use App\Enums\ApplicationMode;
use App\Enums\CompanyStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use App\Support\Vacancies\VacancyDescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function richTextEmployer(): array
{
    Role::firstOrCreate(['name' => 'employer', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('employer');
    $company = Company::factory()->for($user)->create(['status' => CompanyStatus::Pending]);

    return [$user, $company];
}

function publicRichTextVacancy(array $attributes = []): Vacancy
{
    return Vacancy::factory()->create([
        'company_id' => Company::factory()->state(['status' => CompanyStatus::Active]),
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Manual,
        'is_filled' => false,
        'published_at' => now()->subDay(),
        'deadline_at' => now()->addWeek(),
        'expires_at' => now()->addMonth(),
        ...$attributes,
    ]);
}

test('the canonical vacancy sanitizer keeps only the agreed semantic formatting', function () {
    $html = <<<'HTML'
    <div class="layout"><h2 id="kop">Functie</h2><p style="color:red" onclick="alert(1)">Werk <strong>veilig</strong> en <em>goed</em>.</p><ul><li>Eén</li></ul><ol><li>Twee</li></ol><a href="https://example.test" target="_blank">Veilige link</a><a href="javascript:alert(2)">Onveilige link</a><img src=x onerror="alert(3)"><script>alert(4)</script><iframe src="https://evil.test">frame</iframe></div>
    HTML;

    $sanitized = app(VacancyDescription::class)->sanitize($html);

    expect($sanitized)
        ->toContain('<h2>Functie</h2>', '<p>Werk <strong>veilig</strong> en <em>goed</em>.</p>', '<ul>', '<ol>', '<li>Eén</li>', '<a href="https://example.test">Veilige link</a>', '<a>Onveilige link</a>')
        ->not->toContain('<div', 'class=', 'id=', 'style=', 'onclick=', 'target=', '<img', 'onerror=', 'javascript:', '<script', 'alert(4)', '<iframe', 'evil.test');
});

test('plain and multiline legacy descriptions become safe semantic html', function () {
    $description = app(VacancyDescription::class);

    expect($description->sanitize("Eerste regel\nTweede regel\n\nNieuw blok"))
        ->toBe('<p>Eerste regel<br />Tweede regel</p><p>Nieuw blok</p>')
        ->and($description->sanitize('Eerste blok<div>Tweede blok</div><div>Derde blok</div>'))
        ->toBe('Eerste blok<p>Tweede blok</p><p>Derde blok</p>')
        ->and($description->plainText('<h2>Kop</h2><p>Tekst <strong>vet</strong>.</p><ul><li>Punt</li></ul>'))
        ->toBe('Kop Tekst vet. Punt');
});

test('all vacancy model writes use the canonical sanitizer', function () {
    $vacancy = Vacancy::factory()->create([
        'company_id' => Company::factory(),
        'description' => '<h2>Rol</h2><p onclick="bad()">Een <strong>inhoudelijke</strong> vacaturetekst.</p><script>secret()</script>',
    ]);

    expect($vacancy->description)
        ->toBe('<h2>Rol</h2><p>Een <strong>inhoudelijke</strong> vacaturetekst.</p>')
        ->not->toContain('onclick', 'script', 'secret');
});

test('the employer editor is progressively enhanced and stores sanitized rich text', function () {
    [$user, $company] = richTextEmployer();

    $this->actingAs($user)
        ->withSession(['vacancy_placement.package' => AdvertisingPackage::Standard->value])
        ->get(route('vacancy-placement.create'))
        ->assertOk()
        ->assertSee('richTextEditor', false)
        ->assertSee('contenteditable="true"', false)
        ->assertSee('<textarea', false)
        ->assertSee('Opmaak vacaturebeschrijving')
        ->assertSee('aria-label="Vet"', false)
        ->assertSee('aria-label="Genummerde lijst"', false)
        ->assertSee('aria-label="Opnieuw"', false);

    $description = '<h2>Jouw rol</h2><p onclick="bad()">Je werkt aan commerciële groei met <strong>goede klanten</strong> en een ervaren team.</p><script>steal()</script>';

    $this->actingAs($user)
        ->withSession(['vacancy_placement.package' => AdvertisingPackage::Standard->value])
        ->post(route('vacancy-placement.store'), [
            'company_id' => $company->id,
            'title' => 'Veilige accountmanager',
            'description' => $description,
            'location' => 'Utrecht',
            'application_mode' => ApplicationMode::Internal->value,
        ])
        ->assertRedirect();

    $vacancy = Vacancy::query()->sole();
    expect($vacancy->description)
        ->toContain('<h2>Jouw rol</h2>', '<strong>goede klanten</strong>')
        ->not->toContain('onclick', '<script', 'steal');

    $this->actingAs($user)
        ->get(route('vacancy-placement.preview', $vacancy))
        ->assertOk()
        ->assertSee('<h2>Jouw rol</h2>', false)
        ->assertSee('<strong>goede klanten</strong>', false)
        ->assertDontSee('steal()', false);
});

test('markup without meaningful visible content is rejected', function () {
    [$user, $company] = richTextEmployer();

    $this->actingAs($user)
        ->withSession(['vacancy_placement.package' => AdvertisingPackage::Standard->value])
        ->post(route('vacancy-placement.store'), [
            'company_id' => $company->id,
            'title' => 'Lege vacaturetekst',
            'description' => '<p><br></p><script>dit telt niet mee</script>',
            'location' => 'Utrecht',
            'application_mode' => ApplicationMode::Internal->value,
        ])
        ->assertSessionHasErrors('description');

    expect(Vacancy::count())->toBe(0);
});

test('public rendering defensively sanitizes historical database content without mutating it', function () {
    $vacancy = publicRichTextVacancy();
    $legacy = '<h2 class="legacy">Historische tekst</h2><p>Veilige inhoud.</p><img src=x onerror="bad()"><script>steal()</script>';
    DB::table('vacancies')->where('id', $vacancy->id)->update(['description' => $legacy]);

    $this->get(route('vacancies.show', $vacancy->fresh()))
        ->assertOk()
        ->assertSee('<h2>Historische tekst</h2>', false)
        ->assertSee('<p>Veilige inhoud.</p>', false)
        ->assertDontSee('class="legacy"', false)
        ->assertDontSee('<img src=x', false)
        ->assertDontSee('onerror="bad()"', false)
        ->assertDontSee('steal()', false);

    expect(DB::table('vacancies')->where('id', $vacancy->id)->value('description'))->toBe($legacy);
});

test('vacancy metadata and JobPosting use plain text only', function () {
    $vacancy = publicRichTextVacancy([
        'title' => 'Rich text vacature',
        'description' => '<h2>Werken bij ons</h2><p>Bouw aan <strong>duurzame groei</strong>.</p>',
    ]);

    $response = $this->get(route('vacancies.show', $vacancy))->assertOk();

    $response
        ->assertSee('<meta name="description" content="Werken bij ons Bouw aan duurzame groei.">', false)
        ->assertSee('"description":"Werken bij ons Bouw aan duurzame groei."', false)
        ->assertDontSee('"description":"<h2>', false);
});
