<?php

namespace App\Imports\Validation;

use App\Enums\ImportEmployerIdentityType;
use App\Imports\Mapping\NormalizedVacancyData;
use App\Models\ImportSource;
use App\Models\ImportSourceEmployer;
use InvalidArgumentException;

class EmployerResolver
{
    /** @return array{company_id?: int, mapping_id?: int, unresolved?: bool, code?: string, message?: string} */
    public function resolve(ImportSource $source, NormalizedVacancyData $data): array
    {
        if (! $source->usesSourceScopedEmployers()) {
            return $source->company_id
                ? ['company_id' => $source->company_id]
                : ['unresolved' => true, 'code' => 'owner_missing', 'message' => 'De importbron heeft geen eigenaar-bedrijf.'];
        }

        $externalId = $this->exact($data->get('employer.external_id'));
        $name = $this->exact($data->get('employer.name'));
        $url = $this->exact($data->get('employer.source_url'));

        try {
            if ($externalId !== null) {
                $identityType = ImportEmployerIdentityType::ProviderId;
                $identityKey = ImportSourceEmployer::providerKey($externalId);
            } else {
                $identityType = ImportEmployerIdentityType::ReviewedComposite;
                $identityKey = ImportSourceEmployer::compositeKey($name, $url);
            }
        } catch (InvalidArgumentException) {
            return ['unresolved' => true, 'code' => 'employer_identity_missing', 'message' => 'Werkgeveridentiteit ontbreekt of is ongeldig.'];
        }

        $matches = $source->employerMappings()
            ->where('identity_type', $identityType->value)
            ->where('identity_key', $identityKey)
            ->where('is_active', true)
            ->whereHas('company', fn ($query) => $query->whereNull('deleted_at'))
            ->limit(2)
            ->get();

        if ($matches->count() !== 1) {
            return [
                'unresolved' => true,
                'code' => $matches->isEmpty() ? 'employer_unresolved' : 'employer_ambiguous',
                'message' => $matches->isEmpty() ? 'Werkgever is nog niet gekoppeld.' : 'Werkgeverkoppeling is niet eenduidig.',
            ];
        }

        return ['company_id' => $matches->first()->company_id, 'mapping_id' => $matches->first()->id];
    }

    private function exact(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
