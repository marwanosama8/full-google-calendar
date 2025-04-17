<?php

namespace Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\Actions;

use Marwanosama8\FullGoogleCalendar\Models\Event;
use Marwanosama8\FullGoogleCalendar\Models\EventCategory;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Google\Service\Calendar;
use Google\Service\Calendar as GoogleCalendar;
use Marwanosama8\FullGoogleCalendar\Models\FullGoogleCalendarProfile;
use Stevebauman\Hypertext\Transformer;

class FromGoogleToApp
{
    public function createOrUpdate($calendarId, $googleEvent, Calendar $service, $user)
    {

        if ($this->hasAppEventId($googleEvent)) {
            $appEvent = Event::where('id', $googleEvent['extendedProperties']['private']['app_event_id'])->first();
            if (! is_null($appEvent)) {
                if (! $this->isEventTimestampsAreEqual($appEvent, $googleEvent)) {
                    if ($this->isGoogleEventRecent($googleEvent, $appEvent)) {
                        // update app event
                        $this->updateAppEvent($googleEvent, $user);
                    } else {
                        // update google event
                        $this->updateGoogleEvent($service, $calendarId, $googleEvent, $appEvent);
                    }
                }
            } else {
                // delete google event
                $this->deleteGoogleEvent($service, $calendarId, $googleEvent);
            }
        } else {
            // insert google event into app
            $newEvent = $this->insertGoogleEvent($googleEvent, $user);
            // update google event private property and insert app_event_id
            $updatedEvent = $this->updateGoogleEventAppId($service, $calendarId, $googleEvent, $newEvent);
            $this->syncAppEventUpdatedAt($newEvent->id, $updatedEvent->getUpdated());
        }
    }

    public function hasAppEventId($event)
    {
        return isset($event['extendedProperties']['private']['app_event_id']);
    }

    public function isGoogleEventRecent($googleEvent, $appEvent)
    {

        $googleUpdated = Carbon::parse($googleEvent->getUpdated())->timestamp;
        $appUpdated = $appEvent->sync_updated_at->timestamp;

        return $googleUpdated > $appUpdated;
    }

    public function updateAppEvent($googleEvent, $currentUser)
    {

        try {
            $start = is_null($googleEvent->start->date) ? Carbon::parse($googleEvent->start->dateTime) : Carbon::parse($googleEvent->start->date);
            $end = is_null($googleEvent->end->date) ? Carbon::parse($googleEvent->end->dateTime) : Carbon::parse($googleEvent->end->date);
            $eventData = [
                'subject' => $googleEvent->summary,
                'body'    => $googleEvent->description,
                'start'   => $start,
                'end'     => $end,
            ];

            $participants = [];

            // map participant data
            foreach ($googleEvent->attendees as $attendee) {
                $participants[] = [
                    'email'  => $attendee->email,
                    'status' => match ($attendee->responseStatus) {
                        'needsAction' => 'pending',
                        'accepted' => 'accepted',
                        'declined' => 'declined',
                        default => 'pending',
                    },
                ];
            }
            // find the event by its google_event_id
            $event = Event::where('google_event_id', $googleEvent->id)->first();
            if ($event) {
                $userIds = [];
                $syncedParticipants = [];
                // sync participants
                foreach ($participants as $participant) {
                    $user = FullGoogleCalendarProfile::where('google_email', $participant['email'])->first()->user;
                    if ($user) {
                        $userIds[] = $user->id;
                        $syncedParticipants[$user->id] = ['status' => $participant['status']];
                    }
                }

                $eventData['participants'] = array_map('strval', $userIds);
                $event->invitedUsers()->sync($syncedParticipants);
                // update event
                if (! in_array((string) $currentUser->id, $eventData['participants'])) {
                    $newArray = array_merge($eventData['participants'], [(string) $currentUser->id]);
                    $eventData['participants'] = $newArray;
                }
                $event->update($eventData);

                // sync app event updated at timestamp
                $this->syncAppEventUpdatedAt($event->id, $googleEvent->getUpdated());
            }
        } catch (\Exception $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function updateGoogleEvent($service, $calendarId, $googleEvent, $appEvent)
    {
        $eventData = [
            'summary'            => $appEvent->subject,
            'description'        => $this->sanitizeDescription($appEvent->body),
            'extendedProperties' => [
                'private' => [
                    'app_event_id' => $appEvent->id,
                ],
            ],
        ];


        if ($appEvent->isAllDay) {
            $eventData['start'] = [
                'date' => Carbon::parse($appEvent->start)->format('Y-m-d'),
            ];
            $eventData['end'] = [
                'date' => Carbon::parse($appEvent->end)->format('Y-m-d'),
            ];
        } else {
            $eventData['start'] = [
                'dateTime' => Carbon::parse($appEvent->start)->format('Y-m-d\TH:i:s'),
                'timeZone' => config('app.timezone'),
            ];
            $eventData['end'] = [
                'dateTime' => Carbon::parse($appEvent->end)->format('Y-m-d\TH:i:s'),
                'timeZone' => config('app.timezone'),
            ];
        }

        $invitedUsers = $appEvent->invitedUsers;

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
                'displayName'    => $invitedUser->name,
                'email'          => $invitedUser->google_email,
                'responseStatus' => $mappedResponseStatus,
            ];
        }

        $eventData['attendees'] = $attendees;



        try {
            $updatedEvent = new GoogleCalendar\Event($eventData);

            $updatedEvent = $service->events->update($calendarId, $googleEvent->id, $updatedEvent);

            // sync app event updated at timestamp
            $this->syncAppEventUpdatedAt($appEvent->id, $updatedEvent->getUpdated());

            return $updatedEvent;
        } catch (\Exception $e) {
            throw new Exception('Error updating Google Calendar event: '.$e->getMessage());
        }
    }



