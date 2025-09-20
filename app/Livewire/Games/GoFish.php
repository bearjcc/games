<?php

namespace App\Livewire\Games;

use App\Games\GoFish\GoFishGame;
use App\Games\GoFish\GoFishEngine;
use App\Services\UserBestScoreService;
use Livewire\Component;
use Livewire\Attributes\On;

class GoFish extends Component
{
    public array $state = [];
    public bool $showInstructions = false;

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $game = new GoFishGame();
        $this->state = $game->newGameState();
        $this->showInstructions = false;
    }

    public function startGame()
    {
        if (!$this->state['gameStarted']) {
            $this->state = GoFishEngine::applyMove($this->state, ['action' => 'start_game']);
        }
    }

    public function newGame()
    {
        $this->resetGame();
    }

    public function askForCard(int $targetPlayer, string $rank)
    {
        if ($this->state['gameOver'] || $this->state['currentPlayer'] !== 0) {
            return;
        }

        $this->state = GoFishEngine::applyMove($this->state, [
            'action' => 'ask_for_card',
            'targetPlayer' => $targetPlayer,
            'rank' => $rank
        ]);

        // If it's still the player's turn, process AI moves
        if (!$this->state['gameOver'] && $this->state['currentPlayer'] !== 0) {
            $this->processAIMoves();
        }

        // Update best score if game is won
        if ($this->state['gameOver'] && $this->state['winner'] === 0) {
            $this->updateBestScore();
        }
    }

    public function processAIMoves()
    {
        while (!$this->state['gameOver'] && $this->state['currentPlayer'] !== 0) {
            $aiMove = GoFishEngine::getAIMove($this->state);
            
            if ($aiMove['action'] === 'ask_for_card') {
                $this->state = GoFishEngine::applyMove($this->state, $aiMove);
            } else {
                break;
            }
            
            // Small delay for better UX
            usleep(1000000); // 1 second
        }
    }

    public function getSuitSymbol(string $suit): string
    {
        return match($suit) {
            'hearts' => '♥',
            'diamonds' => '♦',
            'clubs' => '♣',
            'spades' => '♠',
            default => '?'
        };
    }

    public function getPossibleAsks(): array
    {
        return GoFishEngine::getPossibleAsks($this->state);
    }

    public function getScore(): int
    {
        return GoFishEngine::calculateScore($this->state);
    }

    public function getPlayerHand(): array
    {
        return GoFishEngine::getPlayerHand($this->state);
    }

    public function getOpponentHands(): array
    {
        return GoFishEngine::getOpponentHands($this->state);
    }

    public function getDeck(): array
    {
        return GoFishEngine::getDeck($this->state);
    }

    public function getSets(): array
    {
        return GoFishEngine::getSets($this->state);
    }

    public function getCurrentPlayer(): int
    {
        return GoFishEngine::getCurrentPlayer($this->state);
    }

    public function canAskForCard(string $rank): bool
    {
        return GoFishEngine::canAskForCard($this->state, $rank);
    }

    public function isGameOver(): bool
    {
        return GoFishEngine::isGameOver($this->state);
    }

    public function isGameComplete(): bool
    {
        return $this->isGameOver();
    }

    public function getGameState(): array
    {
        return GoFishEngine::getGameState($this->state);
    }

    public function updateBestScore()
    {
        if ($this->state['gameOver'] && $this->state['winner'] === 0 && auth()->check()) {
            $score = $this->getScore();
            $service = app(UserBestScoreService::class);
            $service->updateBestScore(auth()->id(), 'go-fish', $score);
        }
    }

    public function render()
    {
        return view('livewire.games.go-fish');
    }
}
