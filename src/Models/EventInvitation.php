<?php

namespace Marwanosama8\FullGoogleCalendar\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventInvitation extends Model
{
    use HasFactory;

    public $fillable = [
        'user_id',
        'event_id',
        'status',
        'note'
    ];

    protected $casts = [
        'event_id' => 'string',
    ];

    /**
     * Get the events that owns the EventInvitation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function events()
    {
        return $this->belongsTo(Event::class,);
    }

    /**
     * The users that belong to the EventInvitation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'event_invitations', 'event_id', 'user_id')
            ->withPivot('status', 'note')
            ->withTimestamps();
    }
}
