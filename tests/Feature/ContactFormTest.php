<?php

use App\Mail\ContactRequestMail;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    RateLimiter::clear('contact:127.0.0.1');
});

afterEach(function () {
    RateLimiter::clear('contact:127.0.0.1');
});

function validContactData(array $overrides = []): array
{
    return [
        'name' => 'Sanne Bezoeker',
        'email' => 'sanne@example.com',
        'purpose' => 'advertising',
        'company' => 'Voorbeeld BV',
        'phone' => '+31 6 12345678',
        'message' => 'Ik ontvang graag meer informatie over adverteren.',
        ...$overrides,
    ];
}

test('the contact page shows the public details and working form', function () {
    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('sales@salesenmarketingvacatures.nl')
        ->assertSee('06 30852152')
        ->assertSee('Stuur ons een bericht')
        ->assertSee('action="'.route('contact.store').'"', false)
        ->assertSee('name="name"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="purpose"', false)
        ->assertSee('name="message"', false);
});

test('an allowlisted advertising reason is preselected without changing the canonical', function () {
    config(['app.env' => 'production']);

    $this->get(route('contact', ['reason' => 'advertising']))
        ->assertOk()
        ->assertSee("value: 'advertising'", false)
        ->assertSee('<link rel="canonical" href="'.route('contact').'">', false)
        ->assertDontSee('reason=advertising', false);

    $this->get(route('contact', ['reason' => 'not-allowed']))
        ->assertOk()
        ->assertSee("value: ''", false)
        ->assertDontSee("value: 'not-allowed'", false)
        ->assertSee('<link rel="canonical" href="'.route('contact').'">', false);
});

test('a valid contact request sends one internal email and uses post redirect get', function () {
    Mail::fake();
    config(['contact.mail_to' => 'contact-recipient@example.com']);

    $response = $this->post(route('contact.store'), validContactData());

    $response
        ->assertRedirect(route('contact'))
        ->assertSessionHas('contact_success')
        ->assertSessionDoesntHaveErrors();

    Mail::assertSent(ContactRequestMail::class, function (ContactRequestMail $mail): bool {
        expect($mail->contactName)->toBe('Sanne Bezoeker')
            ->and($mail->contactEmail)->toBe('sanne@example.com')
            ->and($mail->purposeLabel)->toBe('Vraag over adverteren')
            ->and($mail->company)->toBe('Voorbeeld BV')
            ->and($mail->phone)->toBe('+31 6 12345678')
            ->and($mail->messageBody)->toBe('Ik ontvang graag meer informatie over adverteren.')
            ->and($mail->render())->toContain('Nieuwe contactaanvraag')
            ->toContain('Sanne Bezoeker')
            ->toContain('Vraag over adverteren')
            ->toContain('Ik ontvang graag meer informatie over adverteren.');

        return $mail->hasTo('contact-recipient@example.com')
            && $mail->hasReplyTo('sanne@example.com', 'Sanne Bezoeker');
    });

    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('Bericht verzonden')
        ->assertSee('Bedankt voor uw bericht');
});

test('contact validation is server side and preserves safe submitted values', function () {
    Mail::fake();

    $this->from(route('contact'))
        ->post(route('contact.store'), validContactData([
            'name' => '',
            'email' => 'geen-geldig-adres',
            'purpose' => 'not-allowed',
            'message' => '',
        ]))
        ->assertRedirect(route('contact'))
        ->assertSessionHasErrors(['name', 'email', 'purpose', 'message'])
        ->assertSessionHasInput('company', 'Voorbeeld BV')
        ->assertSessionHasInput('phone', '+31 6 12345678');

    Mail::assertNothingSent();
});

test('contact validation rejects excessive lengths and invalid phone numbers', function () {
    Mail::fake();

    $this->post(route('contact.store'), validContactData([
        'name' => str_repeat('n', 121),
        'email' => str_repeat('e', 250).'@example.com',
        'company' => str_repeat('c', 161),
        'phone' => 'invalid',
        'message' => str_repeat('m', 5001),
    ]))->assertSessionHasErrors(['name', 'email', 'company', 'phone', 'message']);

    Mail::assertNothingSent();
});

test('the honeypot quietly accepts but does not deliver bot submissions', function () {
    Mail::fake();

    $this->post(route('contact.store'), validContactData(['website' => 'https://spam.example']))
        ->assertRedirect(route('contact'))
        ->assertSessionHas('contact_success')
        ->assertSessionDoesntHaveErrors();

    Mail::assertNothingSent();
});

test('the contact rate limiter blocks abusive repeated delivery with a usable response', function () {
    Mail::fake();
    config(['contact.rate_limit_per_minute' => 2]);

    $this->post(route('contact.store'), validContactData())->assertSessionHas('contact_success');
    $this->post(route('contact.store'), validContactData())->assertSessionHas('contact_success');
    $this->post(route('contact.store'), validContactData())
        ->assertRedirect(route('contact'))
        ->assertSessionHas('contact_error', 'U heeft te veel berichten kort na elkaar verstuurd. Wacht even en probeer het daarna opnieuw.')
        ->assertSessionMissing('contact_success');

    Mail::assertSent(ContactRequestMail::class, 2);
});

test('a mail transport failure returns a neutral error and never false success', function () {
    Mail::shouldReceive('to')
        ->once()
        ->with(config('contact.mail_to'))
        ->andThrow(new RuntimeException('SMTP password=secret internal failure'));

    $response = $this->from(route('contact'))->post(route('contact.store'), validContactData());

    $response
        ->assertRedirect(route('contact'))
        ->assertSessionHas('contact_error', 'Uw bericht kon op dit moment niet worden verzonden. Probeer het later opnieuw.')
        ->assertSessionMissing('contact_success')
        ->assertSessionHasInput('name', 'Sanne Bezoeker');

    expect((string) session('contact_error'))
        ->not->toContain('SMTP')
        ->not->toContain('secret');
});

test('contact requests have no database persistence model or table', function () {
    expect(class_exists(ContactMessage::class))->toBeFalse()
        ->and(Schema::hasTable('contact_messages'))->toBeFalse();
});
