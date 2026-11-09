<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\RecurringSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use MatanYadaev\EloquentSpatial\Objects\Point;

/**
 * Spec 019 intake: import researched external event listings from a
 * JSON file (format: specs/019-external-event-listings/04-import-format.md).
 *
 * Every imported row is created as kind=external, status=draft. The
 * command can never publish; a listing becomes visible only when an
 * admin approves it in Filament. Invalid rows are rejected with a
 * reason and never partially imported. Re-importing the same file is
 * safe: rows dedupe on (source_url, title, start_time).
 *
 * Spec 020 (format v2): an optional image_url is downloaded AFTER the
 * dedupe check (a duplicate never triggers a download), processed and
 * stored in FunLynk's own storage. An image failure never fails the
 * row: the listing imports imageless and the report flags it.
 */
class ImportListingsCommand extends Command
{
    protected $signature = 'listings:import {path : Path to the JSON file of candidate listings}';

    protected $description = 'Import external event listings as drafts (spec 019)';

    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! File::exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $decoded = json_decode((string) File::get($path), true);

        if (! is_array($decoded) || ! array_is_list($decoded)) {
            $this->error('The file must contain a JSON array of listing objects.');

            return self::FAILURE;
        }

        $imported = 0;
        $duplicates = 0;
        $rejected = 0;
        $imagesStored = 0;
        $imagesFailed = 0;
        $imageService = app(\App\Services\ListingImageService::class);

        foreach ($decoded as $index => $item) {
            $label = 'Row '.($index + 1);

            if (! is_array($item)) {
                $this->warn("{$label}: rejected, not an object.");
                $rejected++;

                continue;
            }

            $errors = $this->validateItem($item);

            if ($errors !== []) {
                $this->warn("{$label}: rejected, ".implode('; ', $errors));
                $rejected++;

                continue;
            }

            $startTime = Carbon::parse((string) $item['start_time'])->utc();

            $exists = Activity::query()
                ->where('source_url', $item['source_url'])
                ->where('title', $item['title'])
                ->where('start_time', $startTime->format('Y-m-d H:i:s'))
                ->exists();

            if ($exists) {
                $duplicates++;

                continue;
            }

            // Spec 020 image step: after dedupe, before create. Any
            // failure is recorded against the row and the listing still
            // imports, imageless.
            $images = [];
            $imageSourceUrl = null;
            $imageFailure = null;
            if (isset($item['image_url']) && $item['image_url'] !== '') {
                try {
                    $images = [$imageService->storeFromUrl($item['image_url'])];
                    $imageSourceUrl = (string) $item['image_url'];
                    $imagesStored++;
                } catch (\App\Services\ListingImageException $e) {
                    $imageFailure = $e->getMessage();
                    $imagesFailed++;
                }
            }

            Activity::create([
                'kind' => Activity::KIND_EXTERNAL,
                'host_id' => null,
                'status' => 'draft',
                'is_public' => true,
                'title' => $item['title'],
                'description' => $item['description'],
                'activity_type' => $item['activity_type'] ?? 'social',
                'location_name' => $item['location_name'],
                'location_address' => $item['location_address'] ?? null,
                'location_coordinates' => new Point((float) $item['latitude'], (float) $item['longitude']),
                'start_time' => $startTime,
                'end_time' => isset($item['end_time']) ? Carbon::parse((string) $item['end_time'])->utc() : null,
                'payment_type' => $item['payment_type'] ?? 'free',
                'price_cents' => $item['price_cents'] ?? null,
                'currency' => 'USD',
                'source_name' => $item['source_name'],
                'source_url' => $item['source_url'],
                'recurring_schedule_id' => $item['recurring_schedule_id'] ?? null,
                'images' => $images,
                'image_source_url' => $imageSourceUrl,
            ]);

            $imported++;

            if ($imageFailure !== null) {
                $title = is_string($item['title'] ?? null) ? $item['title'] : 'listing';
                $this->warn("{$label} ({$title}): imported, image failed ({$imageFailure}).");
            }
        }

        $summary = "Listings import complete: {$imported} imported, {$duplicates} duplicates skipped, {$rejected} rejected.";
        if ($imagesStored + $imagesFailed > 0) {
            $summary .= " Images: {$imagesStored} stored, {$imagesFailed} failed.";
        }
        $this->info($summary);

        return self::SUCCESS;
    }

    /**
     * Strict validation, mirroring the database checks (valid_price,
     * valid_times) so a rejected row never reaches the database.
     *
     * @param  array<string, mixed>  $item
     * @return list<string>
     */
    private function validateItem(array $item): array
    {
        $errors = [];

        foreach (['title', 'description', 'location_name', 'source_name'] as $field) {
            if (! isset($item[$field]) || ! is_string($item[$field]) || trim($item[$field]) === '') {
                $errors[] = "{$field} is required";
            }
        }

        $sourceUrl = $item['source_url'] ?? null;
        if (! is_string($sourceUrl) || trim($sourceUrl) === '') {
            $errors[] = 'source_url is required';
        } else {
            $parts = parse_url($sourceUrl);
            if ($parts === false || ($parts['scheme'] ?? null) !== 'https' || empty($parts['host'])) {
                $errors[] = 'source_url must be a valid https URL';
            }
        }

        $start = null;
        if (! isset($item['start_time']) || ! is_string($item['start_time'])) {
            $errors[] = 'start_time is required';
        } else {
            try {
                $start = Carbon::parse($item['start_time']);
            } catch (\Throwable) {
                $errors[] = 'start_time is not a valid date/time';
            }
        }

        if (isset($item['end_time'])) {
            try {
                $end = Carbon::parse((string) $item['end_time']);
                if ($start !== null && $end->lte($start)) {
                    $errors[] = 'end_time must be after start_time';
                }
            } catch (\Throwable) {
                $errors[] = 'end_time is not a valid date/time';
            }
        }

        $lat = $item['latitude'] ?? null;
        $lng = $item['longitude'] ?? null;
        if (! is_numeric($lat) || (float) $lat < -90 || (float) $lat > 90) {
            $errors[] = 'latitude must be a number between -90 and 90';
        }
        if (! is_numeric($lng) || (float) $lng < -180 || (float) $lng > 180) {
            $errors[] = 'longitude must be a number between -180 and 180';
        }

        $paymentType = $item['payment_type'] ?? 'free';
        $priceCents = $item['price_cents'] ?? null;
        if (! in_array($paymentType, ['free', 'online', 'at_door'], true)) {
            $errors[] = 'payment_type must be free, online or at_door';
        } elseif ($paymentType === 'free' && $priceCents !== null) {
            $errors[] = 'price_cents must be empty for a free listing';
        } elseif ($paymentType !== 'free' && (! is_int($priceCents) || $priceCents <= 0)) {
            $errors[] = 'price_cents must be a positive integer for a paid listing';
        }

        if (isset($item['recurring_schedule_id'])
            && ! RecurringSchedule::query()->whereKey($item['recurring_schedule_id'])->exists()) {
            $errors[] = 'recurring_schedule_id does not match an existing schedule';
        }

        return $errors;
    }
}
