<?php

namespace Marwanosama8\FullGoogleCalendar\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $bypassSyncUpdatedAt = false;

    protected $fillable = [
        'id',
        'subject',
        'body',
        'start',
        'end',
        'participants',
        'category',
        'attachments',
        'organizer',
        'event_leader',
        'meeting_url',
        'event_origin',
        'google_event_id',
        'isAllDay',
        'sync_updated_at'
    ];


    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'participants' => 'array',
        'attachments' => 'array',
        'sync_updated_at' => 'datetime'
    ];

    public function event_category()
    {
        return $this->belongsTo(EventCategory::class, 'category', 'id');
    }

    public function invitedUsers()
    {
        return $this->belongsToMany(User::class, 'event_invitations', 'event_id', 'user_id')
            ->withPivot('status', 'note')
            ->withTimestamps();
    }

    /**
     * Get the organizer that owns the Event
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function getorganizer()
    {
        return $this->belongsTo(User::class, 'organizer');
    }

    public function getBodyPreviewAttribute()
    {
        return Str::limit(strip_tags($this->body), 50);
    }

    public function bypassSyncUpdatedAt(bool $value = true)
    {
        $this->bypassSyncUpdatedAt = $value;
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if ($model->bypassSyncUpdatedAt) {
                return; // Skip updating sync_updated_at
            }
            $model->id = Str::uuid();
        });

        static::saved(function ($model) {
            if ($model->bypassSyncUpdatedAt) {
                return; // Skip updating sync_updated_at
            }
            if (count($model->participants) > 0) {
                $authUserId = $model->organizer;

                $filteredParticipants = array_filter($model->participants, function ($participant) use ($authUserId) {
                    return $participant != (string)$authUserId;
                });
                if (count($filteredParticipants) > 0) {
                    $model->invitedUsers()->sync($filteredParticipants);
                }
            }
            if (!in_array((string)$model->organizer, $model->participants)) {
                $newArray = array_merge($model->participants, [(string)$model->organizer]);
                $model->participants = $newArray;
            }
        });
        static::updated(function ($model) {
            if ($model->bypassSyncUpdatedAt) {
                return; // Skip updating sync_updated_at
            }

            if (count($model->participants) > 0) {
                $authUserId = $model->organizer;

                $filteredParticipants = array_filter($model->participants, function ($participant) use ($authUserId) {
                    return $participant != (string)$authUserId;
                });

                $model->invitedUsers()->sync($filteredParticipants);

                $model->updateQuietly([
                    'sync_updated_at' => Carbon::now()->toDateTimeString(),
                ]);
            }
        });
    }
}
