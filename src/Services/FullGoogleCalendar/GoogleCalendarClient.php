<?php

namespace Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar;

use Marwanosama8\FullGoogleCalendar\Helper\Helper;
use Exception;
use Google\Client as GoogleClient;
use Google\Service\Oauth2;

class GoogleCalendarClient
{
    public $client;
    public $user;
    public $error = null;
    public $userGoogleKey;

    public function __construct($user = null)
    {
        if (Helper::isGoogleServiceConfigAvaillable()) {
            $this->client = new GoogleClient();
            $this->client->setAuthConfig(config('full-google-calendar.credntials_json'));

            $this->user = $user ?? auth()->user();

            $this->setToken();
            $this->setClient();
            $this->checkTokenExpiration();
        } else {
            $this->client = null;
            $this->error = __('full-google-calendar::full-google-calendar.service-not-availlabe');
        }
    }



    private function setClient(): void
    {

        try {
            $this->client->setAccessToken([
                'access_token' => $this->userGoogleKey['access_token'],
                'expires_in' => time() + 86400,
                'refresh_token' => $this->userGoogleKey['refresh_token']

            ]);

            $this->updateGoogleEmail();
        } catch (\Exception $th) {
            $this->error = __('full-google-calendar::full-google-calendar.client_not_set');
        }
    }


    private function setToken()
    {

        if ($this->user) {
            $accessToken = $this->user->googleServiceAccessTokens()->first();
            if ($accessToken) {
                $this->userGoogleKey = [
                    'access_token' => $accessToken->access_token,
                    'expires_at' => $accessToken->expires_at,
                    'refresh_token' => $accessToken->refresh_token
                ];
            } else {
                $this->error = __('full-google-calendar::full-google-calendar.token_not_found');
            }
        } else {
            $tokenSession = session()->get('google_key');
            if ($tokenSession) {
                $this->userGoogleKey = $tokenSession;
            } else {
                $this->error = __('full-google-calendar::full-google-calendar.token_not_found');
            }
        }
    }



    public function checkTokenExpiration()
    {

        if (is_null($this->client)) {
            $this->error = 'Client not set';
        }
        if (!is_null($this->userGoogleKey)) {
            $isTokenExpired = $this->client->isAccessTokenExpired();
            if (!$isTokenExpired) {
                $newtoken =  $this->client->fetchAccessTokenWithRefreshToken($this->userGoogleKey['access_token']);
                if (!array_key_exists('error', $newtoken)) {
                    $this->updateAccessToken($newtoken);
                } else {
                    $this->error = __('full-google-calendar::full-google-calendar.client_not_set');
                }
            }
        } else {
            $this->error = __('full-google-calendar::full-google-calendar.client_not_set');
        }
    }


    private function updateAccessToken($token)
    {
        $user = $this->user;
        session()->put('google_key', $token);
        if ($token === null || !isset($token['access_token'], $token['expires_in'])) {
            return;
        }

        $accessToken = $this->user->googleServiceAccessTokens()->updateOrCreate(
            ['tokenable_id' => $user->id],
            [
                'access_token' => $token['access_token'],
                'expires_at' => now()->addSeconds($token['expires_in']),
                'refresh_token' => $token['refresh_token'] ?? null
            ]
        );
    }

    public function getClient()
    {
        return $this->client;
    }


    public function getClientLastSync()
    {
        return $this->user->googleCalendarProfile->last_google_calendar_sync;
    }

    public function isUserHasGoogleCalendar()
    {
        return $this->user->googleCalendarProfile->isUserHasGoogleCalendar();
    }

    public function getClientGoogleEmail()
    {
        return $this->user->googleCalendarProfile->google_email;
    }

    public function getClientGoogleCalendarProfileId()
    {
        return $this->user->googleCalendarProfile->google_calendar_id;
    }


    public function unlinkGoogleAccount()
    {
        try {
            // Revoke Google access token
            $this->revokeAccessToken();

            // Clear stored credentials
            session()->forget('google_key');

            // Optionally update user status
            // auth()->user()->googleCalendarProfile->update(['linked_to_google' => false]);

        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function revokeAccessToken()
    {
        $accessToken = $this->userGoogleKey['access_token'];

        $this->getClient()->revokeToken($accessToken);
    }

    private function updateGoogleEmail()
    {
        $oauth2Service = new Oauth2($this->client);
        $userInfo = $oauth2Service->userinfo->get();
        if (!$this->user->googleCalendarProfile->google_email == $userInfo->email) {
            $this->user->googleCalendarProfile()->update([
                'google_email' => $userInfo->email
            ]);
        }
    }
}
