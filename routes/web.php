<?php

declare(strict_types=1);

use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\VerifyAppRedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));

Route::get('/verify', VerifyAppRedirectController::class)->name('verify.app');
Route::get('/privacy', [LegalPageController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms', [LegalPageController::class, 'terms'])->name('legal.terms');
