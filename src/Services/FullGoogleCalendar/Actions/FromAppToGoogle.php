<?php

namespace Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\Actions;

use Marwanosama8\FullGoogleCalendar\Models\Event;
use Carbon\Carbon;
use Exception;
use Google\Service\Calendar;
use Google\Service\Calendar as GoogleCalendar;
use Stevebauman\Hypertext\Transformer;

class FromAppToGoogle
{
    public function createOrUpdate($calendarId, $event, Calendar $service)
    {
        $data = $this->mapToGoogleCalendarFromApplicationEvent($event);

        // check if the event already exists in the calendar
        $eventId = $event['google_event_id'] ?? null;
        // if the event id is provided, return only id , or insert it
        if ($eventId) {
            return $eventId;
        } else {
            $newEvent = $service->events->insert($calendarId, $data);

            $event->update(['google_event_id' => $newEvent->getUpdated()]);

            // update app event updated at timesatamp to match with google event
            $this->updateAppEventUpdatedAt($event['id'], $newEvent->getUpdated());

            return $newEvent->getId();
        }
    }

    public function mapToGoogleCalendarFromApplicationEvent($event)
    {
        try {
            $eventData = [
                'summary' => $event['subject'],
                'description' => $this->sanitizeDescription($event['body']),
                'extendedProperties' => [
                    'private' => [
                        'app_event_id' => $event['id']
                    ]
                ]
            ];


            if ($event['isAllDay']) {
                $eventData['start'] = [
                    'date' => Carbon::parse($event['start'])->startOfDay()->format('Y-m-d'),
                ];
                $eventData['end'] = [
                    'date' => Carbon::parse($event['end'])->endOfDay()->format('Y-m-d'),
                ];
            } else {
                $eventData['start'] = [
                    'dateTime' => Carbon::parse($event['start'])->format('Y-m-d\TH:i:s'),
                    'timeZone' => config('app.timezone'),
                ];
                $eventData['end'] = [
                    'dateTime' => Carbon::parse($event['end'])->format('Y-m-d\TH:i:s'),
                    'timeZone' => config('app.timezone'),
                ];
            }


            $invitedUsers = $event->invitedUsers;

            $attendees = [];
            foreach ($invitedUsers as $invitedUser) {
                $responseStatus = $invitedUser->pivot->status;

                $mappedResponseStatus = match ($responseStatus) {
                    'pending' => 'needsAction',
                    'accepted' => 'accepted',
                    'declined' => 'declined',
                    'missed' => 'declined',
                    default => 'needsAction',
                };

                $attendees[] = [
                    'displayName' => $invitedUser->name,
                    'email' => $invitedUser->google_email,
                    'responseStatus' => $mappedResponseStatus,
                ];

            }

            $eventData['attendees'] = $attendees;

            return $event = new GoogleCalendar\Event($eventData);
        } catch (\Exception $e) {
            throw new Exception('Error with creating google event object: ' . $e->getMessage());
        }
    }


    protected function sanitizeDescription($description)
    {
        return $description ? (new Transformer)->keepNewLines()->toText($description) : null;
    }

    private function updateAppEventUpdatedAt($appEvnetId, $googleEventTimestamp)
    {
        // get event 
        $event = Event::find($appEvnetId);

        $event->sync_updated_at = Carbon::parse($googleEventTimestamp)->toDateTimeString();

        $event->save();
    }
}
