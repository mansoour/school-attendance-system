<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationRevision extends Model
{
    protected $fillable = ['locale', 'messages', 'published_at', 'created_by'];

    protected function casts(): array
    {
        return ['messages' => 'array', 'published_at' => 'datetime'];
    }
}
