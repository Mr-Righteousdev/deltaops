<?php

namespace App\Models;

use App\Enums\DownloadStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Download extends Model
{
    protected $fillable = [
        'url',
        'format',
        'quality',
        'status',
        'file_path',
        'file_name',
        'file_size',
        'error_message',
        'download_token',
        'completed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DownloadStatus::class,
            'file_size' => 'integer',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Download $download): void {
            $download->download_token = (string) Str::uuid();
        });
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', DownloadStatus::Pending);
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', DownloadStatus::Processing);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', DownloadStatus::Completed);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', DownloadStatus::Failed);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
    }
}
