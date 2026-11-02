<?php

namespace App\Http\Middleware;

use App\Models\Activity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Canonical front door for the UUID event URL (events.show).
 *
 * That URL is the in-app event page for signed-in users. Guests who
 * land on it (shared links, emails, crawlers) belong on the public
 * slug page (events.public), so:
 *
 *  - public event  -> 301 to the canonical slug URL, preserving the
 *                     query string (referral codes / UTM parameters
 *                     are captured by the public page instead);
 *  - private event -> 404, so non-public events never leak by URL.
 *
 * Signed-in users pass through untouched.
 */
class RedirectGuestsToPublicEvent
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            return $next($request);
        }

        $activity = $request->route('activity');

        if (is_string($activity)) {
            $activity = Activity::find($activity);
        }

        if (! $activity instanceof Activity) {
            return $next($request);
        }

        if (! $activity->is_public) {
            abort(404);
        }

        $url = route('events.public', $activity->slug);

        if ($query = $request->getQueryString()) {
            $url .= '?'.$query;
        }

        return redirect()->to($url, 301);
    }
}
