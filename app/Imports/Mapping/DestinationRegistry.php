<?php

namespace App\Imports\Mapping;

use InvalidArgumentException;

class DestinationRegistry
{
    /** @return array<string, DestinationDefinition> */
    public function all(): array
    {
        $definitions = [];
        foreach ([
            ['vacancy.title', 'Titel', 'Vacature', true], ['vacancy.description', 'Omschrijving', 'Vacature', false], ['vacancy.location', 'Locatie', 'Vacature', false], ['vacancy.published_at', 'Publicatiedatum', 'Vacature', false], ['vacancy.deadline_at', 'Reactietermijn', 'Vacature', false], ['vacancy.expires_at', 'Verloopdatum', 'Vacature', false], ['vacancy.application_url', 'Sollicitatielink', 'Vacature', false], ['vacancy.application_email', 'Sollicitatie e-mail', 'Vacature', false], ['vacancy.salary_min', 'Salaris vanaf', 'Compensatie', false], ['vacancy.salary_max', 'Salaris tot', 'Compensatie', false], ['vacancy.salary_currency', 'Salarisvaluta', 'Compensatie', false], ['vacancy.salary_period', 'Salarisperiode', 'Compensatie', false], ['vacancy.rate_min', 'Tarief vanaf', 'Compensatie', false], ['vacancy.rate_max', 'Tarief tot', 'Compensatie', false], ['vacancy.rate_currency', 'Tariefvaluta', 'Compensatie', false], ['vacancy.rate_period', 'Tariefperiode', 'Compensatie', false], ['company.name', 'Bedrijfsnaam', 'Bedrijf', false], ['company.email', 'Bedrijfse-mail', 'Bedrijf', false], ['company.phone', 'Bedrijfstelefoon', 'Bedrijf', false], ['company.website', 'Website', 'Bedrijf', false], ['company.logo_url', 'Logo-URL', 'Bedrijf', false], ['tags', 'Tags', 'Taxonomie', false], ['source_reference', 'Bronreferentie', 'Identiteit', true],
        ] as [$key, $label, $group, $required]) {
            $definitions[$key] = new DestinationDefinition($key, $label, $group, ['direct', 'default', 'combine', 'transform'], $required);
        }
        foreach (['employment_type', 'workplace', 'sector', 'function_area', 'experience'] as $type) {
            $definitions["taxonomy.{$type}"] = new DestinationDefinition("taxonomy.{$type}", ucfirst(str_replace('_', ' ', $type)), 'Taxonomie', ['direct', 'transform'], false);
        }

        return $definitions;
    }

    public function get(string $key): DestinationDefinition
    {
        return $this->all()[$key] ?? throw new InvalidArgumentException("Unknown import destination [{$key}].");
    }
}
