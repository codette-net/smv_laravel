<?php

namespace App\Filament\Resources\BlogPosts\Tables;

use App\Enums\BlogPostStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BlogPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            SpatieMediaLibraryImageColumn::make('featured_media')->label('Afbeelding')->collection('featured'),
            TextColumn::make('title')->label('Titel')->searchable()->sortable()->wrap(),
            TextColumn::make('status')->label('Status')->badge()->sortable(),
            TextColumn::make('published_at')->label('Publiceren op')->dateTime()->sortable(),
            TextColumn::make('updated_at')->label('Bijgewerkt')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('created_at')->label('Aangemaakt')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            SelectFilter::make('status')->label('Status')->options(BlogPostStatus::class),
            TernaryFilter::make('publication')->label('Gepubliceerd of gepland')
                ->queries(
                    true: fn ($query) => $query->where('status', BlogPostStatus::Published->value)->where('published_at', '<=', now()),
                    false: fn ($query) => $query->where('status', BlogPostStatus::Published->value)->where('published_at', '>', now()),
                ),
            TrashedFilter::make(),
        ])->recordActions([
            ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]),
        ]);
    }
}
