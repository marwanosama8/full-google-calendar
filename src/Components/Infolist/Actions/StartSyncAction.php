<?php

namespace Marwanosama8\FullGoogleCalendar\Components\Infolist\Actions;

use Filament\Notifications\Notification;
use Marwanosama8\FullGoogleCalendar\FullGoogleCalendarPlugin;
use Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\GoogleCalendarService;
use Filament\Notifications\Actions\Action as ActionAction;

class StartSyncAction extends Action
{
  public static function make(): \Filament\Infolists\Components\Actions\Action
  {
    return \Filament\Infolists\Components\Actions\Action::make('Start')
      ->requiresConfirmation()
      ->label(__('full-google-calendar::full-google-calendar.action.start'))
      ->modalHeading(__('full-google-calendar::full-google-calendar.action.start-sync'))
      ->color('warning')
      ->button()
      ->modalIcon('heroicon-o-exclamation-triangle')
      ->modalIconColor('warning')
      ->modalDescription(__('full-google-calendar::full-google-calendar.action.please-note'))
      ->icon('heroicon-m-arrow-path')
      ->color('success')
      ->action(fn () => self::startSync());
  }


  public static function startSync()
  {
    try {
      $service = app(GoogleCalendarService::class);

      $service->startSync();

      Notification::make()
        ->title(__('full-google-calendar::full-google-calendar.sync-done'))
        ->actions([
          ActionAction::make('Refresh page')
            ->label(__('action.refresh-page'))
            ->color('success')
            ->url(FullGoogleCalendarPlugin::get()->getCalendarPageUrl())
            ->button(),
        ])
        ->success()
        ->send();
    } catch (\Exception $th) {
      Notification::make()
        ->title(__('full-google-calendar::full-google-calendar.notification.sync-failed'))
        ->danger()
        ->send();
    }
  }
}
