<?php

namespace Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar;

use Marwanosama8\FullGoogleCalendar\Models\Event;
use Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\Actions\FromAppToGoogle;
use Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\Actions\FromGoogleToApp;
use Carbon\Carbon;
use Exception;

class GoogleCalendarService
{
    protected $client;
    protected $events;
    protected $calendar;
    protected $user;

    public function __construct(?GoogleCalendarClient $client, ?GoogleCalendarEventsActions $events, ?GoogleCalendarActions $calendar, $user = null)
    {
        $this->client = $client;
        $this->events = $events;
        $this->calendar = $calendar;
        $this->user = $user ?? auth()->user();
    }

    public function checkIfClientCalendarIsAvailable($calendarId)
    {
        return $this->calendar->findCalendarById($calendarId);
    }

    public function createInitCalendar()
    {
        try {
            $calendarName = config('full-google-calendar.calendar_name');
            $similarCalendarId = $this->calendar->findSimilarCalendarsByName($calendarName);

            $calendarId = $similarCalendarId ?? $this->calendar->createInitCalendar();

            Event::where('organizer', $this->user->id)
                ->whereNotNull('google_event_id')
                ->update(['google_event_id' => null]);

            $this->user->googleCalendarProfile()->update(['google_calendar_id' => $calendarId]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function shouldSync()
    {
        if ($this->client->error) {
            return false;
        }

        $hasGoogleCalendarId = $this->client->isUserHasGoogleCalendar();
        if (!$hasGoogleCalendarId) {
            return false;
        }

        return !$this->checkIfClientCalendarIsAvailable($hasGoogleCalendarId);
    }

    public function startSync()
    {
        if ($this->shouldSync()) {
            $this->twoWaySync();

            $this->user->googleCalendarProfile()->update(['last_google_calendar_sync' => Carbon::now()]);
        } else {
            throw new Exception("Something went wrong, please sign in to your Google Calendar first.");
        }
    }

    private function twoWaySync()
    {
        $this->oneWaySync();
        $this->syncFromGoogleCalendar();
    }

    private function syncFromGoogleCalendar()
    {
        try {
            $calendarId = $this->client->getClientGoogleCalendarProfileId();
            $googleEvents = $this->fetchGoogleCalendarEvents($calendarId);
            $appEvents = Event::where('organizer', $this->user->id)->get()->toArray();

            $this->events->syncDeletedEvents($googleEvents, $appEvents, $calendarId);

            foreach ($googleEvents as $googleEvent) {
                $this->createOrUpdateEventToApp($googleEvent);
            }
        } catch (Exception $e) {
            throw new Exception('Google Calendar sync error: ' . $e->getMessage() . ' | Line: ' . $e->getLine() . '  | ' . $e->getFile());
        }
    }

    private function fetchGoogleCalendarEvents($calendarId)
    {
        return $this->events->fetchGoogleCalendarEvents($calendarId);
    }

    private function createOrUpdateEventToApp($event)
    {
        return $this->events->createOrUpdate(new FromGoogleToApp(), $this->client->getClientGoogleCalendarProfileId(), $event, $this->user);
    }

    private function oneWaySync()
    {
        try {
            $userEvents = Event::where('organizer', $this->user->id)->get();
            foreach ($userEvents as $event) {
                $this->createOrUpdateEventToGoogle($event);
            }
        } catch (Exception $e) {
            throw new Exception('One way sync error: ' . $e->getMessage() . ' | Line: ' . $e->getLine() . '  | ' . $e->getFile());
        }
    }

    public function createOrUpdateEventToGoogle(Event $event)
    {
        if ($this->shouldSync()) {
            $this->events->createOrUpdate(new FromAppToGoogle(), $this->client->getClientGoogleCalendarProfileId(), $event, $this->user);
        } else {
            throw new Exception("The event didn't synchronize with your Google Calendar, please sign in to your Google Calendar then start sync from settings.");
        }
    }

    public function revokeClient()
    {
        if ($this->shouldSync()) {
            $this->client->unlinkGoogleAccount();
        } else {
            throw new Exception("Couldn't unlink the account, please try again later.");
        }
    }
}
