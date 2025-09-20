<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Games\GameRegistry;
use App\Games\Samples\SampleGame;
use App\Games\TicTacToe\TicTacToeGame;
use App\Games\TwentyFortyEight\TwentyFortyEightGame;

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
