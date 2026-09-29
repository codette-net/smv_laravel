<?php

test('the template-based public pages are available through their canonical routes', function () {
    $this->get(route('about'))->assertOk()->assertSee('Onze missie');
    $this->get(route('pricing'))->assertOk()->assertSee('Op aanvraag');
    $this->get(route('contact'))->assertOk()->assertSee('Neem contact op');
});
