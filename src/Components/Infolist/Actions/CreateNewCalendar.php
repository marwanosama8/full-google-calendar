<?php

namespace Marwanosama8\FullGoogleCalendar\Components\Infolist\Actions;

class CreateNewCalendar extends Action
{
    public static function make(bool $shouldSync = false, object $service = null, string $calendarPageUrl = ''): \Filament\Infolists\Components\Actions\Action
    {
        return \Filament\Infolists\Components\Actions\Action::make('Create New Calendar')
        ->icon('heroicon-m-plus')
        ->label(__('full-google-calendar::full-google-calendar.action.create-new-calendar'))
        ->color('success')
        ->button()
        ->hidden($this->hasGoogleCalendar)
        ->action(function () {
            $this->createNewCalendar();

            $this->refreshComponent();
        });
    }

    private function createNewCalendar()
    {
        $this->service->createInitCalendar();
        try {
            Notification::make()
                ->title(__('full-google-calendar::full-google-calendar.calendar-created'))
                ->success()
                ->send();
        } catch (\Exception $th) {
            Notification::make()
                ->title(__('full-google-calendar::full-google-calendar.calendar-failed'))
                ->success()
                ->send();
        }
    }

}
