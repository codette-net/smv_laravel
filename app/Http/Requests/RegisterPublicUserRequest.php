<?php

namespace App\Http\Requests;

use App\Enums\RegistrationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class RegisterPublicUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employerRegistration = $this->registrationContext() === RegistrationContext::Employer;

        return [
            'context' => ['nullable', new Enum(RegistrationContext::class)],
            'name' => ['required', 'string', 'max:120'],
            'company_name' => [$employerRegistration ? 'required' : 'nullable', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    public function registrationContext(): ?RegistrationContext
    {
        $context = $this->input('context');

        return is_string($context) ? RegistrationContext::tryFrom($context) : null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('context') !== null && $this->registrationContext() === null) {
            $this->merge(['context' => null]);
        }
    }

    public function messages(): array
    {
        return [
            'required' => 'Het veld :attribute is verplicht.',
            'email' => 'Vul een geldig e-mailadres in.',
            'email.unique' => 'Er bestaat al een account met dit e-mailadres.',
            'password.confirmed' => 'De wachtwoordbevestiging komt niet overeen.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'naam',
            'company_name' => 'bedrijfsnaam',
            'email' => 'e-mailadres',
            'password' => 'wachtwoord',
        ];
    }
}
