<?php

namespace Marwanosama8\FullGoogleCalendar\Components\Infolist\Actions;

use Filament\Notifications\Notification;
use Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\GoogleCalendarService;

class CreateNewCalendarAction extends Action
{
  public static function make(bool $hasGoogleCalendar = false): \Filament\Infolists\Components\Actions\Action
  {
    return \Filament\Infolists\Components\Actions\Action::make('Create New Calendar')
      ->icon('heroicon-m-plus')
      ->label(__('full-google-calendar::full-google-calendar.action.create-new-calendar'))
      ->color('success')
      ->button()
      ->hidden($hasGoogleCalendar)
      ->action(function () {
        $this->createNewCalendar();
      });
  }

  private function createNewCalendar(GoogleCalendarService $service)
  {
    $service->createInitCalendar();
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
