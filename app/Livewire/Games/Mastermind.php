<?php

namespace App\Livewire\Games;

use App\Games\Mastermind\MastermindGame;
use App\Games\Mastermind\MastermindEngine;
use App\Services\UserBestScoreService;
use Livewire\Component;
use Livewire\Attributes\On;

class Mastermind extends Component
{
    public array $state = [];
    public bool $showInstructions = false;
    public bool $showHint = false;
    public string $selectedDifficulty = 'medium';

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $game = new MastermindGame();
        $this->state = $game->newGameState();
        $this->showInstructions = false;
        $this->showHint = false;
    }

    public function startGame()
    {
        if (!$this->state['gameStarted']) {
            $this->state = MastermindEngine::applyMove($this->state, ['action' => 'start_game']);
        }
    }

    public function newGame(string $difficulty = 'medium')
    {
        $this->selectedDifficulty = $difficulty;
        $this->state = MastermindEngine::applyMove($this->state, [
            'action' => 'new_game',
            'difficulty' => $difficulty
        ]);
        $this->showHint = false;
    }

    public function selectColor(int $position, string $color)
    {
        if ($this->state['gameOver']) {
            return;
        }

        $game = new MastermindGame();
        $move = [
            'action' => 'select_color',
            'position' => $position,
            'color' => $color
        ];

        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
        }
    }

    public function submitGuess()
    {
        if ($this->state['gameOver']) {
            return;
        }

        $game = new MastermindGame();
        $move = ['action' => 'submit_guess'];

        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
            
            // Update best score if game is won
            if ($this->state['gameWon'] && auth()->check()) {
                $this->updateBestScore();
            }
        }
    }

    public function clearGuess()
    {
        if ($this->state['gameOver']) {
            return;
        }

        $game = new MastermindGame();
        $move = ['action' => 'clear_guess'];

        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
        }
    }

    public function getHint()
    {
        $this->showHint = true;
    }

    public function applyHint(array $hintData)
    {
        if (isset($hintData['suggestion'])) {
            // Auto-fill the suggested colors
            foreach ($hintData['suggestion'] as $position => $color) {
                $this->selectColor($position, $color);
            }
        }
        
        $this->showHint = false;
    }

    public function getColorImage(string $color): string
    {
        return asset("images/Pieces (Red)/{$color}_piece.png");
    }

    public function getScore(): int
    {
        return MastermindEngine::calculateScore($this->state);
    }

    public function getSecretCode(): array
    {
        return MastermindEngine::getSecretCode($this->state);
    }

    public function getCurrentGuess(): array
    {
        return MastermindEngine::getCurrentGuess($this->state);
    }

    public function getGuesses(): array
    {
        return MastermindEngine::getGuesses($this->state);
    }

    public function getFeedback(): array
    {
        return MastermindEngine::getFeedback($this->state);
    }

    public function getAvailableColors(): array
    {
        return MastermindEngine::getAvailableColors($this->state);
    }

    public function getDifficulty(): string
    {
        return MastermindEngine::getDifficulty($this->state);
    }

    public function getRemainingGuesses(): int
    {
        return MastermindEngine::getRemainingGuesses($this->state);
    }

    public function getHintData(): array
    {
        return MastermindEngine::getHint($this->state);
    }

    public function canMakeGuess(): bool
    {
        return MastermindEngine::canMakeGuess($this->state);
    }

    public function canSelectColor(): bool
    {
        return MastermindEngine::canSelectColor($this->state);
    }

    public function canSubmitGuess(): bool
    {
        return MastermindEngine::canSubmitGuess($this->state);
    }

    public function isGameOver(): bool
    {
        return MastermindEngine::isGameOver($this->state);
    }

    public function isGameWon(): bool
    {
        return MastermindEngine::isGameWon($this->state);
    }

    public function isGameLost(): bool
    {
        return MastermindEngine::isGameLost($this->state);
    }

    public function getGameState(): array
    {
        return MastermindEngine::getGameState($this->state);
    }

    public function updateBestScore()
    {
        if ($this->state['gameWon'] && auth()->check()) {
            $score = $this->getScore();
            $service = app(UserBestScoreService::class);
            $service->updateBestScore(auth()->id(), 'mastermind', $score);
        }
    }

    public function render()
    {
        return view('livewire.games.mastermind');
    }
}
