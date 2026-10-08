<?php

namespace App\Http\Controllers;

use App\Enums\ContactPurpose;
use App\Http\Requests\ContactRequest;
use App\Mail\ContactRequestMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function store(ContactRequest $request): RedirectResponse
    {
        if ($request->isSpam()) {
            return to_route('contact')->with('contact_success', $this->successMessage());
        }

        $data = $request->validated();
        $purpose = ContactPurpose::from($data['purpose']);

        try {
            Mail::to(config('contact.mail_to'))->send(new ContactRequestMail(
                contactName: $data['name'],
                contactEmail: $data['email'],
                purposeLabel: $purpose->getLabel(),
                company: $data['company'] ?? null,
                phone: $data['phone'] ?? null,
                messageBody: $data['message'],
            ));
        } catch (Throwable $exception) {
            report($exception);

            return to_route('contact')
                ->withInput($request->safe()->except(['website']))
                ->with('contact_error', 'Uw bericht kon op dit moment niet worden verzonden. Probeer het later opnieuw.');
        }

        return to_route('contact')->with('contact_success', $this->successMessage());
    }

    private function successMessage(): string
    {
        return 'Bedankt voor uw bericht. We nemen zo snel mogelijk contact met u op. Op werkdagen reageren we doorgaans binnen 24 uur.';
    }
}
