<?php

namespace App\Livewire\Games;

use App\Games\Minesweeper\MinesweeperGame;
use App\Games\Minesweeper\MinesweeperEngine;
use App\Services\UserBestScoreService;
use Livewire\Component;
use Livewire\Attributes\On;

class Minesweeper extends Component
{
    public array $state = [];
    public bool $showInstructions = false;
    public string $selectedDifficulty = 'beginner';

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $game = new MinesweeperGame();
        $this->state = $game->newGameState();
        $this->showInstructions = false;
    }

    public function startGame()
    {
        if (!$this->state['gameStarted']) {
            $this->state = MinesweeperEngine::applyMove($this->state, ['action' => 'start_game']);
        }
    }

    public function newGame()
    {
        $this->resetGame();
    }

    public function changeDifficulty(string $difficulty)
    {
        $this->selectedDifficulty = $difficulty;
        $this->state = MinesweeperEngine::newGame($difficulty);
    }

    public function revealCell(int $x, int $y)
    {
        if ($this->state['gameOver'] || $this->state['gameWon']) {
            return;
        }

        $this->state = MinesweeperEngine::applyMove($this->state, [
            'action' => 'reveal_cell',
            'x' => $x,
            'y' => $y
        ]);

        // Update best score if game is won
        if ($this->state['gameWon']) {
            $this->updateBestScore();
        }
    }

    public function flagCell(int $x, int $y)
    {
        if ($this->state['gameOver'] || $this->state['gameWon']) {
            return;
        }

        $this->state = MinesweeperEngine::applyMove($this->state, [
            'action' => 'flag_cell',
            'x' => $x,
            'y' => $y
        ]);
    }

    public function getProgressPercentage(): int
    {
        if (!$this->state['gameStarted']) {
            return 0;
        }

        $totalCells = $this->state['width'] * $this->state['height'];
        $safeCells = $totalCells - $this->state['mineCount'];
        
        return intval(($this->state['squaresRevealed'] / $safeCells) * 100);
    }

    public function getScore(): int
    {
        return MinesweeperEngine::calculateScore($this->state);
    }

    public function getMineCount(): int
    {
        return MinesweeperEngine::getMineCount($this->state);
    }

    public function getFlagCount(): int
    {
        return MinesweeperEngine::getFlagCount($this->state);
    }

    public function getRevealedCount(): int
    {
        return MinesweeperEngine::getRevealedCount($this->state);
    }

    public function isWon(): bool
    {
        return MinesweeperEngine::isWon($this->state);
    }

    public function isLost(): bool
    {
        return MinesweeperEngine::isLost($this->state);
    }

    public function isGameOver(): bool
    {
        return MinesweeperEngine::isGameOver($this->state);
    }

    public function isGameComplete(): bool
    {
        return $this->isWon();
    }

    public function getGameState(): array
    {
        return MinesweeperEngine::getGameState($this->state);
    }

    public function getBoard(): array
    {
        return MinesweeperEngine::getBoard($this->state);
    }

    public function formatTime(int $seconds): string
    {
        $minutes = intval($seconds / 60);
        $seconds = $seconds % 60;
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function updateBestScore()
    {
        if ($this->state['gameWon'] && auth()->check()) {
            $score = $this->getScore();
            $service = app(UserBestScoreService::class);
            $service->updateBestScore(auth()->id(), 'minesweeper', $score);
        }
    }

    public function render()
    {
        return view('livewire.games.minesweeper');
    }
}
