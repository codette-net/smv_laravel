<?php

namespace App\Enums;

enum ContactPurpose: string
{
    case General = 'general';
    case Advertising = 'advertising';
    case Vacancy = 'vacancy';
    case Technical = 'technical';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::General => 'Algemene vraag',
            self::Advertising => 'Vraag over adverteren',
            self::Vacancy => 'Vraag over een vacature',
            self::Technical => 'Technische vraag',
            self::Other => 'Anders',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $purpose): array => [$purpose->value => $purpose->getLabel()])
            ->all();
    }
}
