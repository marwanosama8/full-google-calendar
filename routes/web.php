<?php

use Illuminate\Support\Facades\Route;
use Marwanosama8\FullGoogleCalendar\Http\Controllers\GoogleCalendarController;

Route::get('/auth/google', [GoogleCalendarController::class, 'redirectToGoogle'])->name('auth.google.calendar');

Route::get('/auth/google/calendar/callback', [GoogleCalendarController::class, 'handleGoogleCallback'])->name('auth.google.calendar.callback');
