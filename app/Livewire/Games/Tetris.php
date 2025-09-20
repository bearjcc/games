<?php

namespace App\Livewire\Games;

use App\Games\Tetris\TetrisGame;
use App\Games\Tetris\TetrisEngine;
use App\Services\UserBestScoreService;
use Livewire\Component;
use Livewire\Attributes\On;

class Tetris extends Component
{
    public array $state = [];
    public bool $showInstructions = false;
    public string $selectedDifficulty = 'medium';

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $game = new TetrisGame();
        $this->state = $game->newGameState();
        $this->showInstructions = false;
    }

    public function startGame()
    {
        if (!$this->state['gameStarted']) {
            $this->state = TetrisEngine::applyMove($this->state, ['action' => 'start_game']);
        }
    }

    public function togglePause()
    {
        if ($this->state['gameStarted'] && !$this->state['gameOver']) {
            $action = $this->state['paused'] ? 'resume_game' : 'pause_game';
            $this->state = TetrisEngine::applyMove($this->state, ['action' => $action]);
        }
    }

    public function newGame()
    {
        $this->resetGame();
    }

    public function movePiece(string $direction)
    {
        if (!$this->state['gameStarted'] || $this->state['gameOver'] || $this->state['paused']) {
            return;
        }

        $actionMap = [
            'left' => 'move_left',
            'right' => 'move_right',
            'down' => 'move_down'
        ];

        if (isset($actionMap[$direction])) {
            $this->state = TetrisEngine::applyMove($this->state, ['action' => $actionMap[$direction]]);
        }
    }

    public function rotatePiece()
    {
        if (!$this->state['gameStarted'] || $this->state['gameOver'] || $this->state['paused']) {
            return;
        }

        $this->state = TetrisEngine::applyMove($this->state, ['action' => 'rotate']);
    }

    public function hardDrop()
    {
        if (!$this->state['gameStarted'] || $this->state['gameOver'] || $this->state['paused']) {
            return;
        }

        $this->state = TetrisEngine::applyMove($this->state, ['action' => 'hard_drop']);
    }

    public function gameTick()
    {
        if ($this->state['gameStarted'] && !$this->state['gameOver'] && !$this->state['paused']) {
            $this->state = TetrisEngine::applyMove($this->state, ['action' => 'tick']);
        }
    }

    public function isCurrentPieceAt(int $x, int $y): bool
    {
        if (!isset($this->state['currentPiece']) || !isset($this->state['currentPosition'])) {
            return false;
        }

        $piece = $this->state['currentPiece'];
        $position = $this->state['currentPosition'];
        $rotation = $this->state['currentRotation'] ?? 0;

        $shape = TetrisEngine::getRotatedShape($piece['shape'], $rotation);

        for ($py = 0; $py < count($shape); $py++) {
            for ($px = 0; $px < count($shape[$py]); $px++) {
                if ($shape[$py][$px] === '#') {
                    $boardX = $position['x'] + $px;
                    $boardY = $position['y'] + $py;
                    
                    if ($boardX === $x && $boardY === $y) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function getProgressPercentage(): int
    {
        if (!$this->state['gameStarted']) {
            return 0;
        }

        $totalCells = TetrisEngine::BOARD_WIDTH * TetrisEngine::BOARD_HEIGHT;
        $filledCells = 0;

        foreach ($this->state['board'] as $row) {
            foreach ($row as $cell) {
                if ($cell !== ' ') {
                    $filledCells++;
                }
            }
        }

        return intval(($filledCells / $totalCells) * 100);
    }

    public function getScore(): int
    {
        return TetrisEngine::calculateScore($this->state);
    }

    public function getLevel(): int
    {
        return TetrisEngine::getLevel($this->state);
    }

    public function getLinesCleared(): int
    {
        return TetrisEngine::getLinesCleared($this->state);
    }

    public function isGameOver(): bool
    {
        return TetrisEngine::isGameOver($this->state);
    }

    public function isGameComplete(): bool
    {
        return TetrisEngine::isGameOver($this->state);
    }

    public function getGameState(): array
    {
        return TetrisEngine::getGameState($this->state);
    }

    public function getPlayField(): array
    {
        return TetrisEngine::getPlayField($this->state);
    }

    public function getCurrentPiece(): array
    {
        return TetrisEngine::getCurrentPiece($this->state);
    }

    public function getNextPiece(): array
    {
        return TetrisEngine::getNextPiece($this->state);
    }

    public function updateBestScore()
    {
        if ($this->state['gameOver'] && auth()->check()) {
            $score = $this->getScore();
            $service = app(UserBestScoreService::class);
            $service->updateBestScore(auth()->id(), 'tetris', $score);
        }
    }

    public function render()
    {
        return view('livewire.games.tetris');
    }
}
