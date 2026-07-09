<?php

namespace App\Filament\Resources\CookieFiles\Pages;

use App\Filament\Resources\CookieFiles\CookieFileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCookieFile extends EditRecord
{
    protected static string $resource = CookieFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
