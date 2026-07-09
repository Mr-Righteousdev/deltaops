<?php

namespace App\Filament\Resources\CookieFiles;

use App\Filament\Resources\CookieFiles\Pages\CreateCookieFile;
use App\Filament\Resources\CookieFiles\Pages\EditCookieFile;
use App\Filament\Resources\CookieFiles\Pages\ListCookieFiles;
use App\Filament\Resources\CookieFiles\Schemas\CookieFileForm;
use App\Filament\Resources\CookieFiles\Tables\CookieFilesTable;
use App\Models\CookieFile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CookieFileResource extends Resource
{
    protected static ?string $model = CookieFile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Downloads';

    public static function form(Schema $schema): Schema
    {
        return CookieFileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CookieFilesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCookieFiles::route('/'),
            'create' => CreateCookieFile::route('/create'),
            'edit' => EditCookieFile::route('/{record}/edit'),
        ];
    }
}
