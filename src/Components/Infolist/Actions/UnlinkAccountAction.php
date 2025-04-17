<?php

namespace Marwanosama8\FullGoogleCalendar\Components\Infolist\Actions;

use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as ActionAction;

class UnlinkAccountAction extends Action
{
    public static function make(bool $shouldSync = false, object $service = null, string $calendarPageUrl = ''): \Filament\Infolists\Components\Actions\Action
    {
        return \Filament\Infolists\Components\Actions\Action::make('Unlink Account')
            ->label(__('full-google-calendar::full-google-calendar.action.unlink-account'))
            ->requiresConfirmation()
            ->modalHeading(__('full-google-calendar::full-google-calendar.unlink-account'))
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('danger')
            ->color('success')
            ->button()
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->hidden(!$shouldSync)
            ->action(function () use ($service, $calendarPageUrl) {
                try {
                    $service->revokeclient();
                    Notification::make()
                        ->title(__('full-google-calendar::full-google-calendar.notification.unlink-done'))
                        ->actions([
                            ActionAction::make('Refresh page')
                                ->label('action.refresh-page')
                                ->color('success')
                                ->url($calendarPageUrl)
                                ->button(),
                        ])
                        ->success()
                        ->send();
                } catch (\Exception $th) {
                    Notification::make()
                        ->title(__('full-google-calendar::full-google-calendar.notification.unlink-failed'))
                        ->success()
                        ->send();
                }
            });
    }
}
