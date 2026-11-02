<?php

namespace Tests\Feature\Poster;

use App\Poster\Contracts\DriveClient;
use App\Poster\Enums\PostBundleState;
use App\Poster\Models\PostBundle;
use App\Poster\Services\DriveIntakeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriveIntakeTest extends TestCase
{
    use RefreshDatabase;

    private function fakeDrive(): DriveClient
    {
        return new class implements DriveClient
        {
            public function listFiles(string $folderId): array
            {
                return [
                    ['id' => 'file-caption', 'name' => 'caption.txt', 'mimeType' => 'text/plain', 'modifiedTime' => null],
                    ['id' => 'file-flyer', 'name' => 'flyer.png', 'mimeType' => 'image/png', 'modifiedTime' => null],
                    ['id' => 'file-clip', 'name' => 'clip.mp4', 'mimeType' => 'video/mp4', 'modifiedTime' => null],
                ];
            }

            public function download(string $fileId, string $destPath): void
            {
                if (! is_dir(dirname($destPath))) {
                    mkdir(dirname($destPath), 0775, true);
                }
                file_put_contents($destPath, "bytes-for-{$fileId}");
            }

            public function exportText(string $fileId): string
            {
                return 'Exported caption';
            }
        };
    }

    public function test_intake_creates_a_draft_bundle_from_a_drop(): void
    {
        $service = new DriveIntakeService($this->fakeDrive());

        $bundle = $service->ingestFolder('folder-123');

        $this->assertNotNull($bundle);
        $this->assertSame(PostBundleState::Draft, $bundle->state);
        $this->assertSame('bytes-for-file-caption', $bundle->caption);
        $this->assertSame(['file-caption', 'file-flyer', 'file-clip'], $bundle->drive_file_ids);
        $this->assertCount(2, $bundle->assets);
        $this->assertSame('image', $bundle->assets[0]['kind']);
        $this->assertSame('video', $bundle->assets[1]['kind']);

        // Intake never approves.
        $this->assertNull($bundle->approved_by);
        $this->assertNull($bundle->approved_at);
    }

    public function test_intake_is_idempotent_for_consumed_files(): void
    {
        $service = new DriveIntakeService($this->fakeDrive());

        $this->assertNotNull($service->ingestFolder('folder-123'));
        $this->assertNull($service->ingestFolder('folder-123'));
        $this->assertSame(1, PostBundle::count());
    }

    public function test_artisan_command_reports_when_folder_is_empty(): void
    {
        $empty = new class implements DriveClient
        {
            public function listFiles(string $folderId): array
            {
                return [];
            }

            public function download(string $fileId, string $destPath): void {}

            public function exportText(string $fileId): string
            {
                return '';
            }
        };
        $this->app->instance(DriveClient::class, $empty);

        $this->artisan('poster:intake', ['--folder' => 'folder-123'])
            ->expectsOutputToContain('Nothing new')
            ->assertSuccessful();
    }
}
