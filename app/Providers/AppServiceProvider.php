<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Games\GameRegistry;
use App\Games\Samples\SampleGame;
use App\Games\TicTacToe\TicTacToeGame;
use App\Games\TwentyFortyEight\TwentyFortyEightGame;
use App\Games\Chess\ChessGame;
use App\Games\Yahtzee\YahtzeeGame;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GameRegistry::class, function () {
            $registry = new GameRegistry();
            // Remove SampleGame from public listing
            $registry->register(new TicTacToeGame());
            $registry->register(new TwentyFortyEightGame());
            $registry->register(new \App\Games\War\WarGame());
            $registry->register(new \App\Games\Solitaire\SolitaireGame());
            $registry->register(new \App\Games\NineMensMorris\NineMensMorrisGame());
            $registry->register(new \App\Games\PegSolitaire\PegSolitaireGame());
            $registry->register(new \App\Games\Connect4\Connect4Game());
            $registry->register(new \App\Games\Checkers\CheckersGame());
            $registry->register(new ChessGame());
            $registry->register(new YahtzeeGame());
            return $registry;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
