<?php

namespace App\Poster\Console\Commands;

use App\Poster\Services\DriveIntakeService;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * php artisan poster:intake
 *
 * Pulls whatever is new in the "FunLink Posts" Drive folder
 * (DRIVE_POSTS_FOLDER_ID) into a draft post bundle. Drafts only — this
 * command can never approve or publish anything.
 */
class IntakeDriveCommand extends Command
{
    protected $signature = 'poster:intake {--folder= : Drive folder id (defaults to DRIVE_POSTS_FOLDER_ID)}';

    protected $description = 'Ingest new files from the FunLink Posts Drive folder into a draft post bundle';

    public function handle(DriveIntakeService $intake): int
    {
        $folderId = $this->option('folder') ?: config('poster.drive.posts_folder_id');

        if (blank($folderId)) {
            $this->error('No Drive folder configured. Set DRIVE_POSTS_FOLDER_ID in .env or pass --folder=');

            return self::FAILURE;
        }

        try {
            $bundle = $intake->ingestFolder((string) $folderId);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($bundle === null) {
            $this->info('Nothing new in the FunLink Posts folder.');

            return self::SUCCESS;
        }

        $assetCount = count($bundle->assets ?? []);
        $this->info("Created draft bundle #{$bundle->id} (\"{$bundle->title}\") with {$assetCount} asset(s).");
        $this->line('State: draft — it will not post until it is explicitly approved.');

        return self::SUCCESS;
    }
}
