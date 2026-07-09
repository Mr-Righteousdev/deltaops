<?php

namespace App\Filament\Resources\Downloads;

use App\Enums\DownloadStatus;
use App\Filament\Resources\Downloads\Pages\ManageDownloads;
use App\Models\Download;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class DownloadResource extends Resource
{
    protected static ?string $model = Download::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|\UnitEnum|null $navigationGroup = 'Downloads';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('url')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\TextColumn::make('format')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mp4' => 'info',
                        'mp3' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('quality'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (DownloadStatus $state): string => match ($state) {
                        DownloadStatus::Pending => 'gray',
                        DownloadStatus::Processing => 'warning',
                        DownloadStatus::Completed => 'success',
                        DownloadStatus::Failed => 'danger',
                    }),
                Tables\Columns\TextColumn::make('file_name')
                    ->limit(30)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('file_size')
                    ->formatStateUsing(fn (?int $state) => $state ? round($state / 1024 / 1024, 2).' MB' : '-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->recordActions([])
            ->toolbarActions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDownloads::route('/'),
        ];
    }
}
