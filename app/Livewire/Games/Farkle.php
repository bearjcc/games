<?php

namespace App\Livewire\Games;

use App\Games\Farkle\FarkleGame;
use App\Games\Farkle\FarkleEngine;
use App\Services\UserBestScoreService;
use Livewire\Component;
use Livewire\Attributes\On;

class Farkle extends Component
{
    public array $state = [];
    public bool $showInstructions = false;
    public bool $showHint = false;
    public array $selectedDiceIndices = [];

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $game = new FarkleGame();
        $this->state = $game->newGameState();
        $this->showInstructions = false;
        $this->showHint = false;
        $this->selectedDiceIndices = [];
    }

    public function startGame()
    {
        if (!$this->state['gameStarted']) {
            $this->state = FarkleEngine::applyMove($this->state, ['action' => 'start_game']);
        }
    }

    public function rollDice()
    {
        if ($this->state['gameOver']) {
            return;
        }

        $game = new FarkleGame();
        $move = ['action' => 'roll_dice'];

        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
            
            // Trigger enhanced dice rolling animation
            $this->dispatch('dice-rolled', [
                'values' => $this->state['dice'],
                'turnScore' => $this->state['turnScore']
            ]);
            
            // Update best score if game is won
            if ($this->state['gameOver'] && $this->state['winner'] === 0 && auth()->check()) {
                $this->updateBestScore();
            }
        }

        $this->clearSelection();
    }

    public function selectDice(array $diceIndices)
    {
        if ($this->state['gameOver']) {
            return;
        }

        $game = new FarkleGame();
        $move = [
            'action' => 'select_dice',
            'diceIndices' => $diceIndices
        ];

        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
        }

        $this->clearSelection();
    }

    public function bankPoints()
    {
        if ($this->state['gameOver']) {
            return;
        }

        $game = new FarkleGame();
        $move = ['action' => 'bank_points'];

        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
        }

        $this->clearSelection();
    }

    public function newGame()
    {
        $this->resetGame();
    }

    public function toggleDiceSelection(int $diceIndex)
    {
        if ($this->state['turnPhase'] !== 'selecting') {
            return;
        }

        if (in_array($diceIndex, $this->selectedDiceIndices)) {
            $this->selectedDiceIndices = array_diff($this->selectedDiceIndices, [$diceIndex]);
        } else {
            $this->selectedDiceIndices[] = $diceIndex;
        }
    }

    public function confirmSelection()
    {
        if (!empty($this->selectedDiceIndices)) {
            $this->selectDice($this->selectedDiceIndices);
        }
    }

    public function clearSelection()
    {
        $this->selectedDiceIndices = [];
    }

    public function getHint()
    {
        $this->showHint = true;
    }

    public function applyHint(array $hintData)
    {
        if (isset($hintData['action'])) {
            switch ($hintData['action']) {
                case 'roll_dice':
                    $this->rollDice();
                    break;
                case 'bank_points':
                    $this->bankPoints();
                    break;
            }
        } elseif (isset($hintData['combination'])) {
            // Auto-select the suggested combination
            $dice = $this->state['dice'];
            $combination = $hintData['combination'];
            $indices = [];
            
            foreach ($combination['dice'] as $value) {
                $index = array_search($value, $dice);
                if ($index !== false) {
                    $indices[] = $index;
                    $dice[$index] = null; // Mark as used
                }
            }
            
            if (!empty($indices)) {
                $this->selectDice($indices);
            }
        }
        
        $this->showHint = false;
    }

    public function getDiceImage(int $value): string
    {
        return asset("images/Dice/die_{$value}.png");
    }

    public function getScore(): int
    {
        return FarkleEngine::calculateScore($this->state);
    }

    public function getDice(): array
    {
        return FarkleEngine::getDice($this->state);
    }

    public function getSelectedDice(): array
    {
        return FarkleEngine::getSelectedDice($this->state);
    }

    public function getTurnScore(): int
    {
        return FarkleEngine::getTurnScore($this->state);
    }

    public function getCurrentPlayer(): int
    {
        return FarkleEngine::getCurrentPlayer($this->state);
    }

    public function getPlayerScores(): array
    {
        return FarkleEngine::getPlayerScores($this->state);
    }

    public function getScoringCombinations(): array
    {
        return FarkleEngine::getScoringCombinations($this->state);
    }

    public function getHintData(): array
    {
        return FarkleEngine::getHint($this->state);
    }

    public function canRollDice(): bool
    {
        return FarkleEngine::canRollDice($this->state);
    }

    public function canBankPoints(): bool
    {
        return FarkleEngine::canBankPoints($this->state);
    }

    public function canSelectDice(): bool
    {
        return FarkleEngine::canSelectDice($this->state);
    }

    public function isGameOver(): bool
    {
        return FarkleEngine::isGameOver($this->state);
    }

    public function isGameWon(): bool
    {
        return $this->state['gameOver'] && $this->state['winner'] === 0;
    }

    public function getGameState(): array
    {
        return FarkleEngine::getGameState($this->state);
    }

    public function updateBestScore()
    {
        if ($this->state['gameOver'] && $this->state['winner'] === 0 && auth()->check()) {
            $score = $this->getScore();
            $service = app(UserBestScoreService::class);
            $service->updateBestScore(auth()->id(), 'farkle', $score);
        }
    }

    public function render()
    {
        return view('livewire.games.farkle');
    }
}
