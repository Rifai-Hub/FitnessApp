<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    return Auth::user()->hasAnyRole(['admin', 'superadmin'])
        ? redirect()->route('admin.dashboard')
        : redirect()->route('member.placeholder');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'auth.login')->name('login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', function () {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    Route::middleware('role:member')->get('/member', function () {
        return view('member-placeholder');
    })->name('member.placeholder');

    Route::middleware('role:admin|superadmin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('/dashboard', 'admin.dashboard')->name('dashboard');
        Route::livewire('/members', 'admin.members')->name('members');
        Route::livewire('/membership-plans', 'admin.membership-plans')->name('membership-plans');
        Route::livewire('/personal-trainer-packages', 'admin.personal-trainer-packages')->name('personal-trainer-packages');
        Route::livewire('/schedules', 'admin.schedules')->name('schedules');
        Route::livewire('/revenue-report', 'admin.revenue-report')->name('revenue-report');
    });
});
