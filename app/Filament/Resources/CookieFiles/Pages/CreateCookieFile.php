<?php

namespace App\Filament\Resources\CookieFiles\Pages;

use App\Filament\Resources\CookieFiles\CookieFileResource;
use App\Models\CookieFile;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Config;

class CreateCookieFile extends CreateRecord
{
    protected static string $resource = CookieFileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        CookieFile::where('is_active', true)->update(['is_active' => false]);

        $data['uploaded_at'] = now();
        $data['original_name'] = $data['file_path'];
        $data['is_active'] = true;

        Config::set('downloader.cookies_path', storage_path('app/'.$data['file_path']));

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return CookieFileResource::getUrl('index');
    }
}
