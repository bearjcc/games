<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Games\GameRegistry;

// Homepage -> New impressive homepage
Volt::route('/', 'games.homepage')->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');
});

// Games
Volt::route('games', 'games.index')->name('games.index');
Volt::route('games/{slug}', 'games.show')->name('games.show');
// Root-level game routes
Volt::route('tic-tac-toe', 'games.tic-tac-toe')->name('t3');
Volt::route('2048', 'games.2048')->name('twozero');
Volt::route('war', 'games.war')->name('war');
Volt::route('solitaire', 'games.solitaire')->name('solitaire');
Volt::route('nine-mens-morris', 'games.nine-mens-morris')->name('morris');
Volt::route('peg-solitaire', 'games.peg-solitaire')->name('peg');
Volt::route('connect4', 'games.connect4')->name('connect4');
Volt::route('checkers', 'games.checkers')->name('checkers');

require __DIR__.'/auth.php';
