<?php

namespace Marwanosama8\FullGoogleCalendar\Http\Controllers;

use Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\GoogleAuthService;
use Illuminate\Http\Request;
use Marwanosama8\FullGoogleCalendar\FullGoogleCalendarPlugin;

class GoogleCalendarController extends Controller
{
    protected $googleAuthService;
    protected $plugin;

    public function __construct(GoogleAuthService $googleAuthService)
    {
        $this->middleware('web');
        $this->googleAuthService = $googleAuthService;
        $this->plugin = FullGoogleCalendarPlugin::get();
    }

    public function redirectToGoogle()
    {
        return redirect()->to($this->googleAuthService->getAuthUrl());
    }

    public function handleGoogleCallback(Request $request)
    {
        $code = $request->get('code');
        if ($code) {
            $accessToken = $this->googleAuthService->fetchAccessToken($code);
            $this->updateAccessToken($accessToken);
            session()->put('google_key', $accessToken);
            return redirect($this->plugin->getCalendarPageUrl());
        } else {
            return redirect()->route('home')->with('error', 'Failed to authenticate.');
        }
    }

    private function updateAccessToken($token)
    {
        $user = auth()->user();
        $accessToken = $user->googleServiceAccessTokens()->updateOrCreate(
            ['tokenable_id' => $user->id],
            [
                'access_token' => $token['access_token'],
                'expires_at' => now()->addSeconds($token['expires_in']),
                'refresh_token' => $token['refresh_token'] ?? null
            ]
        );
    }
}
