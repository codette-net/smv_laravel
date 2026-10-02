<?php

test('the template-based public pages are available through their canonical routes', function () {
    $this->get(route('about'))->assertOk()->assertSee('Onze missie');
    $this->get(route('pricing'))->assertOk()->assertSee('Op aanvraag');
    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('Neem contact op')
        ->assertSee('form-input w-full', false)
        ->assertSee('form-textarea w-full', false)
        ->assertSee('Bericht versturen');
});

test('static public pages expose clean canonicals and matching Open Graph metadata', function (string $routeName, string $title, string $description) {
    config(['app.env' => 'production']);

    $canonical = route($routeName);

    $this->get($canonical.'?utm_source=test')
        ->assertOk()
        ->assertSee('<title>'.$title.'</title>', false)
        ->assertSee('<meta name="description" content="'.$description.'">', false)
        ->assertSee('<link rel="canonical" href="'.$canonical.'">', false)
        ->assertSee('<meta property="og:title" content="'.$title.'">', false)
        ->assertSee('<meta property="og:description" content="'.$description.'">', false)
        ->assertSee('<meta property="og:url" content="'.$canonical.'">', false)
        ->assertDontSee('utm_source=test', false);
})->with([
    'over ons' => ['about', 'Over ons | Sales en Marketing Vacatures', 'Lees meer over Sales en Marketing Vacatures en onze focus op commercieel talent.'],
    'tarieven' => ['pricing', 'Tarieven | Sales en Marketing Vacatures', 'Bekijk de mogelijkheden voor het plaatsen van vacatures bij Sales en Marketing Vacatures.'],
    'contact' => ['contact', 'Contact | Sales en Marketing Vacatures', 'Neem contact op met Sales en Marketing Vacatures.'],
]);
