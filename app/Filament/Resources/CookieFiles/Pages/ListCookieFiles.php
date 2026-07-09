<?php

namespace App\Filament\Resources\CookieFiles\Pages;

use App\Filament\Resources\CookieFiles\CookieFileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCookieFiles extends ListRecords
{
    protected static string $resource = CookieFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
