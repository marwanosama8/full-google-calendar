<?php

namespace Marwanosama8\FullGoogleCalendar;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Marwanosama8\FullGoogleCalendar\Commands\FullGoogleCalendarCommand;
use Marwanosama8\FullGoogleCalendar\Commands\FullGoogleCalendarInstallationCommand;
use Marwanosama8\FullGoogleCalendar\Helper\Helper;
use Marwanosama8\FullGoogleCalendar\Livewire\EventInvitaionModal;
use Marwanosama8\FullGoogleCalendar\Livewire\GoogleCalendarSettings;
use Marwanosama8\FullGoogleCalendar\Livewire\InvitationStatusActions;
use Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\GoogleCalendarActions;
use Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\GoogleCalendarClient;
use Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\GoogleCalendarEventsActions;
use Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\GoogleCalendarService;
use Marwanosama8\FullGoogleCalendar\Testing\TestsFullGoogleCalendar;
use Illuminate\Contracts\Foundation\Application;

class FullGoogleCalendarServiceProvider extends PackageServiceProvider
{
    public static string $name = 'full-google-calendar';

    public static string $viewNamespace = 'full-google-calendar';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            // ->hasViews()
            ->hasCommands($this->getCommands());
        // // ->hasInstallCommand(function (InstallCommand $command) {
        //     $command
        //         ->publishConfigFile()
        //         ->publishMigrations()
        //         ->askToRunMigrations()
        //         ->askToStarRepoOnGitHub('marwanosama8/full-google-calendar');
        // });

        $configFileName = $package->shortName();


        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }

        $package->hasRoute('web');

        // $package->hasCommand(FullGoogleCalendarInstallationCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(GoogleCalendarClient::class, function () {
            return new GoogleCalendarClient();
        });

        $this->app->singleton(GoogleCalendarService::class, function ( Application $app) {
            $client = $app->make(GoogleCalendarClient::class);

            // You can handle the logic for client availability here instead of the widget itself
            if (!Helper::isGoogleServiceConfigAvaillable()) {
                return new GoogleCalendarService(null, null, null); // Return a null or fallback service
            }

            $googleService = new \Google\Service\Calendar($client->getClient());

            return new GoogleCalendarService(
                $client,
                new GoogleCalendarEventsActions($googleService),
                new GoogleCalendarActions($googleService)
            );
        });
    }

    public function packageBooted(): void
    {
        Livewire::component('google-calendar-settings', GoogleCalendarSettings::class);
        Livewire::component('event-invitaion-modal', EventInvitaionModal::class);
        Livewire::component('invitaion-status-actions', InvitationStatusActions::class);

        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/full-google-calendar/{$file->getFilename()}"),
                ], 'full-google-calendar-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsFullGoogleCalendar);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'marwanosama8/full-google-calendar';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            //     // AlpineComponent::make('full-google-calendar', __DIR__ . '/../resources/dist/components/full-google-calendar.js'),
            //     Css::make('full-google-calendar-styles', __DIR__ . '/../resources/dist/full-google-calendar.css'),
            //     Js::make('full-google-calendar-scripts', __DIR__ . '/../resources/dist/full-google-calendar.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            FullGoogleCalendarInstallationCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_full-google-calendar_table',
        ];
    }
}
