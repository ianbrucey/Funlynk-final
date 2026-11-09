<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeSocketId
{
    /**
     * Drop a malformed X-Socket-Id header before it can reach the
     * broadcaster.
     *
     * Livewire copies Echo.socketId() into this header on every request.
     * When Echo has not connected (realtime down or unconfigured),
     * socketId() is undefined and the header arrives as the literal
     * string "undefined". Laravel passes the header verbatim to the
     * broadcaster as the toOthers() exclusion, and Pusher rejects it
     * ("Invalid socket ID undefined"), failing the whole request.
     *
     * A real socket id is only ever "<digits>.<digits>", so anything
     * else is removed and the broadcast simply goes to everyone;
     * clients de-duplicate by message id.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $socketId = $request->header('X-Socket-Id');

        if ($socketId !== null && preg_match("/^\d+\.\d+$/", $socketId) !== 1) {
            $request->headers->remove('X-Socket-Id');
        }

        return $next($request);
    }
}
