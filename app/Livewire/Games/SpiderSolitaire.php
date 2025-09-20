<?php

namespace App\Livewire\Games;

use App\Games\SpiderSolitaire\SpiderSolitaireGame;
use App\Games\SpiderSolitaire\SpiderSolitaireEngine;
use App\Services\UserBestScoreService;
use Livewire\Component;
use Livewire\Attributes\On;

class SpiderSolitaire extends Component
{
    public array $state = [];
    public bool $showInstructions = false;
    public bool $showHint = false;
    public array $selectedCards = [];
    public int $dragFromColumn = -1;
    public int $dragCardCount = 0;

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $game = new SpiderSolitaireGame();
        $this->state = $game->newGameState();
        $this->showInstructions = false;
        $this->showHint = false;
        $this->selectedCards = [];
        $this->dragFromColumn = -1;
        $this->dragCardCount = 0;
    }

    public function moveCards(int $fromColumn, int $toColumn, int $cardCount)
    {
        if ($this->state['gameOver']) {
            return;
        }

        $game = new SpiderSolitaireGame();
        $move = [
            'action' => 'move_cards',
            'fromColumn' => $fromColumn,
            'toColumn' => $toColumn,
            'cardCount' => $cardCount
        ];

        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
            
            // Update best score if game is won
            if ($this->state['gameWon'] && auth()->check()) {
                $this->updateBestScore();
            }
        }

        $this->clearSelection();
    }

    public function dealCards()
    {
        if ($this->state['gameOver']) {
            return;
        }

        $game = new SpiderSolitaireGame();
        $move = ['action' => 'deal_cards'];

        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
        }
    }

    public function undo()
    {
        $game = new SpiderSolitaireGame();
        $move = ['action' => 'undo'];

        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
        }

        $this->clearSelection();
    }

    public function newGame()
    {
        $this->resetGame();
    }

    public function getHint()
    {
        $this->showHint = true;
    }

    public function applyHint(array $hintMove)
    {
        if (isset($hintMove['move'])) {
            $move = $hintMove['move'];
            $this->moveCards($move['fromColumn'], $move['toColumn'], $move['cardCount']);
        }
        $this->showHint = false;
    }

    public function selectCard(int $column, int $cardIndex)
    {
        if ($this->state['gameOver']) {
            return;
        }

        $columnCards = $this->state['tableau'][$column];
        $card = $columnCards[$cardIndex];

        if (!$card['faceUp']) {
            return;
        }

        // Check if this card can be part of a movable sequence
        $movableSequences = SpiderSolitaireEngine::findMovableSequences($columnCards);
        
        foreach ($movableSequences as $sequence) {
            foreach ($sequence as $seqCardIndex => $seqCard) {
                if ($seqCardIndex + $cardIndex === count($columnCards) - count($sequence)) {
                    $this->selectedCards = [
                        'column' => $column,
                        'startIndex' => $cardIndex,
                        'count' => count($sequence),
                        'cards' => $sequence
                    ];
                    $this->dragFromColumn = $column;
                    $this->dragCardCount = count($sequence);
                    return;
                }
            }
        }
    }

    public function clearSelection()
    {
        $this->selectedCards = [];
        $this->dragFromColumn = -1;
        $this->dragCardCount = 0;
    }

    public function canPlaceOnColumn(int $column): bool
    {
        if (empty($this->selectedCards)) {
            return false;
        }

        $game = new SpiderSolitaireGame();
        return $game->validateMove($this->state, [
            'action' => 'move_cards',
            'fromColumn' => $this->selectedCards['column'],
            'toColumn' => $column,
            'cardCount' => $this->selectedCards['count']
        ]);
    }

    public function getCardImage(array $card): string
    {
        if (!$card['faceUp']) {
            return asset('images/Cards/cardBack_blue1.png');
        }
        
        $suit = ucfirst($card['suit']);
        $rank = $card['rank'];
        return asset("images/Cards/card{$suit}{$rank}.png");
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

    public function getSuitColor(string $suit): string
    {
        return match($suit) {
            'hearts', 'diamonds' => 'text-red-600',
            'clubs', 'spades' => 'text-gray-800',
            default => 'text-gray-600'
        };
    }

    public function getScore(): int
    {
        return SpiderSolitaireEngine::calculateScore($this->state);
    }

    public function getTableau(): array
    {
        return SpiderSolitaireEngine::getTableau($this->state);
    }

    public function getStock(): array
    {
        return SpiderSolitaireEngine::getStock($this->state);
    }

    public function getCompletedSuits(): array
    {
        return SpiderSolitaireEngine::getCompletedSuits($this->state);
    }

    public function getPossibleMoves(): array
    {
        return SpiderSolitaireEngine::getPossibleMoves($this->state);
    }

    public function getHintData(): array
    {
        return SpiderSolitaireEngine::getHint($this->state);
    }

    public function canDealCards(): bool
    {
        return SpiderSolitaireEngine::canDealCards($this->state);
    }

    public function canUndo(): bool
    {
        return SpiderSolitaireEngine::canUndo($this->state);
    }

    public function isGameOver(): bool
    {
        return SpiderSolitaireEngine::isGameOver($this->state);
    }

    public function isGameWon(): bool
    {
        return $this->state['gameWon'] ?? false;
    }

    public function getGameState(): array
    {
        return SpiderSolitaireEngine::getGameState($this->state);
    }

    public function updateBestScore()
    {
        if ($this->state['gameWon'] && auth()->check()) {
            $score = $this->getScore();
            $service = app(UserBestScoreService::class);
            $service->updateBestScore(auth()->id(), 'spider-solitaire', $score);
        }
    }

    public function render()
    {
        return view('livewire.games.spider-solitaire');
    }
}
