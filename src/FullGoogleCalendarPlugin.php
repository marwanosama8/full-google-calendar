<?php

namespace Marwanosama8\FullGoogleCalendar;

use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Marwanosama8\FullGoogleCalendar\Pages\Invitations;
use Marwanosama8\FullGoogleCalendar\Resources\EventCategoryResource;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class FullGoogleCalendarPlugin extends FilamentFullCalendarPlugin
{

    public string $calendarPageUrl = '';

    public function getId(): string
    {
        return 'full-google-calendar';
    }

    public function register(Panel $panel): void
    {
        $panel
            // ->path('')
            ->resources([
                EventCategoryResource::class
            ])
            ->pages([
                Invitations::class,
            ]);
        // ->plugin(FullGoogleCalendarPlugin::make());
    }

    public function boot(Panel $panel): void
    {
        Filament::getPanel($panel->getId())?->plugin(\Saade\FilamentFullCalendar\FilamentFullCalendarPlugin::make());
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function calendarPageUrl(string $calendarPageUrl): static
    {
        $this->calendarPageUrl = $calendarPageUrl;

        return $this;
    }

    public function getCalendarPageUrl(): string
    {
        return   $this->calendarPageUrl;
    }
}
