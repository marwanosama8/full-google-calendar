<?php

namespace Marwanosama8\FullGoogleCalendar\Helper;

class Helper
{
    public static function isGoogleServiceConfigAvaillable()
    {
        $requiredKeys = [
            config('full-google-calendar.calendar_name'),
            config('full-google-calendar.scope'),
            config('full-google-calendar.credntials_json'),
            config('full-google-calendar.access_type'),
            config('full-google-calendar.default_category'),
        ];


        if (in_array(null, $requiredKeys)) {
            $isServiceAvailable = false;
        } else {
            $isServiceAvailable = true;
        }

        return $isServiceAvailable;
    }
}
