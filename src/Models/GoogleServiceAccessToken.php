<?php

namespace Marwanosama8\FullGoogleCalendar\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleServiceAccessToken extends Model
{
    protected $fillable = [
        'access_token',
        'refresh_token',
        'expires_at',
        'tokenable_id',
        'tokenable_type'
    ];

    public function tokenable()
    {
        return $this->morphTo();
    }
}
