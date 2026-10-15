<?php

use App\Enums\CompanyStatus;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Models\Company;
use App\Models\User;
use App\Support\Companies\CompanyDescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function companyDescriptionUser(string $role = 'employer'): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('the company description policy preserves limited formatting and readable legacy text', function () {
    $description = app(CompanyDescription::class);
    $unsafe = <<<'HTML'
    <h2 class="layout">Over ons</h2><p style="color:red" onclick="bad()">Wij werken <strong>samen</strong>.</p><ul><li>Eén</li></ul><a href="https://example.test" target="_blank">Website</a><a href="javascript:bad()">Onveilig</a><script>secret()</script>
    HTML;

    expect($description->sanitize($unsafe))
        ->toContain('<h2>Over ons</h2>', '<p>Wij werken <strong>samen</strong>.</p>', '<ul><li>Eén</li></ul>', '<a href="https://example.test">Website</a>', '<a>Onveilig</a>')
        ->not->toContain('class=', 'style=', 'onclick=', 'target=', 'javascript:', '<script', 'secret()')
        ->and($description->sanitize("Eerste regel\nTweede regel\n\nNieuw blok"))
        ->toBe('<p>Eerste regel<br />Tweede regel</p><p>Nieuw blok</p>');
});

test('an employer edits only an owned company through the reusable rich text editor', function () {
    $owner = companyDescriptionUser();
    $other = companyDescriptionUser();
    $company = Company::factory()->for($owner)->create(['status' => CompanyStatus::Active]);

    $this->actingAs($owner)
        ->get(route('account.companies.edit', $company))
        ->assertOk()
        ->assertSee('richTextEditor', false)
        ->assertSee('contenteditable="true"', false)
        ->assertSee('<textarea', false)
        ->assertSee('aria-label="Vet"', false)
        ->assertSee('aria-label="Link toevoegen"', false);

    $richText = '<h2>Onze cultuur</h2><p onclick="bad()">Samen bouwen wij aan <strong>groei</strong>.</p><ul><li>Vertrouwen</li></ul><a href="https://example.test">Lees meer</a><a href="javascript:bad()">Onveilig</a><script>secret()</script>';

    $this->actingAs($owner)
        ->patch(route('account.companies.update', $company), [
            'name' => $company->name,
            'description' => $richText,
        ])
        ->assertRedirect(route('account.index'));

    expect($company->fresh()->description)
        ->toContain('<h2>Onze cultuur</h2>', '<strong>groei</strong>', '<ul><li>Vertrouwen</li></ul>', '<a href="https://example.test">Lees meer</a>', '<a>Onveilig</a>')
        ->not->toContain('onclick', 'javascript:', '<script', 'secret()');

    $this->actingAs($other)
        ->patch(route('account.companies.update', $company), [
            'name' => 'Onbevoegde wijziging',
            'description' => '<p>Mag niet worden opgeslagen.</p>',
        ])
        ->assertForbidden();

    expect($company->fresh()->name)->not->toBe('Onbevoegde wijziging');
});

test('the public company page defensively renders legacy descriptions without rewriting them', function () {
    $company = Company::factory()->create(['status' => CompanyStatus::Active]);
    $legacy = "Eerste regel\nTweede regel\n\nNieuw blok";
    DB::table('companies')->where('id', $company->id)->update(['description' => $legacy]);

    $this->get(route('bedrijven.show', $company->fresh()))
        ->assertOk()
        ->assertSee('<p>Eerste regel<br />Tweede regel</p><p>Nieuw blok</p>', false)
        ->assertSee('<meta name="description" content="Eerste regel Tweede regel Nieuw blok">', false);

    expect(DB::table('companies')->where('id', $company->id)->value('description'))->toBe($legacy);

    $unsafeLegacy = '<h3 onclick="bad()">Historische kop</h3><p>Leesbare inhoud.</p><script>secret()</script>';
    DB::table('companies')->where('id', $company->id)->update(['description' => $unsafeLegacy]);

    $this->get(route('bedrijven.show', $company->fresh()))
        ->assertOk()
        ->assertSee('<h3>Historische kop</h3>', false)
        ->assertSee('<p>Leesbare inhoud.</p>', false)
        ->assertDontSee('onclick=', false)
        ->assertDontSee('<script>secret()</script>', false)
        ->assertDontSee('secret()', false)
        ->assertSee('<meta name="description" content="Historische kop Leesbare inhoud.">', false)
        ->assertSee('"description":"Historische kop Leesbare inhoud."', false);

    expect(DB::table('companies')->where('id', $company->id)->value('description'))->toBe($unsafeLegacy);
});

test('Filament company editing follows the same server-side description policy', function () {
    $administrator = companyDescriptionUser('admin');
    $company = Company::factory()->create();

    $this->actingAs($administrator);

    Livewire::test(EditCompany::class, ['record' => $company->getRouteKey()])
        ->fillForm([
            'description' => '<h3>Administratief</h3><p onmouseover="bad()">Veilige <em>inhoud</em>.</p><a href="data:text/html,bad">Onveilig</a><script>secret()</script>',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($company->fresh()->description)
        ->toContain('<h3>Administratief</h3>', '<em>inhoud</em>', 'Onveilig')
        ->not->toContain('onmouseover', 'data:', '<script', 'secret()');
});