    public function insertGoogleEvent($googleEvent, $appUser)
    {

        try {
            $start = is_null($googleEvent->start->date) ? Carbon::parse($googleEvent->start->dateTime) : Carbon::parse($googleEvent->start->date)->startOfDay();
            $end = is_null($googleEvent->end->date) ? Carbon::parse($googleEvent->end->dateTime) : Carbon::parse($googleEvent->end->date)->startOfDay();
            $appEvent = new Event();
            $appEvent->subject = $googleEvent->summary;
            $appEvent->body = $googleEvent->description;
            $appEvent->start = $start;
            $appEvent->end = $end;
            $appEvent->google_event_id = $googleEvent->id;
            //new 
            $appEvent->organizer = $appUser->id;
            $appEvent->attachments = [];


            $participantsIds = [];
            // iterate over the attendees array
            if (count($googleEvent->attendees) > 0) {
                foreach ($googleEvent->attendees as $attendee) {
                    $user = FullGoogleCalendarProfile::where('google_email', $attendee->email)->first()->user;
                    if ($user) {
                        array_push($participantsIds, $user->id);
                    }
                }
            }

            $appEvent->participants = array_map('strval', $participantsIds);
            $category = $this->findCategoryIdByEventSummary($googleEvent->summary);
            $appEvent->category = $category;
            $appEvent->organizer = $appUser->id;
            if (! in_array((string) $appUser->id, $appEvent->participants)) {
                $newArray = array_merge($appEvent->participants, [(string) $appUser->id]);
                $appEvent->participants = $newArray;
            }
            $appEvent->save();

            $appEvent->invitedUsers()->sync($participantsIds);


            return $appEvent;
        } catch (\Exception $th) {
            throw new Exception($th->getMessage());
        }
    }


    public function deleteGoogleEvent($service, $calendarId, $googleEvent)
    {
        try {
            // delete the google calendar event
            $service->events->delete($calendarId, $googleEvent->id);
        } catch (\Exception $e) {
            throw new Exception('Error deleting Google Calendar event: '.$e->getMessage());
        }
    }

    private function findCategoryIdByEventSummary($summary)
    {
        $categories = EventCategory::all();
        
        foreach ($categories as $category) {
            if (! is_null($category->tags)) {
                foreach ($category->tags as $tag) {
                    if (str_starts_with($tag, '#')) {
                        if (preg_match('/\b' . preg_quote($tag, '/') . '\b/', $summary)) {
                            return $category->id;
                        }
                    }
                }
            }
        }
        

        return $categories->where('default', 1)->first()->id ?? $categories->first()->id;
    }

    protected function sanitizeDescription($description)
    {
        return $description ? (new Transformer)->keepNewLines()->toText($description) : null;
    }

    private function updateGoogleEventAppId($service, $calendarId, $googleEvent, $newEvent)
    {
        $extendedProperties = new GoogleCalendar\EventExtendedProperties();
        $extendedProperties->setPrivate(['app_event_id' => $newEvent->id]);
        $googleEvent->extendedProperties = $extendedProperties;
        $service->events->update($calendarId, $googleEvent->id, $googleEvent);
        return $googleEvent;
    }

    private function isEventTimestampsAreEqual($appEvent, $googleEvent)
    {
        $appEventTimestamp = $appEvent?->sync_updated_at?->timestamp;
        $googleEventTimestamp = Carbon::parse($googleEvent->getUpdated())->timestamp;

        return $appEventTimestamp == $googleEventTimestamp;
    }

    private function syncAppEventUpdatedAt($appEventId, $googleEventTimestamp)
    {
        $event = Event::find($appEventId);
        // Bypass the observer
        $event->bypassSyncUpdatedAt();

        // Update the field
        $event->update([
            'sync_updated_at' => Carbon::parse($googleEventTimestamp)->toDateTimeString(),
        ]);

    }
}