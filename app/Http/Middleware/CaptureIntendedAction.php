<?php

namespace App\Http\Middleware;

use App\Services\ContextPreservationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureIntendedAction
{
    public function __construct(
        protected ContextPreservationService $contextService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Capture referral code from URL
        if ($request->has('ref')) {
            $this->contextService->captureReferralCode($request->input('ref'));
        }

        // Capture UTM parameters
        if ($request->has('utm_source')) {
            $this->contextService->captureUtmParams([
                'source' => $request->input('utm_source'),
                'medium' => $request->input('utm_medium'),
                'campaign' => $request->input('utm_campaign'),
            ]);
        }

        return $next($request);
    }
}
