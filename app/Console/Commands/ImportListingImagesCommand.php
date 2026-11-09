<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Services\ListingImageException;
use App\Services\ListingImageService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Spec 020 backfill: fill images on listings that already exist
 * without one (the 18 imported under 019, and any row whose image
 * failed at import). Keyed on the importer's dedupe triple
 * (source_url, title, start_time) plus an image_url.
 *
 * It creates nothing, changes no field other than images and
 * image_source_url, never overwrites an existing image, and cannot
 * publish or unpublish anything. Re-running the same file is a no-op:
 * filled rows are skipped on the second run.
 */
class ImportListingImagesCommand extends Command
{
    protected $signature = 'listings:import-images {path : Path to the JSON backfill file}';

    protected $description = 'Fill images on existing listings that have none (spec 020)';

    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! File::exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $decoded = json_decode((string) File::get($path), true);

        if (! is_array($decoded) || ! array_is_list($decoded)) {
            $this->error('The file must contain a JSON array of backfill objects.');

            return self::FAILURE;
        }

        $matched = 0;
        $filled = 0;
        $skipped = 0;
        $notFound = 0;
        $imageFailed = 0;
        $imageService = app(ListingImageService::class);

        foreach ($decoded as $index => $item) {
            $label = 'Row '.($index + 1);

            if (! is_array($item)
                || ! is_string($item['source_url'] ?? null)
                || ! is_string($item['title'] ?? null)
                || ! is_string($item['start_time'] ?? null)
                || ! isset($item['image_url'])) {
                $this->warn("{$label}: not found, entry is missing source_url, title, start_time or image_url.");
                $notFound++;

                continue;
            }

            try {
                $startTime = Carbon::parse($item['start_time'])->utc();
            } catch (\Throwable) {
                $this->warn("{$label}: not found, start_time is not a valid date/time.");
                $notFound++;

                continue;
            }

            $listing = Activity::query()
                ->where('source_url', $item['source_url'])
                ->where('title', $item['title'])
                ->where('start_time', $startTime->format('Y-m-d H:i:s'))
                ->first();

            if ($listing === null) {
                $this->warn("{$label} ({$item['title']}): not found.");
                $notFound++;

                continue;
            }

            $matched++;

            if (! empty($listing->images)) {
                $this->warn("{$label} ({$item['title']}): skipped, already has image.");
                $skipped++;

                continue;
            }

            try {
                $stored = $imageService->storeFromUrl($item['image_url']);
            } catch (ListingImageException $e) {
                $this->warn("{$label} ({$item['title']}): image failed ({$e->getMessage()}).");
                $imageFailed++;

                continue;
            }

            $listing->update([
                'images' => [$stored],
                'image_source_url' => (string) $item['image_url'],
            ]);
            $filled++;
        }

        $this->info("Listings image backfill complete: {$matched} matched, {$filled} filled, {$skipped} skipped, {$notFound} not found, {$imageFailed} image failed.");

        return self::SUCCESS;
    }
}
