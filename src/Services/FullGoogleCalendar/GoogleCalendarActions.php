<?php

namespace Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar;

use Exception;
use Google\Service\Calendar;
use Google\Service\Calendar\Calendar as CalendarModel;

class GoogleCalendarActions
{
    public $service;

    public function __construct(Calendar $service)
    {
        $this->service = $service;
    }


    public function findCalendarById($calendarId)
    {
        try {
            $pageToken = null;
            do {
                $optParams = ['pageToken' => $pageToken];
                $calendarList = $this->service->calendarList->listCalendarList($optParams);
                foreach ($calendarList->getItems() as $calendarListEntry) {
                    if ($calendarListEntry->getId() === $calendarId) {
                        return $calendarListEntry->getId();
                    }
                }

                $pageToken = $calendarList->getNextPageToken();
            } while ($pageToken);

            return null; // Calendar not found
        } catch (\Exception $th) {
            return new Exception('Error by server, refresh token');
        }
    }

    public function findSimilarCalendarsByName($calendarName)
    {
        $pageToken = null;

        do {
            $optParams = ['pageToken' => $pageToken];
            $calendarList = $this->service->calendarList->listCalendarList($optParams);
            foreach ($calendarList->getItems() as $calendarListEntry) {
                $summary = $calendarListEntry->getSummary();
                if (stripos($summary, $calendarName) !== false) {
                    return $calendarListEntry->getId();
                }
            }

            $pageToken = $calendarList->getNextPageToken();
        } while ($pageToken);

        return null; // Calendar not found
    }

    public function createInitCalendar()
    {
        $calendar = new CalendarModel();

        $calendar->setSummary(config('full-google-calendar.calendar_name'));
        $calendar->setTimeZone(config('app.timezone'));

        $createdCalendar = $this->service->calendars->insert($calendar);

        return $createdCalendar->getId();
    }
}
