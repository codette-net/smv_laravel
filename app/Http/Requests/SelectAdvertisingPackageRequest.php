<?php

namespace App\Http\Requests;

use App\Enums\AdvertisingPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectAdvertisingPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['package' => ['required', Rule::enum(AdvertisingPackage::class)]];
    }

    public function messages(): array
    {
        return [
            'package.required' => 'Kies een pakket om verder te gaan.',
            'package.enum' => 'Kies een geldig pakket.',
        ];
    }
}
