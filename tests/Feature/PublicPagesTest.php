<?php

test('the template-based public pages are available through their canonical routes', function () {
    $this->get(route('about'))->assertOk()->assertSee('Waarom SMV bestaat');
    $this->get(route('advertising'))
        ->assertOk()
        ->assertSee('Van materiaal tot evaluatie')
        ->assertSee('href="'.route('pricing').'"', false)
        ->assertSee('href="'.route('contact').'"', false);
    $this->get(route('pricing'))
        ->assertOk()
        ->assertSee('Standaard')
        ->assertSee('Superior')
        ->assertSee('Maatwerk')
        ->assertSee('href="'.route('contact').'"', false);
    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('sales@salesenmarketingvacatures.nl')
        ->assertSee('06 30852152')
        ->assertDontSee('form-input w-full', false)
        ->assertDontSee('form-textarea w-full', false);
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
    'over ons' => ['about', 'Over ons | Sales en Marketing Vacatures', 'Lees waarom Sales en Marketing Vacatures zich richt op relevante kansen, werkgevers en vakinhoud voor commerciële professionals.'],
    'tarieven' => ['pricing', 'Tarieven voor vacatureplaatsing | Sales en Marketing Vacatures', 'Bekijk Standaard, Superior en maatwerk voor het plaatsen en zichtbaar maken van een sales- of marketingvacature.'],
    'contact' => ['contact', 'Contact | Sales en Marketing Vacatures', 'Neem contact op over vacatureplaatsing, adverteren en samenwerken met Sales en Marketing Vacatures.'],
    'adverteren' => ['advertising', 'Adverteren voor werkgevers | Sales en Marketing Vacatures', 'Plaats uw sales- of marketingvacature gericht bij Sales en Marketing Vacatures en bespreek passende zichtbaarheid en ondersteuning.'],
]);
