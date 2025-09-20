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
Volt::route('chess', 'games.chess')->name('chess');
Volt::route('yahtzee', 'games.yahtzee')->name('yahtzee');
Volt::route('sudoku', 'games.sudoku')->name('sudoku');
Volt::route('blackjack', 'games.blackjack')->name('blackjack');
Volt::route('snake', 'games.snake')->name('snake');
Volt::route('memory', 'games.memory')->name('memory');
Volt::route('tetris', 'games.tetris')->name('tetris');
Volt::route('minesweeper', 'games.minesweeper')->name('minesweeper');
Volt::route('poker', 'games.poker')->name('poker');
Volt::route('go-fish', 'games.go-fish')->name('go-fish');
Volt::route('crazy-eights', 'games.crazy-eights')->name('crazy-eights');
Volt::route('spider-solitaire', 'games.spider-solitaire')->name('spider-solitaire');
Volt::route('farkle', 'games.farkle')->name('farkle');
Volt::route('mastermind', 'games.mastermind')->name('mastermind');
Volt::route('phase10', 'games.phase10')->name('phase10');
Volt::route('word-detective', 'games.word-detective')->name('word-detective');
Volt::route('slitherlink', 'games.slitherlink')->name('slitherlink');
Volt::route('hexagon-slitherlink', 'games.hexagon-slitherlink')->name('hexagon-slitherlink');

// Print routes
Route::get('games/{game}/print', function ($game) {
    $puzzleData = session('print_puzzle');
    
    if (!$puzzleData) {
        abort(404);
    }
    
    return view('games.print', [
        'game' => $game,
        'puzzleData' => $puzzleData
    ]);
})->name('games.print');

require __DIR__.'/auth.php';
