<?php

namespace Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar;

use Google\Client as GoogleClient;
use Google\Service\Oauth2;

class GoogleAuthService
{
    protected $client;

    public function __construct(GoogleClient $client)
    {
        $this->client = $client;
        $this->client->setAuthConfig(config('full-google-calendar.credntials_json'));
        $this->client->setRedirectUri(route('auth.google.calendar.callback'));
        $this->client->addScope(config('full-google-calendar.scope'));
        $this->client->addScope('https://www.googleapis.com/auth/userinfo.email');
        $this->client->setAccessType(config('full-google-calendar.access_type'));

    }

    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    public function fetchAccessToken($code)
    {
        return $this->client->fetchAccessTokenWithAuthCode($code);
    }
}
