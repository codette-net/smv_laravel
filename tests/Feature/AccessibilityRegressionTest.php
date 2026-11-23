<?php

use App\Enums\ContactPurpose;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the public shell exposes labelled mobile navigation and restores menu focus on escape', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('x-ref="menuTrigger"', false)
        ->assertSee('type="button"', false)
        ->assertSee('id="mobile-nav"', false)
        ->assertSee('aria-label="Mobiele hoofdnavigatie"', false)
        ->assertSee('if (expanded) { expanded = false; $nextTick(() => $refs.menuTrigger.focus()) }', false)
        ->assertSee('x-ref="accountTrigger"', false)
        ->assertSee('if (open) { open = false; $nextTick(() => $refs.accountTrigger.focus()) }', false);
});

test('the custom select preserves its selected value and supports keyboard opening', function (): void {
    $purpose = ContactPurpose::Advertising->value;

    $response = $this->get(route('contact', ['reason' => $purpose]));

    $response
        ->assertOk()
        ->assertSee('name="purpose"', false)
        ->assertSee('type="hidden"', false)
        ->assertSee('value="'.$purpose.'"', false)
        ->assertSee('x-on:keydown.arrow-up.prevent="openMenu()"', false)
        ->assertSee('x-on:keydown.arrow-down.prevent="openMenu()"', false);

    expect($response->getContent())->toContain('querySelector(\'[aria-selected=true]\')');
});

test('the reduced-motion policy is included in the public frontend stylesheet', function (): void {
    expect(file_get_contents(resource_path('css/app.css')))
        ->toContain('@media (prefers-reduced-motion: reduce)')
        ->toContain('transition-duration: 0.01ms !important;');
});
