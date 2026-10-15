<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Support\Companies\CompanyDescription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployerCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = $this->route('company');

        return $company instanceof Company
            && $this->user()?->can('update', $company) === true
            && $company->user_id === $this->user()->id;
    }

    protected function prepareForValidation(): void
    {
        $data = collect($this->only([
            'name',
            'tagline',
            'description',
            'email',
            'phone',
            'website',
            'location',
            'linkedin_url',
            'facebook_url',
            'instagram_url',
        ]))->map(fn (mixed $value): mixed => is_string($value) ? trim($value) : $value)->all();

        if (is_string($data['description'] ?? null) && strlen($data['description']) <= 200_000) {
            $data['description'] = app(CompanyDescription::class)->sanitize($data['description']);
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        /** @var Company $company */
        $company = $this->route('company');

        return [
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'email' => ['nullable', 'email:rfc', 'max:255', Rule::unique('companies', 'email')->ignore($company)],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'linkedin_url' => ['nullable', 'url:http,https', 'max:255'],
            'facebook_url' => ['nullable', 'url:http,https', 'max:255'],
            'instagram_url' => ['nullable', 'url:http,https', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'bedrijfsnaam',
            'tagline' => 'korte introductie',
            'description' => 'bedrijfsomschrijving',
            'email' => 'e-mailadres',
            'phone' => 'telefoonnummer',
            'website' => 'website',
            'location' => 'locatie',
            'linkedin_url' => 'LinkedIn-URL',
            'facebook_url' => 'Facebook-URL',
            'instagram_url' => 'Instagram-URL',
            'logo' => 'logo',
            'cover' => 'omslagafbeelding',
        ];
    }
}
