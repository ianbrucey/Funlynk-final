<?php

namespace App\Poster\Services\Posters;

use App\Poster\Contracts\PlatformPoster;
use App\Poster\Enums\Platform;
use App\Poster\Exceptions\NotConfiguredException;
use App\Poster\Models\BundlePlatformPost;
use App\Poster\Support\PublishResult;

abstract class BasePoster implements PlatformPoster
{
    abstract public function platform(): Platform;

    public function adaptCopy(string $caption): string
    {
        $max = $this->capabilities()->maxTextLength;

        if ($max !== null && mb_strlen($caption) > $max) {
            return rtrim(mb_substr($caption, 0, $max - 1)).'…';
        }

        return $caption;
    }

    /**
     * Phase 1 publish(): always refuses, with a precise reason. There is no
     * code path in this phase that reports a successful post.
     *
     * @throws NotConfiguredException
     */
    public function publish(BundlePlatformPost $post): PublishResult
    {
        $platform = $this->platform();
        $account = $post->platformAccount;

        if ($account === null || $account->access_token === null) {
            throw new NotConfiguredException(
                "{$platform->label()} is not configured: no connected platform account with a token. ".
                'Connect the account first (see README account checklist); tokens are stored encrypted on platform_accounts, never in git.'
            );
        }

        $missing = [];
        foreach ($this->requiredConfigKeys() as $key) {
            if (blank(config("poster.credentials.{$key}"))) {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            throw new NotConfiguredException(
                "{$platform->label()} is not configured: missing env values for ".implode(', ', $missing).'.'
            );
        }

        throw new NotConfiguredException(
            "{$platform->label()} API client is not implemented yet (Phase 2). Account and credentials are present, but no publish call has been built — refusing rather than faking success."
        );
    }
}
