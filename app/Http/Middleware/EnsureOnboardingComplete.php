<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If user hasn't completed onboarding, redirect them
        if ($user && !$user->hasCompletedOnboarding()) {
            // Allow access to these specific routes
            $allowedRoutes = ['onboarding', 'profile.edit', 'logout'];
            
            if (!in_array($request->route()->getName(), $allowedRoutes)) {
                return redirect()->route('onboarding')
                    ->with('info', 'Please complete your profile setup to access this feature.');
            }
        }

        return $next($request);
    }
}
