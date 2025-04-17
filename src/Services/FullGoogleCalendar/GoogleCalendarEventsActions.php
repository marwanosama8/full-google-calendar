<?php

namespace Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar;

use Marwanosama8\FullGoogleCalendar\Models\Event;
use Exception;
use Google\Service\Calendar;


class GoogleCalendarEventsActions
{
    public $service;
    public $client;

    public function __construct(Calendar $service)
    {
        $this->service = $service;
    }

    public function createOrUpdate($action, $calendarId, $event,$user)
    {
        return $action->createOrUpdate($calendarId, $event, $this->service,$user);
    }


    public function fetchGoogleCalendarEvents($calendarId)
    {
        try {
            // retrieve events from the specified calendar
            $events = $this->service->events->listEvents($calendarId);
            $eventsArray = [];
            while (true) {
                foreach ($events->getItems() as $event) {
                    array_push($eventsArray, $event);
                }
                $pageToken = $events->getNextPageToken();
                if ($pageToken) {
                    $optParams = array('pageToken' => $pageToken);
                    $events = $this->service->events->listEvents($calendarId, $optParams);
                } else {
                    break;
                }
            }

            return $eventsArray;
        } catch (\Exception $e) {
            throw new Exception('Error fetching Google Calendar events: ' . $e->getMessage());
        }
    }

    public function syncDeletedEvents($googleEvents, $appEvents, $calendarId)
    {
        $deletedEvents = $this->findDeletedEvents($appEvents, $googleEvents);
        foreach ($deletedEvents as $event) {
            Event::find($event['id'])->delete();
        }
    }
    
    protected function findDeletedEvents($appEvents, $googleEvents)
    {
        $deletedEvents = [];

        // compare events between google calendar and app calendar
        foreach ($appEvents as $appEvent) {
            $found = false;
            foreach ($googleEvents as $googleEvent) {
                if ($this->eventsAreEqual($appEvent, $googleEvent)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                // event exists in google calendar but not in app calendar
                $deletedEvents[] = $appEvent;
            }
        }

        return $deletedEvents;
    }
    protected function eventsAreEqual($event1, $event2)
    {
        return $event1['google_event_id'] == $event2['id'];
    }
}
