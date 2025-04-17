<?php

namespace Marwanosama8\FullGoogleCalendar\Resources;

use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Marwanosama8\FullGoogleCalendar\Models\EventCategory;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Guava\FilamentIconPicker\Forms\IconPicker;

class EventCategoryResource extends Resource
{
    protected static ?string $model = EventCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-plus';
    protected static ?string $navigationGroup = 'Admin';

    public static function getModelLabel(): string
    {
        return __('admin.eventCategory');
    }


    public static function getPluralModelLabel(): string
    {
        return __('admin.eventCategories');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('value')
                    ->required()
                    ->columnSpanFull(),

                Select::make('color')
                    ->options([
                        'blue'   => 'Blue',
                        'green'  => 'Green',
                        'yellow' => 'Yellow',
                        'red'    => 'Red',
                        'pink'   => 'Pink',
                        'indigo' => 'Indigo',
                        'purple' => 'Purple',
                        'teal'   => 'Teal',
                        'orange' => 'Orange',
                        'gray'   => 'Gray',
                    ])
                    ->required(),

                TagsInput::make('tags'),

                Toggle::make('show_in_overview')
                    ->default(1)
                    ->label(__('calendar.show_in_overview')),

                Toggle::make('default')
                    ->default(1)
                    ->dehydrated(1)
                    ->disabled(function ($state) {
                        if ((bool) $state === true) {
                            return EventCategory::where('default', 1)->count() <= 1;
                        }
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('value'),

                TextColumn::make('color'),

                TextColumn::make('tags')
                    ->badge(),

                ToggleColumn::make('default')
                    ->disabled(function ($state) {
                        if ((bool) $state === true) {
                            return EventCategory::where('default', 1)->count() <= 1;
                        }
                    }),
                

                ToggleColumn::make('show_in_overview')
                    ->label(__('calendar.show_in_overview')),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEventCategories::route('/'),
            'create' => Pages\CreateEventCategory::route('/create'),
            'edit'   => Pages\EditEventCategory::route('/{record}/edit'),
        ];
    }
}
