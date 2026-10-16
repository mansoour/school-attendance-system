<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BrandingAsset extends Model
{
    use HasUuids;

    protected $fillable = ['disk', 'path', 'mime'];
}
