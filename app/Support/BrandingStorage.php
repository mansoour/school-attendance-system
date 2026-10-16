<?php

namespace App\Support;

use App\Models\BrandingAsset;
use App\Settings\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BrandingStorage
{
    public function store(UploadedFile $file): string
    {
        $disk = app(SettingsService::class)->get('storage.r2_enabled') ? 'r2' : 'public';
        $path = $file->store('branding', $disk);
        throw_unless($path, new \RuntimeException('Upload failed'));

        return 'asset:'.BrandingAsset::create(['disk' => $disk, 'path' => $path, 'mime' => $file->getMimeType()])->id;
    }

    public function url(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return str_starts_with($value, 'asset:') ? route('branding.asset', substr($value, 6)) : Storage::disk('public')->url($value);
    }
}
