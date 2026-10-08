<?php

namespace App\Http\Requests;

use App\Enums\ApplicationMode;
use App\Enums\CategoryType;
use App\Enums\SalaryBasis;
use App\Enums\VacancyStatus;
use App\Models\Vacancy;
use App\Rules\MeaningfulVacancyDescription;
use App\Support\Vacancies\VacancyDescription;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveVacancyPlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vacancy = $this->route('vacancy');

        return $this->user() !== null && (
            ! $vacancy instanceof Vacancy
            || ($vacancy->status === VacancyStatus::Draft && $vacancy->company()->where('user_id', $this->user()->id)->exists())
        );
    }

    protected function prepareForValidation(): void
    {
        $data = collect($this->only([
            'title',
            'description',
            'location',
            'application_email',
            'application_url',
        ]))->map(fn (mixed $value): mixed => is_string($value) ? trim($value) : $value)->all();

        if (is_string($data['description'] ?? null) && strlen($data['description']) <= 200_000) {
            $data['description'] = app(VacancyDescription::class)->sanitize($data['description']);
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $userId = $this->user()?->id ?? 0;

        return [
            'company_id' => [
                'required',
                'integer',
                Rule::exists('companies', 'id')->where(fn (Builder $query): Builder => $query
                    ->where('user_id', $userId)
                    ->whereNull('deleted_at')),
            ],
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['bail', 'required', 'string', 'max:100000', new MeaningfulVacancyDescription],
            'location' => ['required', 'string', 'max:255'],
            'application_mode' => ['required', Rule::enum(ApplicationMode::class)],
            'application_email' => ['nullable', 'required_if:application_mode,'.ApplicationMode::Email->value, 'email:rfc', 'max:255'],
            'application_url' => ['nullable', 'required_if:application_mode,'.ApplicationMode::External->value, 'url:http,https', 'max:2048'],
            'salary_min' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'salary_max' => ['nullable', 'integer', 'min:1', 'max:1000000', 'gte:salary_min'],
            'salary_basis' => ['nullable', Rule::enum(SalaryBasis::class)],
            'deadline_at' => ['nullable', 'date', 'after_or_equal:today'],
            'employment_type_category_id' => $this->categoryRules(CategoryType::employment_type),
            'workplace_category_id' => $this->categoryRules(CategoryType::workplace),
            'sector_category_id' => $this->categoryRules(CategoryType::sector),
            'function_area_category_id' => $this->categoryRules(CategoryType::function_area),
            'experience_category_id' => $this->categoryRules(CategoryType::experience),
            'qualification_category_id' => $this->categoryRules(CategoryType::qualification),
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Het veld :attribute is verplicht.',
            'company_id.exists' => 'Kies een bedrijf dat bij uw account hoort.',
            'application_email.required_if' => 'Vul een sollicitatie-e-mailadres in.',
            'application_url.required_if' => 'Vul een externe sollicitatielink in.',
            'salary_max.gte' => 'Het maximale salaris moet gelijk aan of hoger dan het minimale salaris zijn.',
        ];
    }

    public function attributes(): array
    {
        return [
            'company_id' => 'bedrijf',
            'title' => 'functietitel',
            'description' => 'vacaturebeschrijving',
            'location' => 'locatie',
            'application_mode' => 'sollicitatiemethode',
            'salary_min' => 'minimumsalaris',
            'salary_max' => 'maximumsalaris',
            'salary_basis' => 'salarisbasis',
            'deadline_at' => 'sollicitatiedeadline',
        ];
    }

    /** @return array<int, mixed> */
    private function categoryRules(CategoryType $type): array
    {
        return [
            'nullable',
            'integer',
            Rule::exists('categories', 'id')->where(fn (Builder $query): Builder => $query
                ->where('type', $type->value)
                ->whereNull('deleted_at')),
        ];
    }
}
