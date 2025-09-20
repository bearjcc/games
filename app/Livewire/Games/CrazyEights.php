<?php

namespace App\Livewire\Games;

use App\Games\CrazyEights\CrazyEightsGame;
use App\Games\CrazyEights\CrazyEightsEngine;
use App\Services\UserBestScoreService;
use Livewire\Component;
use Livewire\Attributes\On;

class CrazyEights extends Component
{
    public array $state = [];
    public bool $showInstructions = false;

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $game = new CrazyEightsGame();
        $this->state = $game->newGameState();
        $this->showInstructions = false;
    }

    public function startGame()
    {
        if (!$this->state['gameStarted']) {
            $this->state = CrazyEightsEngine::applyMove($this->state, ['action' => 'start_game']);
        }
    }

    public function newGame()
    {
        $this->resetGame();
    }

    public function playCard(int $cardIndex, string $newSuit = null)
    {
        if ($this->state['gameOver'] || $this->state['currentPlayer'] !== 0) {
            return;
        }

        $move = [
            'action' => 'play_card',
            'cardIndex' => $cardIndex
        ];

        if ($newSuit) {
            $move['newSuit'] = $newSuit;
        }

        $this->state = CrazyEightsEngine::applyMove($this->state, $move);

        // If it's still the player's turn, process AI moves
        if (!$this->state['gameOver'] && $this->state['currentPlayer'] !== 0) {
            $this->processAIMoves();
        }

        // Update best score if game is won
        if ($this->state['gameOver'] && $this->state['winner'] === 0) {
            $this->updateBestScore();
        }
    }

    public function drawCard()
    {
        if ($this->state['gameOver'] || $this->state['currentPlayer'] !== 0) {
            return;
        }

        $this->state = CrazyEightsEngine::applyMove($this->state, ['action' => 'draw_card']);

        // If drawn card can be played, give player option to play it
        if (!$this->state['mustDraw'] && !$this->state['gameOver']) {
            // Player can now play the drawn card or pass
            return;
        }

        // If still must draw or game over, process AI moves
        if ($this->state['gameOver'] || $this->state['currentPlayer'] !== 0) {
            $this->processAIMoves();
        }
    }

    public function changeSuit(string $newSuit)
    {
        if ($this->state['gameOver']) {
            return;
        }

        $this->state = CrazyEightsEngine::applyMove($this->state, [
            'action' => 'change_suit',
            'newSuit' => $newSuit
        ]);
    }

    public function processAIMoves()
    {
        while (!$this->state['gameOver'] && $this->state['currentPlayer'] !== 0) {
            $aiMove = CrazyEightsEngine::getAIMove($this->state);
            $this->state = CrazyEightsEngine::applyMove($this->state, $aiMove);
            
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

    public function canPlayCard(array $card): bool
    {
        return CrazyEightsEngine::canPlayCard($this->state, $card);
    }

    public function getPlayableCards(): array
    {
        return CrazyEightsEngine::getPlayableCards($this->state);
    }

    public function getScore(): int
    {
        return CrazyEightsEngine::calculateScore($this->state);
    }

    public function getPlayerHand(): array
    {
        return CrazyEightsEngine::getPlayerHand($this->state);
    }

    public function getOpponentHands(): array
    {
        return CrazyEightsEngine::getOpponentHands($this->state);
    }

    public function getDiscardPile(): array
    {
        return CrazyEightsEngine::getDiscardPile($this->state);
    }

    public function getCurrentPlayer(): int
    {
        return CrazyEightsEngine::getCurrentPlayer($this->state);
    }

    public function getCurrentSuit(): string
    {
        return CrazyEightsEngine::getCurrentSuit($this->state);
    }

    public function isGameOver(): bool
    {
        return CrazyEightsEngine::isGameOver($this->state);
    }

    public function isGameComplete(): bool
    {
        return $this->isGameOver();
    }

    public function getGameState(): array
    {
        return CrazyEightsEngine::getGameState($this->state);
    }

    public function updateBestScore()
    {
        if ($this->state['gameOver'] && $this->state['winner'] === 0 && auth()->check()) {
            $score = $this->getScore();
            $service = app(UserBestScoreService::class);
            $service->updateBestScore(auth()->id(), 'crazy-eights', $score);
        }
    }

    public function render()
    {
        return view('livewire.games.crazy-eights');
    }
}
