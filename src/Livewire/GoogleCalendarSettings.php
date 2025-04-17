<?php

namespace Marwanosama8\FullGoogleCalendar\Livewire;

use Livewire\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Livewire\Attributes\Locked;
use Filament\Notifications\Actions\Action as ActionAction;
use Filament\Support\Enums\FontFamily;
use Marwanosama8\FullGoogleCalendar\Components\Infolist\Actions\CreateNewCalendarAction;
use Marwanosama8\FullGoogleCalendar\Components\Infolist\Actions\EnableSyncAction;
use Marwanosama8\FullGoogleCalendar\Components\Infolist\Actions\StartSyncAction;
use Marwanosama8\FullGoogleCalendar\Components\Infolist\Actions\UnlinkAccountAction;
use Marwanosama8\FullGoogleCalendar\FullGoogleCalendarPlugin;
use Marwanosama8\FullGoogleCalendar\Resources\EventCategoryResource;
use Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\GoogleCalendarActions;
use Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\GoogleCalendarClient;
use Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\GoogleCalendarEventsActions;
use Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\GoogleCalendarService;
use Marwanosama8\FullGoogleCalendar\Traits\MountSettingsPage;

class GoogleCalendarSettings extends Component implements HasForms, HasInfolists
{
    use InteractsWithInfolists;
    use InteractsWithForms;
    use MountSettingsPage;

    #[Locked]
    protected GoogleCalendarClient $client;
    private GoogleCalendarService $service;

    public $shouldSync = false;
    public $hasGoogleCalendar = false;
    public $calendarId;
    public string $calendarPageUrl;

    protected $listeners = ['needRefresh' => '$refresh'];

    public function boot(GoogleCalendarClient $client, GoogleCalendarService $service)
    {
        $this->client = $client;
        $this->service = $service;
        $this->calendarPageUrl = FullGoogleCalendarPlugin::get()->getCalendarPageUrl();
    }


    public function settingInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state([
                'Google Calendar Sync'    => $this->shouldSync ? __('full-google-calendar::full-google-calendar.enabled') : __('full-google-calendar::full-google-calendar.disabled'),
                'Your Google Calendar Id' => $this->hasGoogleCalendar ? $this->calendarId : __('full-google-calendar::full-google-calendar.you-should-create-new-calendar-to-start-sync'),
                'Sync Now'                => __('full-google-calendar::full-google-calendar.last-sync').$this->client->getClientLastSync(),
                'Google Email'            => $this->client->getClientGoogleEmail(),
            ])
            ->schema([
                TextEntry::make('Google Calendar Sync')
                    ->weight(FontWeight::Bold)
                    ->label(__('full-google-calendar::full-google-calendar.google-calendar-sync'))
                    ->size(TextEntry\TextEntrySize::Large)
                    ->badge()
                    ->color($this->shouldSync ? 'success' : 'danger')
                    ->hintActions([
                        EnableSyncAction::make($this->shouldSync),
                        UnlinkAccountAction::make($this->shouldSync, $this->service, $this->calendarPageUrl),
                    ]),
                TextEntry::make('Your Google Calendar Id')
                    ->label(__('full-google-calendar::full-google-calendar.your-google-calendar-id'))
                    ->color('gray')
                    ->hidden(! $this->shouldSync)
                    ->weight(FontWeight::Bold)
                    ->fontFamily(FontFamily::Mono)
                    ->color($this->hasGoogleCalendar ? 'success' : 'danger')
                    ->limit(fn () => $this->hasGoogleCalendar ? 24 : 255)
                    ->hintAction(
                        CreateNewCalendarAction::make($this->hasGoogleCalendar)
                    ),
                TextEntry::make('Sync Now')
                    ->color('gray')
                    ->label(__('full-google-calendar::full-google-calendar.sync-now'))
                    ->hidden($this->service->shouldSync() == false || ! $this->hasGoogleCalendar)
                    ->weight(FontWeight::Bold)
                    ->limit(25)
                    ->hintAction(
                        StartSyncAction::make()
                    ),
                TextEntry::make('Google Email')
                    ->color('success')
                    ->label(__('full-google-calendar::full-google-calendar.google-email'))
                    ->hidden($this->service->shouldSync() == false || ! $this->hasGoogleCalendar)
                    ->weight(FontWeight::Bold)
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->copyMessageDuration(1500),
            ]);
    }
    public function render()
    {
        return view('full-google-calendar::livewire.google-calendar-settings');
    }

    public function refreshComponent()
    {
        return $this->dispatch('needRefresh');
    }
}
