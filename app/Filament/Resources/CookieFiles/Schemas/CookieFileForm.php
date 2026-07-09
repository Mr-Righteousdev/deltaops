<?php

namespace App\Filament\Resources\CookieFiles\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class CookieFileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('file_path')
                    ->label('Cookies File')
                    ->helperText('Upload a cookies.txt file exported from a logged-in YouTube browser session.')
                    ->acceptedFileTypes(['text/plain'])
                    ->maxSize(1024)
                    ->required()
                    ->disk('local')
                    ->directory('cookies'),
            ]);
    }
}
