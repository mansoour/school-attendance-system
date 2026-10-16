<?php

namespace App\Http\Controllers;

use App\Models\BrandingAsset;
use Illuminate\Support\Facades\Storage;

class BrandingAssetController extends Controller
{
    public function __invoke(BrandingAsset $asset)
    {
        return Storage::disk($asset->disk)->response($asset->path, null, ['Content-Type' => $asset->mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public, max-age=86400']);
    }
}
