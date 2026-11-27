<?php

declare(strict_types=1);

use App\Http\Controllers\BookmarkCardsController;
use App\Http\Controllers\BookmarkCardsFileController;
use App\Http\Controllers\DemoSignInController;
use App\Http\Controllers\DownloadCardsController;
use App\Http\Controllers\LiveSearchController;
use App\Http\Controllers\SearchHitController;
use Illuminate\Support\Facades\Route;

// Lynx serves every page, the home page included, from its catch-all route; these come before it.

Route::get('/search/results', LiveSearchController::class)->middleware('throttle:120,1')->name('search.results');
Route::post('/search/hit', SearchHitController::class)->middleware('throttle:60,1')->name('search.hit');
Route::get('/bookmarks/cards', BookmarkCardsController::class)->middleware('throttle:60,1')->name('bookmarks.cards');
Route::get('/bookmarks/cards/{language}.json', BookmarkCardsFileController::class)->where('language', '[a-z]{2,10}')->middleware('throttle:60,1')->name('bookmarks.cards-file');
Route::get('/downloads/cards/{language}.json', DownloadCardsController::class)->where('language', '[a-z]{2,10}')->middleware('throttle:60,1')->name('downloads.cards');
Route::post('/demo/sign-in/{account}', DemoSignInController::class)->middleware('throttle:20,1')->name('demo.sign-in');
