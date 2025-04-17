<?php

namespace Marwanosama8\FullGoogleCalendar\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Marwanosama8\FullGoogleCalendar\Resources\EventCategoryResource;
use Filament\Actions;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class ListEventCategories extends ListRecords
{
    protected static string $resource = EventCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Action::make('editGoogleEmail')
            // ->color('info')
            // ->icon('heroicon-m-pencil-square')
            // ->label(__('full-google-calendar::full-google-calendar.google-email'))
            //     ->form([
            //         TextInput::make('google_email')
            //             ->label('Edit Google Email')
            //             ->email()
            //             ->columnSpanFull()
            //             ->default(auth()->user()->googleCalendarProfile?->google_email)
            //             ->required(),
            //     ])
            //     ->action(function (array $data): void {
            //         auth()->user()->googleCalendarProfile()->update([
            //             'google_email' => $data['google_email']
            //         ]);
            //     }),
            Actions\CreateAction::make(),
        ];
    }
}
