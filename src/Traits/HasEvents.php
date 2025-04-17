<?php

namespace Marwanosama8\FullGoogleCalendar\Traits;

use Marwanosama8\FullGoogleCalendar\Models\Event;
use Marwanosama8\FullGoogleCalendar\Models\FullGoogleCalendarProfile;
use Marwanosama8\FullGoogleCalendar\Models\GoogleServiceAccessToken;

trait HasEvents
{
    public function eventInvitations()
    {
        return $this->belongsToMany(Event::class, 'event_invitations', 'user_id', 'event_id')
            ->withPivot('status', 'note')
            ->withTimestamps();
    }

    public function googleCalendarProfile()
    {
        return $this->hasOne(FullGoogleCalendarProfile::class);
    }

    public function googleServiceAccessTokens()
    {
        return $this->morphMany(GoogleServiceAccessToken::class, 'tokenable');
    }

    public function getGoogleEmailAttribute()
    {
        return $this->googleCalendarProfile->google_email ?? '';
    }
}
