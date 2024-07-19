<?php

use FriendsOfBotble\Thawani\Http\Controllers\ThawaniController;
use Illuminate\Support\Facades\Route;

Route::group([
    'controller' => ThawaniController::class,
    'prefix' => 'thawani/payment',
    'as' => 'thawani.payment.',
    'middleware' => ['core', 'web'],
], function () {
    Route::get('callback', [
        'as' => 'callback',
        'uses' => 'getCallback',
    ]);

    Route::get('cancel', [
        'as' => 'cancel',
        'uses' => 'getCancel',
    ]);
});
