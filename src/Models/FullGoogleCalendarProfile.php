<?php

namespace Marwanosama8\FullGoogleCalendar\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class FullGoogleCalendarProfile extends Model
{
    use HasFactory;

    public $timestamps = false;


    protected $fillable = [
        'user_id',
        'google_calendar_id',
        'last_google_calendar_sync',
        'google_email'
    ];


    public function scopeShowInOverview(Builder $query): void
    {
        $query->where('show_in_overview', 1);
    }


    protected function getLastGoogleCalendarSyncAttribute($value): string
    {
        if (!is_null($value)) {
            return Carbon::parse($value)->diffForHumans();
        }
        return 'Never';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isUserHasGoogleCalendar()
    {
        return is_null($this->google_calendar_id) ? false : true;
    }
}
