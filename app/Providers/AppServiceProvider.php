<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Games\GameRegistry;
use App\Games\Samples\SampleGame;
use App\Games\TicTacToe\TicTacToeGame;
use App\Games\TwentyFortyEight\TwentyFortyEightGame;
use App\Games\Chess\ChessGame;
use App\Games\Yahtzee\YahtzeeGame;
use App\Games\Sudoku\SudokuGame;
use App\Games\Blackjack\BlackjackGame;
use App\Games\Snake\SnakeGame;
use App\Games\Memory\MemoryGame;
use App\Games\Tetris\TetrisGame;
use App\Games\Minesweeper\MinesweeperGame;
use App\Games\Poker\PokerGame;
use App\Games\GoFish\GoFishGame;
use App\Games\CrazyEights\CrazyEightsGame;
use App\Games\SpiderSolitaire\SpiderSolitaireGame;
use App\Games\Farkle\FarkleGame;
use App\Games\Mastermind\MastermindGame;
use App\Games\Phase10\Phase10Game;
use App\Games\WordDetective\WordDetectiveGame;
use App\Games\Slitherlink\SlitherlinkGame;
use App\Games\HexagonSlitherlink\HexagonSlitherlinkGame;
use App\Games\Hiking\HikingGame;

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
            $registry->register(new SudokuGame());
            $registry->register(new BlackjackGame());
            $registry->register(new SnakeGame());
            $registry->register(new MemoryGame());
            $registry->register(new TetrisGame());
            $registry->register(new MinesweeperGame());
            $registry->register(new PokerGame());
            $registry->register(new GoFishGame());
            $registry->register(new CrazyEightsGame());
            $registry->register(new SpiderSolitaireGame());
            $registry->register(new FarkleGame());
            $registry->register(new MastermindGame());
            $registry->register(new Phase10Game());
            $registry->register(new WordDetectiveGame());
            $registry->register(new SlitherlinkGame());
            $registry->register(new HexagonSlitherlinkGame());
            $registry->register(new HikingGame());
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
