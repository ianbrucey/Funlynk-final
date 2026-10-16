<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Gives seeded users, activities and groups sample images so local testing has no broken images.
 *
 * Images are downloaded once from picsum.photos / pravatar.cc into the public disk
 * (storage/app/public, served at /storage/... after `php artisan storage:link`). If the
 * network is unavailable, gradient placeholders are drawn with GD instead. Safe to re-run.
 */
class SampleImagesSeeder extends Seeder
{
    private const ACTIVITY_IMAGES = 16;

    private const AVATARS = 16;

    private const GROUP_COVERS = 8;

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('SampleImagesSeeder must not run in production.');

            return;
        }

        $activityPaths = $this->pool('activities', self::ACTIVITY_IMAGES, 800, 500, 'photo');
        $avatarPaths = $this->pool('avatars', self::AVATARS, 200, 200, 'avatar');
        $coverPaths = $this->pool('groups/covers', self::GROUP_COVERS, 800, 300, 'photo');

        $activities = $this->assign(Activity::query()->whereNull('cover_image_url'), $activityPaths, function (Activity $activity, string $path, int $i) use ($activityPaths): void {
            $second = $activityPaths[($i + 5) % count($activityPaths)];
            $activity->forceFill([
                'cover_image_url' => Storage::url($path),
                'images' => $i % 3 === 0 ? [$path, $second] : [$path],
            ])->saveQuietly();
        });

        $users = $this->assign(User::query()->whereNull('profile_image_url'), $avatarPaths, function (User $user, string $path): void {
            $user->forceFill(['profile_image_url' => $path])->saveQuietly();
        });

        $groups = $this->assign(Group::query()->whereNull('cover_image_url'), $coverPaths, function (Group $group, string $path): void {
            $group->forceFill([
                'cover_image_url' => Storage::url($path),
                'avatar_url' => Storage::url($path),
            ])->saveQuietly();
        });

        $this->command?->info("Sample images assigned: {$activities} activities, {$users} users, {$groups} groups.");
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function assign($query, array $paths, callable $apply): int
    {
        $count = 0;

        $query->lazyById(500)->each(function ($model) use ($paths, $apply, &$count): void {
            $apply($model, $paths[$count % count($paths)], $count);
            $count++;
        });

        return $count;
    }

    /**
     * @return array<int, string> disk-relative paths
     */
    private function pool(string $directory, int $size, int $width, int $height, string $kind): array
    {
        $disk = Storage::disk('public');
        $paths = [];

        for ($i = 1; $i <= $size; $i++) {
            $path = "{$directory}/sample-{$i}.jpg";

            if (! $disk->exists($path)) {
                $disk->put($path, $this->download($kind, $i, $width, $height) ?? $this->placeholder($i, $width, $height));
            }

            $paths[] = $path;
        }

        return $paths;
    }

    private function download(string $kind, int $seed, int $width, int $height): ?string
    {
        $url = $kind === 'avatar'
            ? "https://i.pravatar.cc/{$width}?img={$seed}"
            : "https://picsum.photos/seed/funlynk-{$seed}/{$width}/{$height}.jpg";

        try {
            $response = Http::timeout(15)->retry(2, 500)->get($url);
        } catch (Throwable) {
            return null;
        }

        return $response->successful() && str_starts_with((string) $response->header('Content-Type'), 'image/')
            ? $response->body()
            : null;
    }

    private function placeholder(int $seed, int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        $hue = ($seed * 47) % 360;

        for ($y = 0; $y < $height; $y++) {
            [$r, $g, $b] = $this->hsv(($hue + ($y / $height) * 60) % 360, 0.6, 0.35 + 0.4 * ($y / $height));
            imagefilledrectangle($image, 0, $y, $width, $y, imagecolorallocate($image, $r, $g, $b));
        }

        ob_start();
        imagejpeg($image, null, 85);

        return (string) ob_get_clean();
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function hsv(float $h, float $s, float $v): array
    {
        $c = $v * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $v - $c;
        [$r, $g, $b] = match (true) {
            $h < 60 => [$c, $x, 0],
            $h < 120 => [$x, $c, 0],
            $h < 180 => [0, $c, $x],
            $h < 240 => [0, $x, $c],
            $h < 300 => [$x, 0, $c],
            default => [$c, 0, $x],
        };

        return [(int) (($r + $m) * 255), (int) (($g + $m) * 255), (int) (($b + $m) * 255)];
    }
}
