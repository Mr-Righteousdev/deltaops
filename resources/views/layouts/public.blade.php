<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-neutral-50 antialiased">
        <div class="flex min-h-svh flex-col items-center justify-start gap-6 px-6 pt-16 md:px-10 md:pt-24">
            <div class="flex w-full max-w-4xl flex-col gap-2">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-medium mb-6" wire:navigate>
                    <span class="flex h-9 w-9 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9" />
                    </span>
                    <span class="text-sm font-semibold text-neutral-900">{{ config('app.name', 'Delta TV') }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
