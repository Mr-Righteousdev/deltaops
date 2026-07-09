<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CookieFile extends Model
{
    protected $fillable = [
        'file_path',
        'original_name',
        'is_active',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'uploaded_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
