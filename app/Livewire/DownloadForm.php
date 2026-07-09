<?php

namespace App\Livewire;

use App\Jobs\ProcessDownload;
use App\Models\CookieFile;
use App\Models\Download;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\Process\Process;

#[Title('YouTube Downloader')]
#[Layout('layouts.public')]
class DownloadForm extends Component
{
    public string $step = 'input';

    #[Rule(['required', 'url', 'regex:/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+/i'])]
    public string $url = '';

    public ?array $videoInfo = null;

    public ?int $downloadId = null;

    public bool $isLoading = false;

    public string $activeTab = 'video';

    public function updatedUrl(): void
    {
        if (blank($this->url)) {
            return;
        }

        $this->validateOnly('url');

        if ($this->getErrorBag()->has('url')) {
            return;
        }

        $this->fetchInfo();
    }

    public function fetchInfo(): void
    {
        $this->isLoading = true;

        $this->validate();

        $cacheKey = 'yt_info_'.md5($this->url);
        $info = Cache::remember($cacheKey, 3600, function (): array {
            $command = [
                config('downloader.yt_dlp_path'),
                '--dump-json',
                '--no-playlist',
                '--js-runtimes', 'deno',
            ];

            $activeCookie = CookieFile::where('is_active', true)->latest('uploaded_at')->first();
            $cookiesPath = $activeCookie?->file_path
                ? storage_path('app/'.$activeCookie->file_path)
                : config('downloader.cookies_path');
            if ($cookiesPath && file_exists($cookiesPath)) {
                $command[] = '--cookies';
                $command[] = $cookiesPath;
            }

            $command[] = $this->url;

            $process = new Process($command);
            $process->setTimeout(30);
            $process->run();

            if (! $process->isSuccessful()) {
                $this->isLoading = false;

                throw new \RuntimeException('Failed to fetch video info: '.$process->getErrorOutput());
            }

            $data = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);

            return $data;
        });

        $this->videoInfo = $info;
        $this->step = 'select';
        $this->isLoading = false;
    }

    public function downloadVideo(string $formatSpec): void
    {
        $download = Download::create([
            'url' => $this->url,
            'format' => 'mp4',
            'quality' => $formatSpec,
        ]);

        Bus::dispatch(new ProcessDownload($download));

        $this->downloadId = $download->id;
        $this->step = 'processing';
    }

    public function downloadAudio(string $formatSpec): void
    {
        $download = Download::create([
            'url' => $this->url,
            'format' => 'mp3',
            'quality' => $formatSpec,
        ]);

        Bus::dispatch(new ProcessDownload($download));

        $this->downloadId = $download->id;
        $this->step = 'processing';
    }

    #[Computed]
    public function videoFormats(): array
    {
        $targets = [1080, 720, 360, 240, 144];

        if (! $this->videoInfo || ! isset($this->videoInfo['formats'])) {
            return array_map(fn (int $h) => [
                'height' => $h,
                'id' => 'h:'.$h,
                'filesize' => null,
            ], $targets);
        }

        $formats = collect($this->videoInfo['formats'])
            ->filter(fn (array $f) => ($f['ext'] ?? '') === 'mp4' && ($f['vcodec'] ?? 'none') !== 'none')
            ->map(fn (array $f) => [
                'id' => 'fmt:'.$f['format_id'],
                'height' => $f['height'] ?? 0,
                'filesize' => $f['filesize'] ?? $f['filesize_approx'] ?? null,
                'vcodec' => $f['vcodec'] ?? '',
            ])
            ->filter(fn (array $f) => $f['height'] > 0)
            ->sortByDesc('height')
            ->values();

        $results = [];
        foreach ($targets as $target) {
            $exact = $formats->firstWhere('height', $target);

            if ($exact) {
                $results[] = $exact;
            } else {
                $best = $formats->where('height', '<=', $target + 50)
                    ->sortByDesc('height')
                    ->first();

                if ($best && $best['height'] >= $target * 0.6) {
                    $results[] = $best;
                } else {
                    $results[] = [
                        'height' => $target,
                        'id' => 'h:'.$target,
                        'filesize' => null,
                    ];
                }
            }
        }

        return $results;
    }

    #[Computed]
    public function audioFormats(): array
    {
        $targets = [320, 256, 128];

        if (! $this->videoInfo || ! isset($this->videoInfo['formats'])) {
            return array_map(fn (int $b) => [
                'abr' => $b,
                'id' => 'h:'.$b,
            ], $targets);
        }

        $formats = collect($this->videoInfo['formats'])
            ->filter(fn (array $f) => ($f['vcodec'] ?? 'none') === 'none' && ($f['acodec'] ?? 'none') !== 'none')
            ->map(fn (array $f) => [
                'id' => 'fmt:'.$f['format_id'],
                'abr' => $f['abr'] ?? 0,
                'filesize' => $f['filesize'] ?? $f['filesize_approx'] ?? null,
                'ext' => $f['ext'] ?? '',
            ])
            ->sortByDesc('abr')
            ->values();

        $results = [];
        foreach ($targets as $target) {
            $exact = $formats->firstWhere('abr', $target);

            if ($exact) {
                $results[] = $exact;
            } else {
                $best = $formats->where('abr', '<=', $target + 50)
                    ->sortByDesc('abr')
                    ->first();

                if ($best && $best['abr'] >= $target * 0.6) {
                    $results[] = $best;
                } else {
                    $results[] = [
                        'abr' => $target,
                        'id' => 'h:'.$target,
                        'filesize' => null,
                    ];
                }
            }
        }

        return $results;
    }

    #[Computed]
    public function download(): ?Download
    {
        if ($this->downloadId === null) {
            return null;
        }

        return Download::find($this->downloadId);
    }

    public function goBack(): void
    {
        $this->reset('url', 'videoInfo', 'downloadId', 'step');
        $this->step = 'input';
    }

    public function render()
    {
        return view('livewire.download-form');
    }
}
