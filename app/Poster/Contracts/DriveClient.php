<?php

namespace App\Poster\Contracts;

/**
 * The slice of Google Drive the poster needs. Phase 1 implementation is
 * CliDriveClient (the workspace's hatch_gws_cli); a native Google API
 * client can replace it later without touching intake logic.
 */
interface DriveClient
{
    /**
     * @return list<array{id: string, name: string, mimeType: string, modifiedTime: ?string}>
     */
    public function listFiles(string $folderId): array;

    /**
     * Download a binary file to a local path.
     */
    public function download(string $fileId, string $destPath): void;

    /**
     * Export a Google Workspace document (e.g. a caption written as a
     * Google Doc) as plain text and return its contents.
     */
    public function exportText(string $fileId): string;
}
