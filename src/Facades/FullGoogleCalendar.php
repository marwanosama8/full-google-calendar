<?php

namespace Marwanosama8\FullGoogleCalendar\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Marwanosama8\FullGoogleCalendar\FullGoogleCalendar
 */
class FullGoogleCalendar extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Marwanosama8\FullGoogleCalendar\FullGoogleCalendar::class;
    }
}
