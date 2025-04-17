<?php

namespace Marwanosama8\FullGoogleCalendar\Components\Infolist\Actions;

class EnableSyncAction extends Action
{
    public static function make(bool $shouldSync = false): \Filament\Infolists\Components\Actions\Action
    {
        return \Filament\Infolists\Components\Actions\Action::make('Enable Sync')
            ->label(__('full-google-calendar::full-google-calendar.action.enable-sync'))
            ->icon('heroicon-m-check')
            ->color('success')
            ->button()
            ->hidden($shouldSync)
            ->url(route('auth.google.calendar'));
    }
}
