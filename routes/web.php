<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware(['auth', 'verified'])
    ->group(function () {
        Route::redirect('/', 'models')->name('home');

        Route::get('models/download/{model:uuid}', 'App\Http\Controllers\DownloadController@download')->name('models.download');

        Route::livewire('models', 'pages::models.index')->name('models');
        Route::livewire('models/upload', 'pages::models.upload')->name('models.upload');
        Route::livewire('models/import', 'pages::models.import')->name('models.import');
        Route::livewire('models/{model:slug}', 'pages::models.view')->name('models.view');

        Route::livewire('collections', 'pages::collections.index')->name('collections');
        Route::livewire('collections/{collection:slug}', 'pages::collections.view')->name('collections.view');
    });

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}/accept', 'pages::teams.accept-invitation')->name('invitations.accept');
});

require __DIR__.'/settings.php';
