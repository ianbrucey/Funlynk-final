<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\SocialShare;
use App\Models\User;
use Illuminate\Support\Str;

class SocialShareService
{
    /**
     * Record a social share
     *
     * @param  Activity  $activity
     * @param  string  $platform  'instagram', 'facebook', 'twitter', 'whatsapp', 'copy_link'
     * @param  User|null  $user
     * @return SocialShare
     */
    public function recordShare(Activity $activity, string $platform, ?User $user = null): SocialShare
    {
        return SocialShare::create([
            'activity_id' => $activity->id,
            'user_id' => $user?->id,
            'platform' => $platform,
            'referral_code' => $this->generateReferralCode(),
        ]);
    }

    /**
     * Generate shareable URL with referral tracking
     *
     * @param  Activity  $activity
     * @param  string  $platform
     * @param  User|null  $user
     * @return string
     */
    public function generateShareUrl(Activity $activity, string $platform, ?User $user = null): string
    {
        $share = $this->recordShare($activity, $platform, $user);

        $baseUrl = route('events.public', $activity->slug);

        $params = [
            'ref' => $share->referral_code,
            'utm_source' => $platform,
            'utm_medium' => 'social',
            'utm_campaign' => 'event_share',
        ];

        return $baseUrl.'?'.http_build_query($params);
    }

    /**
     * Track click on shared link
     *
     * @param  string  $referralCode
     * @return void
     */
    public function trackClick(string $referralCode): void
    {
        SocialShare::where('referral_code', $referralCode)
            ->increment('clicks');
    }

    /**
     * Track conversion from shared link
     *
     * @param  string  $referralCode
     * @return void
     */
    public function trackConversion(string $referralCode): void
    {
        SocialShare::where('referral_code', $referralCode)
            ->increment('conversions');
    }

    /**
     * Get share analytics for an activity
     *
     * @param  Activity  $activity
     * @return array
     */
    public function getShareAnalytics(Activity $activity): array
    {
        $shares = SocialShare::where('activity_id', $activity->id)->get();

        return [
            'total_shares' => $shares->count(),
            'total_clicks' => $shares->sum('clicks'),
            'total_conversions' => $shares->sum('conversions'),
            'by_platform' => $shares->groupBy('platform')->map(function ($platformShares) {
                return [
                    'shares' => $platformShares->count(),
                    'clicks' => $platformShares->sum('clicks'),
                    'conversions' => $platformShares->sum('conversions'),
                    'conversion_rate' => $platformShares->sum('clicks') > 0
                        ? round(($platformShares->sum('conversions') / $platformShares->sum('clicks')) * 100, 2)
                        : 0,
                ];
            }),
        ];
    }

    /**
     * Generate unique referral code
     *
     * @return string
     */
    protected function generateReferralCode(): string
    {
        do {
            $code = 'SH'.Str::random(8);
        } while (SocialShare::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Get social share by referral code
     *
     * @param  string  $referralCode
     * @return SocialShare|null
     */
    public function getShareByReferralCode(string $referralCode): ?SocialShare
    {
        return SocialShare::where('referral_code', $referralCode)->first();
    }

    /**
     * Get top performing shares for an activity
     *
     * @param  Activity  $activity
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTopShares(Activity $activity, int $limit = 10)
    {
        return SocialShare::where('activity_id', $activity->id)
            ->orderByDesc('conversions')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get();
    }
}

