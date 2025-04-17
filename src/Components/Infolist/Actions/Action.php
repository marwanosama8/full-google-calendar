<?php

namespace Marwanosama8\FullGoogleCalendar\Components\Infolist\Actions;

abstract class Action
{
    abstract public static function make(): \Filament\Infolists\Components\Actions\Action;
}
