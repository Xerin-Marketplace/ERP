<?php

namespace App\Services;

use App\Models\ManagedFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FileService
{
    /** Default allowed mime groups — extend via config('erp.files.allowed_mimes'). */
    private const DEFAULT_MIMES = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif',
        'application/pdf', 'text/plain', 'text/csv',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    private const MAX_SIZE_KB = 10240; // 10 MB

    public function store(UploadedFile $file, ?string $directory = 'uploads', ?string $disk = null, string $visibility = 'private'): ManagedFile
    {
        $disk ??= config('filesystems.default', 'local');
        $allowed = config('erp.files.allowed_mimes', self::DEFAULT_MIMES);
        $maxKb = (int) config('erp.files.max_size_kb', self::MAX_SIZE_KB);

        if (! in_array($file->getMimeType(), $allowed, true)) {
            throw ValidationException::withMessages(['file' => "File type {$file->getMimeType()} is not allowed."]);
        }

        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages(['file' => "File exceeds the {$maxKb} KB limit."]);
        }

        $path = $file->storeAs(
            $directory,
            Str::ulid().'.'.$file->guessExtension(),
            ['disk' => $disk, 'visibility' => $visibility]
        );

        return ManagedFile::create([
            'user_id' => auth()->id(),
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'visibility' => $visibility,
        ]);
    }

    /**
     * Stream an authorized download of a managed file.
     */
    public function download(ManagedFile $file)
    {
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function delete(ManagedFile $file): void
    {
        Storage::disk($file->disk)->delete($file->path);
        $file->delete();
    }
}
