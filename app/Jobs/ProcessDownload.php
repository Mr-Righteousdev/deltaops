<?php

namespace App\Jobs;

use App\Enums\DownloadStatus;
use App\Models\CookieFile;
use App\Models\Download;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProcessDownload implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public $timeout = 600;

    public $tries = 1;

    public function __construct(
        public Download $download
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->download->id;
    }

    public function handle(): void
    {
        $this->download->update(['status' => DownloadStatus::Processing]);

        $outputTemplate = storage_path('app/downloads/%(title)s.%(ext)s');

        $command = [
            config('downloader.yt_dlp_path'),
            '--no-playlist',
            '--print', 'after_move:filepath',
            '--js-runtimes', 'deno',
            '-o', $outputTemplate,
            '-f', $this->buildFormatArgument(),
        ];

        if ($this->download->format === 'mp3') {
            array_push($command, '--extract-audio', '--audio-format', 'mp3', '--audio-quality', '0');
        }

        $activeCookie = CookieFile::where('is_active', true)->latest('uploaded_at')->first();
        $cookiesPath = $activeCookie?->file_path
            ? Storage::disk('local')->path($activeCookie->file_path)
            : config('downloader.cookies_path');
        if ($cookiesPath && file_exists($cookiesPath)) {
            array_push($command, '--cookies', $cookiesPath);
        }

        $command[] = $this->download->url;

        $process = new Process($command);
        $process->setTimeout($this->timeout);

        try {
            $process->mustRun();
            $outputFilePath = trim($process->getOutput());
            $lines = explode("\n", $outputFilePath);
            $actualFilePath = end($lines);

            $this->download->update([
                'status' => DownloadStatus::Completed,
                'file_path' => $actualFilePath,
                'file_name' => basename($actualFilePath),
                'file_size' => file_exists($actualFilePath) ? filesize($actualFilePath) : null,
                'completed_at' => now(),
                'expires_at' => now()->addHours(config('downloader.retention_hours', 24)),
            ]);
        } catch (ProcessFailedException $e) {
            $errorOutput = $process->getErrorOutput();
            $this->download->update([
                'status' => DownloadStatus::Failed,
                'error_message' => $errorOutput ?: $e->getMessage(),
            ]);

            Log::error('yt-dlp download failed', [
                'download_id' => $this->download->id,
                'url' => $this->download->url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildFormatArgument(): string
    {
        $quality = $this->download->quality;

        if (str_starts_with($quality, 'fmt:')) {
            $formatId = substr($quality, 4);

            if ($this->download->format === 'mp3') {
                return $formatId;
            }

            return "{$formatId}+bestaudio/best";
        }

        $value = str_starts_with($quality, 'h:') ? substr($quality, 2) : $quality;

        if ($this->download->format === 'mp3') {
            return "bestaudio[abr<=$value]/bestaudio";
        }

        return "bestvideo[height<=$value]+bestaudio/best[height<=$value]";
    }

    public function failed(?Throwable $exception): void
    {
        $this->download->update([
            'status' => DownloadStatus::Failed,
            'error_message' => $exception->getMessage(),
        ]);
    }
}
