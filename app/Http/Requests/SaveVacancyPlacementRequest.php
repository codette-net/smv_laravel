<?php

namespace App\Http\Requests;

use App\Enums\ApplicationMode;
use App\Enums\CategoryType;
use App\Enums\VacancyStatus;
use App\Models\Vacancy;
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
        $this->merge(collect($this->only([
            'title',
            'description',
            'location',
            'application_email',
            'application_url',
        ]))->map(fn (mixed $value): mixed => is_string($value) ? trim($value) : $value)->all());
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
            'description' => ['required', 'string', 'min:50', 'max:20000'],
            'location' => ['required', 'string', 'max:255'],
            'application_mode' => ['required', Rule::enum(ApplicationMode::class)],
            'application_email' => ['nullable', 'required_if:application_mode,'.ApplicationMode::Email->value, 'email:rfc', 'max:255'],
            'application_url' => ['nullable', 'required_if:application_mode,'.ApplicationMode::External->value, 'url:http,https', 'max:2048'],
            'salary_min' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'max:1000000', 'gte:salary_min'],
            'deadline_at' => ['nullable', 'date', 'after_or_equal:today'],
            'employment_type_category_id' => $this->categoryRules(CategoryType::employment_type),
            'workplace_category_id' => $this->categoryRules(CategoryType::workplace),
            'sector_category_id' => $this->categoryRules(CategoryType::sector),
            'function_area_category_id' => $this->categoryRules(CategoryType::function_area),
            'experience_category_id' => $this->categoryRules(CategoryType::experience),
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Het veld :attribute is verplicht.',
            'company_id.exists' => 'Kies een bedrijf dat bij uw account hoort.',
            'description.min' => 'Geef een vacaturebeschrijving van minimaal :min tekens.',
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
