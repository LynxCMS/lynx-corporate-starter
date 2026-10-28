<?php

declare(strict_types=1);

use App\Http\Controllers\BookmarkCardsController;
use App\Http\Controllers\LiveSearchController;
use App\Http\Controllers\SearchHitController;
use Illuminate\Support\Facades\Route;

// Lynx serves every page, the home page included, from its catch-all route; these come before it.

Route::get('/search/results', LiveSearchController::class)->middleware('throttle:120,1')->name('search.results');
Route::post('/search/hit', SearchHitController::class)->middleware('throttle:60,1')->name('search.hit');
Route::get('/bookmarks/cards', BookmarkCardsController::class)->middleware('throttle:60,1')->name('bookmarks.cards');
