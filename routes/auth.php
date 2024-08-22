<?php

use App\Http\Controllers\Auth\TwoFactorRequiredController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Connections\GitHubController;
use App\Http\Controllers\Connections\GitLabController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('register', 'pages.auth.register')->name('register');
    Volt::route('login', 'pages.auth.login')->name('login');
    Volt::route('forgot-password', 'pages.auth.forgot-password')->name('password.request');
    Volt::route('reset-password/{token}', 'pages.auth.reset-password')->name('password.reset');
});

// GitHub Routes
Route::get('auth/github', [GitHubController::class, 'redirect'])->name('github.redirect');
Route::get('auth/github/callback', [GitHubController::class, 'callback'])->name('github.callback');

// GitLab Routes
Route::get('auth/gitlab', [GitLabController::class, 'redirect'])->name('gitlab.redirect');
Route::get('auth/gitlab/callback', [GitLabController::class, 'callback'])->name('gitlab.callback');

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'pages.auth.verify-email')->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'pages.auth.confirm-password')->name('password.confirm');

    Route::match(['get', 'post'], 'two-factor-challenge', TwoFactorRequiredController::class)
        ->name('two-factor.challenge');
});
