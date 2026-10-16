<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = ['recipient', 'subject', 'status', 'provider_id'];

    protected function casts(): array
    {
        return [];
    }
}
