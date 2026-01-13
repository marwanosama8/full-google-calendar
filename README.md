# Filament Google Calendar Sync

A Laravel package that syncs **Google Calendar events** into your **Filament admin panel**, allowing you to view and manage calendar events directly inside Filament.

This package is designed to be reusable and easy to integrate, providing seamless synchronization between Google Calendar and your Laravel application using the Google Calendar API.

---

## Features

- 🔐 Google OAuth authentication
- 🔄 Sync Google Calendar events into your database
- 📅 Display events inside Filament (Resources / Widgets)
- ⏱ Supports scheduled syncing via Laravel Scheduler
- 🧩 Clean, extensible package architecture

---

## Installation

Install the package via Composer:

```bash
composer require vendor/filament-google-calendar
Publish and run the migrations:
```

```bash
php artisan vendor:publish --tag="filament-google-calendar-migrations"
php artisan migrate
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="filament-google-calendar-config"
```
(Optional) Publish views if customization is needed:

```bash
php artisan vendor:publish --tag="filament-google-calendar-views"
```
Configuration
After publishing, you will find the config file at:

```php
config/filament-google-calendar.php
```
Example configuration:

```php
return [

    'client_id' => env('GOOGLE_CALENDAR_CLIENT_ID'),

    'client_secret' => env('GOOGLE_CALENDAR_CLIENT_SECRET'),

    'redirect_uri' => env('GOOGLE_CALENDAR_REDIRECT_URI'),

    'calendar_id' => 'primary',

    'sync' => [
        'enabled' => true,
        'interval' => 'hourly',
    ],

];
```
Make sure to add the required environment variables to your .env file.

## Usage
Sync Google Calendar Events
You can manually trigger a sync using:

```bash
php artisan google-calendar:sync
```
Or schedule it inside app/Console/Kernel.php:

```php
$schedule->command('google-calendar:sync')->hourly();
```
Filament Integration
Once synced, events will be available inside Filament via:

Filament Resource (Events)

Dashboard widgets (optional)

Calendar views (if enabled)

Testing
Run the test suite using:

```bash
composer test
```
Changelog
Please see CHANGELOG for details about recent changes.

Contributing
Contributions are welcome!
Please review CONTRIBUTING for details.
