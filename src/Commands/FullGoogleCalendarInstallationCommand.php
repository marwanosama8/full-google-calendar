<?php

namespace Marwanosama8\FullGoogleCalendar\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use Marwanosama8\FullGoogleCalendar\Models\FullGoogleCalendarProfile;
use Marwanosama8\FullGoogleCalendar\Models\GoogleServiceAccessToken;
use Marwanosama8\FullGoogleCalendar\Models\EventCategory;

class FullGoogleCalendarInstallationCommand extends Command
{
    public $signature = 'full-google-calendar:install';
    public $description = 'Install the Full Google Calendar plugin and set up necessary tables';

    public function handle(): int
    {
        // Start installation process
        $this->info('Starting Full Google Calendar installation...');

        // Migrate the required tables
        $this->info('Migrating tables...');
        $this->call('migrate');

        // Ensure required tables exist before proceeding
        if (!Schema::hasTable('users') || !Schema::hasTable('full_google_calendar_profiles')) {
            $this->error('Tables "users" or "full_google_calendar_profiles" do not exist.');
            return self::FAILURE;
        }

        // Seed Full Google Calendar profiles for all users
        $this->info('Seeding Full Google Calendar Profiles for all users...');
        DB::transaction(function () {
            User::all()->each(function ($user) {
                GoogleServiceAccessToken::updateOrCreate(
                    ['tokenable_id' => $user->id],
                    [
                        'tokenable_type' => User::class,
                        'tokenable_id' => $user->id,
                    ]
                );

                FullGoogleCalendarProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'user_id' => $user->id,
                        'google_calendar_id' => null,
                        'last_google_calendar_sync' => null,
                    ]
                );
            });
        });

        $this->info('Full Google Calendar Profiles seeded successfully.');

        // Seed Event Categories
        if (!Schema::hasTable('event_categories')) {
            $this->error('Table "event_categories" does not exist. Skipping event categories seeding.');
        } else {
            $this->info('Seeding Event Categories...');
            $categories = [
                ['Meeting', 'blue'],
                ['Appointment', 'green'],
                ['Reminder', 'yellow'],
                ['Holiday', 'red'],
                ['Birthday', 'pink'],
                ['Conference', 'indigo'],
                ['Webinar', 'purple'],
                ['Workshop', 'teal'],
                ['Networking', 'orange'],
                ['Travel', 'gray'],
                ['Deadline', 'red'],
                ['Sports', 'green'],
                ['Medical', 'pink'],
                ['Family', 'blue'],
                ['Online Event', 'purple'],
                ['Offline Event', 'gray'],
            ];

            DB::transaction(function () use ($categories) {
                foreach ($categories as $values) {
                    EventCategory::firstOrCreate(
                        ['value' => $values[0]],
                        [
                            'id' => Str::uuid(),
                            'color' => $values[1],
                        ]
                    );
                }
            });

            $this->info('Event Categories seeded successfully.');
        }

        $this->comment('Installation completed successfully.');
        return self::SUCCESS;
    }
}
