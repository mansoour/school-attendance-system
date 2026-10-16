<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Locale extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['code', 'name', 'native_name', 'direction', 'plural_forms', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'plural_forms' => 'integer'];
    }
}
