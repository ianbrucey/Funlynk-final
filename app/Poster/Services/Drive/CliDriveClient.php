<?php

namespace App\Poster\Services\Drive;

use App\Poster\Contracts\DriveClient;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Drive access via the workspace's Google Workspace CLI
 * (/opt/hatch/bin/hatch_gws_cli), the same pattern the voice-notes
 * ingestion uses: `drive files list` with a parent query, `drive files get`
 * with alt=media for downloads, `drive files export` for Google Docs.
 *
 * Note: the CLI is authenticated on the Lotus VM. When the poster runs
 * somewhere without it, DRIVE_CLI_BIN points at the binary — and intake
 * fails loudly (RuntimeException) rather than silently doing nothing.
 */
class CliDriveClient implements DriveClient
{
    public function __construct(
        private readonly string $binary,
    ) {}

    public static function fromConfig(): self
    {
        return new self((string) config('poster.drive.cli'));
    }

    public function listFiles(string $folderId): array
    {
        $query = "'{$folderId}' in parents and trashed = false";
        $output = $this->run([
            'drive', 'files', 'list',
            '--params', $this->encodeParams(['q' => $query, 'fields' => 'files(id,name,mimeType,modifiedTime,size)']),
            '--format', 'json',
        ]);

        $decoded = json_decode($output, true);
        $files = is_array($decoded) && is_array($decoded['files'] ?? null) ? $decoded['files'] : [];

        $result = [];

        foreach ($files as $f) {
            if (! is_array($f)) {
                continue;
            }

            $result[] = [
                'id' => (string) ($f['id'] ?? ''),
                'name' => (string) ($f['name'] ?? ''),
                'mimeType' => (string) ($f['mimeType'] ?? ''),
                'modifiedTime' => isset($f['modifiedTime']) ? (string) $f['modifiedTime'] : null,
            ];
        }

        return $result;
    }

    public function download(string $fileId, string $destPath): void
    {
        $dir = dirname($destPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $this->run([
            'drive', 'files', 'get',
            '--params', $this->encodeParams(['fileId' => $fileId, 'alt' => 'media']),
            '-o', $destPath,
        ]);
    }

    public function exportText(string $fileId): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'poster-caption-').'.txt';

        $this->run([
            'drive', 'files', 'export',
            '--params', $this->encodeParams(['fileId' => $fileId, 'mimeType' => 'text/plain']),
            '-o', $tmp,
        ]);

        $contents = file_get_contents($tmp);
        unlink($tmp);

        return $contents === false ? '' : $contents;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function encodeParams(array $params): string
    {
        return json_encode($params, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  list<string>  $args
     */
    private function run(array $args): string
    {
        if (! is_executable($this->binary)) {
            throw new RuntimeException(
                "Drive CLI not found/executable at {$this->binary}. Set DRIVE_CLI_BIN to the hatch_gws_cli binary on a machine where it is authenticated."
            );
        }

        $process = new Process([$this->binary, ...$args]);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Drive CLI failed: '.trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        return $process->getOutput();
    }
}
