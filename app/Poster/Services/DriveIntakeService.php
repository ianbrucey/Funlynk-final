<?php

namespace App\Poster\Services;

use App\Poster\Contracts\DriveClient;
use App\Poster\Enums\PostBundleState;
use App\Poster\Models\PostBundle;

/**
 * Turns one drop into the "FunLink Posts" Drive folder into one draft
 * PostBundle:
 *
 *   - text files (.txt/.md) and Google Docs  -> the caption
 *   - image files                            -> image assets (downloaded)
 *   - video files                            -> video assets (downloaded)
 *
 * Files already consumed into an earlier bundle (matched by Drive file id
 * against post_bundles.drive_file_ids) are skipped, so re-running intake
 * is safe. Intake NEVER approves and never posts — bundles are born draft.
 */
class DriveIntakeService
{
    public function __construct(
        private readonly DriveClient $drive,
    ) {}

    /**
     * Returns the created bundle, or null when the folder holds nothing new.
     */
    public function ingestFolder(string $folderId): ?PostBundle
    {
        $files = $this->drive->listFiles($folderId);

        $consumed = PostBundle::query()
            ->whereNotNull('drive_file_ids')
            ->pluck('drive_file_ids')
            ->flatMap(fn (mixed $ids): array => is_array($ids) ? $ids : [])
            ->all();

        $new = collect($files)->reject(fn (array $f) => in_array($f['id'], $consumed, true))->values();

        if ($new->isEmpty()) {
            return null;
        }

        $captions = [];
        $media = [];

        foreach ($new as $file) {
            if ($this->isCaptionFile($file)) {
                $captions[] = $file;
            } elseif ($this->isMediaFile($file)) {
                $media[] = $file;
            }
            // Anything else (folders, spreadsheets, ...) is ignored but
            // still recorded as consumed so it isn't reconsidered forever.
        }

        $captionText = '';
        foreach ($captions as $file) {
            $text = $file['mimeType'] === 'application/vnd.google-apps.document'
                ? $this->drive->exportText($file['id'])
                : $this->downloadToTempAndRead($file['id'], $file['name']);
            $captionText .= ($captionText === '' ? '' : "\n\n").trim($text);
        }

        $bundle = PostBundle::create([
            'title' => $this->deriveTitle($captionText, $media),
            'caption' => $captionText,
            'source' => 'drive',
            'drive_folder_id' => $folderId,
            'drive_file_ids' => $new->pluck('id')->all(),
            'assets' => [],
            'state' => PostBundleState::Draft,
        ]);

        $assets = [];
        foreach ($media as $file) {
            $relative = "poster/intake/{$bundle->id}/{$file['name']}";
            $this->drive->download($file['id'], storage_path("app/{$relative}"));

            $assets[] = [
                'kind' => str_starts_with($file['mimeType'], 'video/') ? 'video' : 'image',
                'name' => $file['name'],
                'mime' => $file['mimeType'],
                'drive_id' => $file['id'],
                'local_path' => $relative,
            ];
        }

        $bundle->update(['assets' => $assets]);

        return $bundle;
    }

    /**
     * @param  array{id: string, name: string, mimeType: string, modifiedTime: ?string}  $file
     */
    private function isCaptionFile(array $file): bool
    {
        return $file['mimeType'] === 'application/vnd.google-apps.document'
            || in_array($file['mimeType'], ['text/plain', 'text/markdown'], true)
            || preg_match('/\.(txt|md)$/i', $file['name']) === 1;
    }

    /**
     * @param  array{id: string, name: string, mimeType: string, modifiedTime: ?string}  $file
     */
    private function isMediaFile(array $file): bool
    {
        return str_starts_with($file['mimeType'], 'image/')
            || str_starts_with($file['mimeType'], 'video/');
    }

    private function downloadToTempAndRead(string $fileId, string $name): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'poster-caption-');
        $this->drive->download($fileId, $tmp);
        $contents = file_get_contents($tmp);
        unlink($tmp);

        return $contents === false ? '' : $contents;
    }

    /**
     * @param  list<array{id: string, name: string, mimeType: string, modifiedTime: ?string}>  $media
     */
    private function deriveTitle(string $caption, array $media): ?string
    {
        $firstLine = trim(strtok($caption, "\n") ?: '');
        if ($firstLine !== '') {
            return mb_substr($firstLine, 0, 80);
        }

        return $media[0]['name'] ?? null;
    }
}
