<?php

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the homepage renders the shared public navigation footer and vacancy search form', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertViewIs('home')
        ->assertSee('Vind jouw volgende commerciële uitdaging')
        ->assertSee('action="'.route('vacancies.index').'"', false)
        ->assertSee('name="zoek"', false)
        ->assertSee('name="locatie"', false)
        ->assertSee('name="dienstverband"', false)
        ->assertSee('name="functiegebied"', false)
        ->assertSee('href="'.route('home').'"', false)
        ->assertSee('href="'.route('vacancies.index').'"', false)
        ->assertSee('href="'.route('companies.index').'"', false)
        ->assertSee('href="'.route('blog.index').'"', false)
        ->assertSee('href="'.route('filament.dashboard.auth.login').'"', false)
        ->assertSee('Footer navigatie')
        ->assertSee('© '.now()->year.' Sales en Marketing Vacatures');
});

test('the shared public shell is rendered on public pages', function () {
    BlogPost::factory()->published()->create(['title' => 'Publiek artikel']);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('Hoofdnavigatie')
        ->assertSee('Footer navigatie')
        ->assertSee('href="'.route('filament.dashboard.auth.login').'"', false);
});
