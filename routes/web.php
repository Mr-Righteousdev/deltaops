<?php

use App\Enums\DownloadStatus;
use App\Livewire\DownloadForm;
use App\Models\Download;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

Route::view('/', 'welcome')->name('home');

Route::livewire('/download', DownloadForm::class)->name('download');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::get('/download/serve/{download}', function (Download $download): BinaryFileResponse {
    abort_unless($download->status === DownloadStatus::Completed, 404);
    abort_unless($download->download_token === request('token'), 404);

    $path = $download->file_path;

    abort_unless($path && file_exists($path), 404);

    return response()->download($path, $download->file_name);
})->name('download.serve');

require __DIR__.'/settings.php';
