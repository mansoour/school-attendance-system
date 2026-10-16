<?php

namespace App\Models;

use App\Support\HasLocalizedFields;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['slug', 'title', 'body', 'is_published'];

    protected function casts(): array
    {
        return ['title' => 'array', 'body' => 'array', 'is_published' => 'boolean'];
    }

    use HasLocalizedFields;

    public const LOCALIZED = ['title', 'body'];
}
