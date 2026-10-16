<?php

namespace App\Models;

use App\Support\HasLocalizedFields;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['event', 'subject', 'body', 'is_active'];

    protected function casts(): array
    {
        return ['subject' => 'array', 'body' => 'array', 'is_active' => 'boolean'];
    }

    use HasLocalizedFields;

    public const LOCALIZED = ['body', 'subject'];
}
