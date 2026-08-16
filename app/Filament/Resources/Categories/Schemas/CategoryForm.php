<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Enums\CategoryType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Select::make('type')
                    ->options(CategoryType::class)
                    ->required()
                    ->default(CategoryType::vacancy_category->value),
            ]);
    }
}
