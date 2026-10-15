<?php

namespace App\Http\Requests;

use App\Enums\ContactPurpose;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(collect($this->only([
            'name',
            'email',
            'company',
            'phone',
            'message',
            'website',
        ]))->map(fn (mixed $value): mixed => is_string($value) ? trim($value) : $value)->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'purpose' => ['required', Rule::enum(ContactPurpose::class)],
            'company' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'min:8', 'max:32', 'regex:/^\+?[0-9][0-9\s().\/-]{6,30}[0-9]$/'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Het veld :attribute is verplicht.',
            'string' => 'Het veld :attribute moet tekst bevatten.',
            'email' => 'Vul een geldig e-mailadres in.',
            'min.string' => 'Het veld :attribute moet minimaal :min tekens bevatten.',
            'max.string' => 'Het veld :attribute mag maximaal :max tekens bevatten.',
            'purpose.enum' => 'Kies een geldige reden voor uw bericht.',
            'phone.regex' => 'Vul een geldig (internationaal) telefoonnummer in.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'naam',
            'email' => 'e-mailadres',
            'purpose' => 'reden van contact',
            'company' => 'bedrijfsnaam',
            'phone' => 'telefoonnummer',
            'message' => 'bericht',
        ];
    }

    public function isSpam(): bool
    {
        return $this->filled('website');
    }
}
