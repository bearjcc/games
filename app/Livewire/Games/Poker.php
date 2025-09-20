<?php

namespace App\Livewire\Games;

use App\Games\Poker\PokerGame;
use App\Games\Poker\PokerEngine;
use App\Services\UserBestScoreService;
use Livewire\Component;
use Livewire\Attributes\On;

class Poker extends Component
{
    public array $state = [];
    public bool $showInstructions = false;

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $game = new PokerGame();
        $this->state = $game->newGameState();
        $this->showInstructions = false;
    }

    public function startHand()
    {
        if (!$this->state['gameStarted']) {
            $this->state = PokerEngine::applyMove($this->state, ['action' => 'start_hand']);
        }
    }

    public function newGame()
    {
        $this->resetGame();
    }

    public function makeMove(string $action, int $amount = 0)
    {
        if ($this->state['gameOver'] || $this->state['currentPlayer'] !== 0) {
            return;
        }

        $move = ['action' => $action];
        if ($amount > 0) {
            $move['amount'] = $amount;
        }

        $this->state = PokerEngine::applyMove($this->state, $move);

        // If it's still the player's turn, process AI moves
        if (!$this->state['gameOver'] && $this->state['currentPlayer'] !== 0) {
            $this->processAIMoves();
        }

        // Update best score if game is won
        if ($this->state['gameOver'] && $this->state['winner'] === 'player') {
            $this->updateBestScore();
        }
    }

    public function processAIMoves()
    {
        while (!$this->state['gameOver'] && $this->state['currentPlayer'] !== 0) {
            $aiMove = $this->getAIMove();
            $this->state = PokerEngine::applyMove($this->state, $aiMove);
            
            // Small delay for better UX
            usleep(500000); // 0.5 seconds
        }
    }

    public function getAIMove(): array
    {
        $currentPlayer = $this->state['players'][$this->state['currentPlayer']];
        
        // Simple AI logic
        $handStrength = $this->evaluateHandStrength($currentPlayer['holeCards'], $this->state['communityCards']);
        
        if ($handStrength < 0.3) {
            // Weak hand - fold or check
            if ($this->state['currentBet'] === $currentPlayer['currentBet']) {
                return ['action' => 'check'];
            } else {
                return ['action' => 'fold'];
            }
        } elseif ($handStrength < 0.6) {
            // Medium hand - call or check
            if ($this->state['currentBet'] === $currentPlayer['currentBet']) {
                return ['action' => 'check'];
            } else {
                return ['action' => 'call'];
            }
        } else {
            // Strong hand - bet or raise
            if ($this->state['currentBet'] === 0) {
                return ['action' => 'bet', 'amount' => 20];
            } else {
                return ['action' => 'raise', 'amount' => $this->state['currentBet'] * 2];
            }
        }
    }

    public function evaluateHandStrength(array $holeCards, array $communityCards): float
    {
        if (empty($communityCards)) {
            // Pre-flop evaluation
            return $this->evaluatePreFlopHand($holeCards);
        }

        // Post-flop evaluation
        $hand = PokerEngine::evaluateHand($holeCards, $communityCards);
        return $hand['value'] / 10.0; // Normalize to 0-1
    }

    public function evaluatePreFlopHand(array $holeCards): float
    {
        $card1 = $holeCards[0];
        $card2 = $holeCards[1];
        
        // Pair
        if ($card1['rank'] === $card2['rank']) {
            return 0.8;
        }
        
        // Suited
        if ($card1['suit'] === $card2['suit']) {
            return 0.6;
        }
        
        // High cards
        if (in_array($card1['rank'], ['A', 'K', 'Q', 'J']) || in_array($card2['rank'], ['A', 'K', 'Q', 'J'])) {
            return 0.5;
        }
        
        return 0.3;
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

    public function getHandRank(): ?array
    {
        if (empty($this->state['communityCards'])) {
            return null;
        }

        return PokerEngine::getHandRank($this->state);
    }

    public function getScore(): int
    {
        return PokerEngine::calculateScore($this->state);
    }

    public function getPotSize(): int
    {
        return PokerEngine::getPotSize($this->state);
    }

    public function getCurrentBet(): int
    {
        return PokerEngine::getCurrentBet($this->state);
    }

    public function isGameOver(): bool
    {
        return PokerEngine::isGameOver($this->state);
    }

    public function isGameComplete(): bool
    {
        return $this->isGameOver();
    }

    public function getGameState(): array
    {
        return PokerEngine::getGameState($this->state);
    }

    public function getPlayerHand(): array
    {
        return PokerEngine::getPlayerHand($this->state);
    }

    public function getCommunityCards(): array
    {
        return PokerEngine::getCommunityCards($this->state);
    }

    public function canBet(): bool
    {
        return PokerEngine::canBet($this->state);
    }

    public function canRaise(): bool
    {
        return PokerEngine::canRaise($this->state);
    }

    public function canCall(): bool
    {
        return PokerEngine::canCall($this->state);
    }

    public function canFold(): bool
    {
        return PokerEngine::canFold($this->state);
    }

    public function updateBestScore()
    {
        if ($this->state['gameOver'] && $this->state['winner'] === 'player' && auth()->check()) {
            $score = $this->getScore();
            $service = app(UserBestScoreService::class);
            $service->updateBestScore(auth()->id(), 'poker', $score);
        }
    }

    public function render()
    {
        return view('livewire.games.poker');
    }
}
