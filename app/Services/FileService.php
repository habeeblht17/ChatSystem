<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Illuminate\Support\Arr;

class FileService
{
    protected string $disk;
    protected array $allowedMimes;
    protected int $maxSizeKb;

    public function __construct()
    {
        // Primary disk 
        $this->disk = config('chat.attachment_disk');

        // Allowed mimes and max size should be configurable in config/chat.php
        $this->allowedMimes = config('chat.allowed_mimes', [
            'image/png',
            'image/jpeg',
            'image/webp',
            'image/gif',
            'application/pdf',
        ]);

        // max size (kilobytes). default 5 MB if not set
        $this->maxSizeKb = (int) config('chat.max_upload_kb', 1024);
    }

    /**
     * Upload a chat attachment. Returns metadata array.
     *
     * metadata: ['path','url','original_name','mime','size']
     *
     * @throws RuntimeException
     */
    public function uploadChatAttachment(UploadedFile $file, ?string $subFolder = null): array
    {
        // Use server-side detection (getMimeType) which inspects file contents
        $mime = $file->getMimeType() ?: $file->getClientMimeType();

        if (! in_array($mime, $this->allowedMimes, true)) {
            throw new RuntimeException('Unsupported file type: ' . $mime);
        }

        // Size check (getSize returns bytes)
        $sizeKb = (int) ceil(($file->getSize() ?? 0) / 1024);
        if ($this->maxSizeKb > 0 && $sizeKb > $this->maxSizeKb) {
            throw new RuntimeException("File too large ({$sizeKb} KB). Max is {$this->maxSizeKb} KB.");
        }

        $folder = trim('chat_attachments/' . ($subFolder ? trim($subFolder, '/') : ''), '/');
        $folder = $folder === '' ? 'chat_attachments' : $folder;

        // Use a safe extension: let the UploadedFile guess from content
        $extension = $file->extension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'bin';
        $filename = Str::uuid()->toString() . '.' . $extension;

        try {
            $path = $file->storeAs($folder, $filename, $this->disk);
            $path = ltrim($path, '/');
            if (! $path) {
                throw new RuntimeException('Failed to store attachment.');
            }
        } catch (\Throwable $e) {
            Log::error('FileService::uploadChatAttachment store failure', [
                'exception' => $e,
                'disk' => $this->disk,
                'folder' => $folder,
            ]);
            throw new RuntimeException('Failed to store attachment.');
        }

        $url = null;
        try {
            $url = Storage::disk($this->disk)->url($path);
        } catch (\Throwable $e) {
            // If disk is private or url() fails, just leave url null (caller can generate temporary url)
            Log::debug('FileService::uploadChatAttachment could not generate url', ['exception' => $e]);
            $url = null;
        }

        return [
            'path' => $path,
            'url' => $url,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $mime,
            'size' => $file->getSize(),
        ];
    }

    /**
     * Delete an uploaded chat attachment (by path)
     */
    public function deleteChatAttachment(string $path): bool
    {
        return $this->deleteFile($path, $this->disk);
    }

    /**
     * Store uploaded file and return stored path.
     *
     * Accepts UploadedFile (preferred), an existing stored path (string),
     * or null. If $disk is null the service's configured disk is used.
     *
     * @param  UploadedFile|string|null  $file
     * @param  string  $folder
     * @param  string|null  $disk
     * @return string|null
     *
     * @throws RuntimeException on storage errors
     */
    public function storeFile($file, string $folder = 'uploads', ?string $disk = null): ?string
    {
        if (! $file) {
            return null;
        }

        $diskToUse = $disk ?? $this->disk;

        // If already a path (string), assume it's stored already (normalize)
        if (is_string($file)) {
            return ltrim($file, '/');
        }

        if ($file instanceof UploadedFile) {
            // limit filename length and sanitize name
            $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $baseName = Str::limit(Str::slug($baseName), 120, '');

            // prefer guessed extension
            $extension = $file->extension() ?: ($file->getClientOriginalExtension() ?: 'bin');

            $filename = $baseName . '-' . Str::random(8) . '.' . $extension;
            $folder = trim($folder, '/');

            try {
                $path = $file->storeAs($folder, $filename, $diskToUse);
                if (! $path) {
                    throw new RuntimeException('Failed to store file.');
                }
                return $path;
            } catch (\Throwable $e) {
                Log::error('FileService::storeFile store failure', [
                    'exception' => $e,
                    'disk' => $diskToUse,
                    'folder' => $folder,
                ]);
                throw new RuntimeException('Failed to store file.');
            }
        }

        return null;
    }

    /**
     * Delete file if exists.
     *
     * @param string|null $path
     * @param string|null $disk
     * @return bool
     */
    public function deleteFile(?string $path, ?string $disk = null): bool
    {
        if (! $path) {
            return true;
        }

        $diskToUse = $disk ?? $this->disk;

        try {
            if (Storage::disk($diskToUse)->exists($path)) {
                return Storage::disk($diskToUse)->delete($path);
            }
            return true;
        } catch (\Throwable $e) {
            Log::warning('FileService::deleteFile failed', [
                'exception' => $e,
                'path' => $path,
                'disk' => $diskToUse,
            ]);
            return false;
        }
    }

    /**
     * Return configured disk (helper).
     */
    public function getDisk(): string
    {
        return $this->disk;
    }

    /**
     * Return allowed mimetypes (for UI or validation rules).
     */
    public function getAllowedMimes(): array
    {
        return $this->allowedMimes;
    }

    /**
     * Return max upload size in KB (for UI or validation rules).
     */
    public function getMaxSizeKb(): int
    {
        return $this->maxSizeKb;
    }
}