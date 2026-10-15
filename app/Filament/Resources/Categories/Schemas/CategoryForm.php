<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Enums\CategoryType;
use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Naam')
                    ->required(),
                TextInput::make('slug')
                    ->label('Slug')
                    ->helperText('Blijft na creatie stabiel voor filters en toekomstige URL’s.'),
                Select::make('type')
                    ->label('Type')
                    ->options(CategoryType::class)
                    ->required()
                    ->default(CategoryType::function_area->value)
                    ->live(),
                Select::make('parent_id')
                    ->label('Bovenliggende categorie')
                    ->relationship('parent', 'name', fn (Builder $query, callable $get): Builder => $query->where('type', $get('type')))
                    ->getOptionLabelFromRecordUsing(fn (Category $record): string => $record->name)
                    ->searchable()
                    ->preload()
                    ->helperText('Alleen categorieën van hetzelfde type zijn beschikbaar.'),
            ]);
    }
}
