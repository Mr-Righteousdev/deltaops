<div class="flex flex-col gap-8">
    <div class="text-center">
        <h1 class="text-2xl font-bold text-neutral-900">YouTube Downloader</h1>
        <p class="mt-1 text-neutral-500">
            Paste a YouTube URL below to see available formats.
        </p>
    </div>

    @if ($step === 'processing' && ($download = $this->download))
        <div class="bg-white rounded-xl shadow-sm border border-neutral-200 p-8">
            @if ($download->status === \App\Enums\DownloadStatus::Pending || $download->status === \App\Enums\DownloadStatus::Processing)
                <div class="flex flex-col items-center gap-4 text-center" wire:poll.2s>
                    <div class="size-12 rounded-full bg-amber-50 flex items-center justify-center">
                        <flux:icon name="arrow-path" class="size-6 text-amber-500 animate-spin" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-neutral-900">Processing your download...</h3>
                        <p class="mt-1 text-sm text-neutral-500">This may take a moment depending on the file size.</p>
                    </div>
                    <flux:progress class="w-full max-w-xs" />
                </div>
            @elseif ($download->status === \App\Enums\DownloadStatus::Completed)
                <div class="flex flex-col items-center gap-4 text-center">
                    <div class="size-12 rounded-full bg-green-50 flex items-center justify-center">
                        <flux:icon name="check-circle" class="size-6 text-green-500" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-neutral-900">Download ready!</h3>
                        <p class="mt-1 text-sm text-neutral-500">{{ $download->file_name }}</p>
                    </div>
                    <div class="flex gap-3">
                        <flux:button
                            tag="a"
                            href="{{ route('download.serve', ['download' => $download, 'token' => $download->download_token]) }}"
                            variant="primary"
                            icon="arrow-down-tray"
                        >
                            Download File
                        </flux:button>
                        <flux:button wire:click="goBack" variant="ghost">
                            Download Another
                        </flux:button>
                    </div>
                </div>
            @elseif ($download->status === \App\Enums\DownloadStatus::Failed)
                <div class="flex flex-col items-center gap-4 text-center">
                    <div class="size-12 rounded-full bg-red-50 flex items-center justify-center">
                        <flux:icon name="x-circle" class="size-6 text-red-500" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-neutral-900">Download failed</h3>
                        <p class="mt-1 text-sm text-red-600">
                            {{ $download->error_message ?? 'An unexpected error occurred.' }}
                        </p>
                    </div>
                    <flux:button wire:click="goBack" variant="ghost">
                        Try Again
                    </flux:button>
                </div>
            @endif
        </div>
    @elseif ($step === 'select' && $videoInfo)
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
            <div class="md:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                    <input type="hidden" id="video_id" value="{{ $videoInfo['id'] ?? '' }}">
                    <div class="thumbnail cover">
                        <img
                            src="{{ $videoInfo['thumbnail'] ?? '' }}"
                            alt="{{ $videoInfo['title'] ?? '' }}"
                            class="w-full aspect-video object-cover"
                        >
                        <div class="p-4">
                            <b class="text-sm text-neutral-900 leading-snug">{{ $videoInfo['title'] ?? '' }}</b>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-3">
                <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                    <ul class="flex border-b border-neutral-200" id="selectTab">
                        <li class="p-0 {{ $activeTab === 'audio' ? '' : 'active' }}">
                            <button
                                wire:click="$set('activeTab', 'video')"
                                class="flex items-center gap-1.5 px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'video' ? 'border-blue-600 text-blue-700' : 'border-transparent text-neutral-500 hover:text-neutral-700' }}"
                            >
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M0 128C0 92.7 28.7 64 64 64H320c35.3 0 64 28.7 64 64V384c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128zM559.1 99.8c10.4 5.6 16.9 16.4 16.9 28.2V384c0 11.8-6.5 22.6-16.9 28.2s-23 5-32.9-1.6l-96-64L416 337.1V320 192 174.9l14.2-9.5 96-64c9.8-6.5 22.4-7.2 32.9-1.6z"/></svg>
                                Video
                            </button>
                        </li>
                        <li class="p-0 {{ $activeTab === 'audio' ? 'active' : '' }}">
                            <button
                                wire:click="$set('activeTab', 'audio')"
                                class="flex items-center gap-1.5 px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'audio' ? 'border-blue-600 text-blue-700' : 'border-transparent text-neutral-500 hover:text-neutral-700' }}"
                            >
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M499.1 6.3c8.1 6 12.9 15.6 12.9 25.7v72V368c0 44.2-43 80-96 80s-96-35.8-96-80s43-80 96-80c11.2 0 22 1.6 32 4.6V147L192 223.8V432c0 44.2-43 80-96 80s-96-35.8-96-80s43-80 96-80c11.2 0 22 1.6 32 4.6V200 128c0-14.1 9.3-26.6 22.8-30.7l320-96c9.7-2.9 20.2-1.1 28.3 5z"/></svg>
                                Audio
                            </button>
                        </li>
                    </ul>

                    @if ($activeTab === 'video')
                        <div class="p-0">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-neutral-200 bg-neutral-50">
                                        <th class="text-left px-4 py-2.5 font-semibold text-neutral-700">File type</th>
                                        <th class="text-left px-4 py-2.5 font-semibold text-neutral-700">Format</th>
                                        <th class="text-center px-4 py-2.5 font-semibold text-neutral-700">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->videoFormats as $fmt)
                                        <tr class="border-b border-neutral-100 hover:bg-neutral-50 transition-colors">
                                            <td class="px-4 py-2.5 text-neutral-900 whitespace-nowrap">{{ $fmt['height'] }}p (.mp4)</td>
                                            <td class="px-4 py-2.5 text-neutral-500">Auto</td>
                                            <td class="px-4 py-2.5 text-center">
                                                <button
                                                    wire:click="downloadVideo('{{ $fmt['id'] }}')"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors"
                                                >
                                                    <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M480 352h-133.5l-45.25 45.25C289.2 409.3 273.1 416 256 416s-33.16-6.656-45.25-18.75L165.5 352H32c-17.67 0-32 14.33-32 32v96c0 17.67 14.33 32 32 32h448c17.67 0 32-14.33 32-32v-96C512 366.3 497.7 352 480 352zM432 456c-13.2 0-24-10.8-24-24c0-13.2 10.8-24 24-24s24 10.8 24 24C456 445.2 445.2 456 432 456zM233.4 374.6C239.6 380.9 247.8 384 256 384s16.38-3.125 22.62-9.375l128-128c12.49-12.5 12.49-32.75 0-45.25c-12.5-12.5-32.76-12.5-45.25 0L288 274.8V32c0-17.67-14.33-32-32-32C238.3 0 224 14.33 224 32v242.8L150.6 201.4c-12.49-12.5-32.75-12.5-45.25 0c-12.49 12.5-12.49 32.75 0 45.25L233.4 374.6z"/></svg>
                                                    Download
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if ($activeTab === 'audio')
                        <div class="p-0">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-neutral-200 bg-neutral-50">
                                        <th class="text-left px-4 py-2.5 font-semibold text-neutral-700">File type</th>
                                        <th class="text-left px-4 py-2.5 font-semibold text-neutral-700">Format</th>
                                        <th class="text-center px-4 py-2.5 font-semibold text-neutral-700">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->audioFormats as $fmt)
                                        <tr class="border-b border-neutral-100 hover:bg-neutral-50 transition-colors">
                                            <td class="px-4 py-2.5 text-neutral-900 whitespace-nowrap">MP3 - {{ $fmt['abr'] }}kbps</td>
                                            <td class="px-4 py-2.5 text-neutral-500">Auto</td>
                                            <td class="px-4 py-2.5 text-center">
                                                <button
                                                    wire:click="downloadAudio('{{ $fmt['id'] }}')"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors"
                                                >
                                                    <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M480 352h-133.5l-45.25 45.25C289.2 409.3 273.1 416 256 416s-33.16-6.656-45.25-18.75L165.5 352H32c-17.67 0-32 14.33-32 32v96c0 17.67 14.33 32 32 32h448c17.67 0 32-14.33 32-32v-96C512 366.3 497.7 352 480 352zM432 456c-13.2 0-24-10.8-24-24c0-13.2 10.8-24 24-24s24 10.8 24 24C456 445.2 445.2 456 432 456zM233.4 374.6C239.6 380.9 247.8 384 256 384s16.38-3.125 22.62-9.375l128-128c12.49-12.5 12.49-32.75 0-45.25c-12.5-12.5-32.76-12.5-45.25 0L288 274.8V32c0-17.67-14.33-32-32-32C238.3 0 224 14.33 224 32v242.8L150.6 201.4c-12.49-12.5-32.75-12.5-45.25 0c-12.49 12.5-12.49 32.75 0 45.25L233.4 374.6z"/></svg>
                                                    Download
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="text-center">
            <button wire:click="goBack" class="text-sm text-neutral-400 hover:text-neutral-600 transition-colors underline underline-offset-2">
                Choose a different URL
            </button>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-neutral-200 p-8">
            <flux:field>
                <flux:label class="text-sm font-semibold text-neutral-700">YouTube URL</flux:label>
                <div class="flex gap-3 mt-1.5">
                    <div class="relative flex-1">
                        <flux:input
                            wire:model.live.debounce.800ms="url"
                            type="url"
                            class="w-full text-neutral-900"
                            placeholder="https://youtube.com/watch?v=..."
                        />
                        @if ($isLoading)
                            <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                <flux:icon name="arrow-path" class="size-4 text-neutral-400 animate-spin" />
                            </div>
                        @endif
                    </div>
                </div>
                <flux:error name="url" />
            </flux:field>
        </div>
    @endif
</div>
