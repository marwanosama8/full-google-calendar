<?php

namespace Marwanosama8\FullGoogleCalendar\Traits;

use Marwanosama8\FullGoogleCalendar\FullGoogleCalendarPlugin;

trait MountSettingsPage
{
    public function mount()
    {
        try {
            if ($this->client->error !== null) {
                $this->setCalendarState(false, false, null);
                return;
            }

            if ($this->client->isUserHasGoogleCalendar()) {
                $this->initializeGoogleCalendar();
            } else {
                $this->setCalendarState(true, false, null);
            }
        } catch (\Exception $e) {
            $this->setCalendarState(false, false, null);
        }
    }

    private function initializeGoogleCalendar(): void
    {
        $profileId = $this->client->getClientGoogleCalendarProfileId();
        $calendarAvailable = $this->service->checkIfClientCalendarIsAvailable($profileId);

        $this->setCalendarState(
            $this->service->shouldSync(),
            $calendarAvailable !== null,
            $calendarAvailable !== null ? $profileId : null
        );
    }

    private function setCalendarState(bool $shouldSync, bool $hasGoogleCalendar, ?string $calendarId): void
    {
        $this->shouldSync = $shouldSync;
        $this->hasGoogleCalendar = $hasGoogleCalendar;
        $this->calendarId = $calendarId;
    }
}
