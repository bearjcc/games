<?php

namespace App\Livewire\Games;

use App\Games\Phase10\Phase10Game;
use App\Games\Phase10\Phase10Engine;
use App\Services\UserBestScoreService;
use Livewire\Component;
use Livewire\Attributes\On;

class Phase10 extends Component
{
    public array $state = [];
    public bool $showInstructions = false;
    public bool $showHint = false;
    public array $selectedCards = [];

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $game = new Phase10Game();
        $this->state = $game->newGameState();
        $this->showInstructions = false;
        $this->showHint = false;
        $this->selectedCards = [];
    }

    public function startGame()
    {
        if (!$this->state['gameStarted']) {
            $this->state = Phase10Engine::applyMove($this->state, ['action' => 'start_game']);
        }
    }

    public function newGame()
    {
        $this->resetGame();
    }

    public function drawFromDrawPile()
    {
        if ($this->state['gameOver']) {
            return;
        }

        $game = new Phase10Game();
        $move = ['action' => 'draw_from_draw'];

        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
        }

        $this->clearSelection();
    }

    public function drawFromDiscardPile()
    {
        if ($this->state['gameOver']) {
            return;
        }

        $game = new Phase10Game();
        $move = ['action' => 'draw_from_discard'];

        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
        }

        $this->clearSelection();
    }

    public function discardCard(int $cardIndex)
    {
        if ($this->state['gameOver']) {
            return;
        }

        $game = new Phase10Game();
        $move = [
            'action' => 'discard_card',
            'cardIndex' => $cardIndex
        ];

        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
        }

        $this->clearSelection();
    }

    public function playPhase()
    {
        if ($this->state['gameOver']) {
            return;
        }

        $game = new Phase10Game();
        $move = [
            'action' => 'play_phase',
            'cards' => $this->selectedCards
        ];

        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
        }

        $this->clearSelection();
    }

    public function goOut()
    {
        if ($this->state['gameOver']) {
            return;
        }

        $game = new Phase10Game();
        $move = ['action' => 'go_out'];

        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
            
            // Update best score if game is won
            if ($this->state['winner'] === 0 && auth()->check()) {
                $this->updateBestScore();
            }
        }

        $this->clearSelection();
    }

    public function toggleCardSelection(int $cardIndex)
    {
        if ($this->state['turnPhase'] !== 'playing') {
            return;
        }

        if (in_array($cardIndex, $this->selectedCards)) {
            $this->selectedCards = array_diff($this->selectedCards, [$cardIndex]);
        } else {
            $this->selectedCards[] = $cardIndex;
        }
    }

    public function clearSelection()
    {
        $this->selectedCards = [];
    }

    public function getHint()
    {
        $this->showHint = true;
    }

    public function applyHint(array $hintData)
    {
        if (isset($hintData['action'])) {
            switch ($hintData['action']) {
                case 'draw_from_draw':
                    $this->drawFromDrawPile();
                    break;
                case 'draw_from_discard':
                    $this->drawFromDiscardPile();
                    break;
                case 'play_phase':
                    $this->playPhase();
                    break;
                case 'go_out':
                    $this->goOut();
                    break;
            }
        }
        
        $this->showHint = false;
    }

    public function getCardImage(array $card): string
    {
        if ($card['type'] === 'wild') {
            return asset("images/Cards/wild_card.png");
        } elseif ($card['type'] === 'skip') {
            return asset("images/Cards/skip_card.png");
        } else {
            $color = $card['color'];
            $number = $card['number'];
            return asset("images/Cards/{$number}_of_{$color}.png");
        }
    }

    public function getCardClass(array $card): string
    {
        $classes = ['card'];
        
        if ($card['type'] === 'wild') {
            $classes[] = 'wild-card';
        } elseif ($card['type'] === 'skip') {
            $classes[] = 'skip-card';
        } else {
            $classes[] = 'number-card';
            $classes[] = "color-{$card['color']}";
        }
        
        return implode(' ', $classes);
    }

    public function getScore(): int
    {
        return Phase10Engine::calculateScore($this->state);
    }

    public function getCurrentPlayer(): int
    {
        return Phase10Engine::getCurrentPlayer($this->state);
    }

    public function getPlayerHands(): array
    {
        return Phase10Engine::getPlayerHands($this->state);
    }

    public function getDiscardPile(): array
    {
        return Phase10Engine::getDiscardPile($this->state);
    }

    public function getDrawPile(): array
    {
        return Phase10Engine::getDrawPile($this->state);
    }

    public function getCurrentPhase(): int
    {
        return Phase10Engine::getCurrentPhase($this->state);
    }

    public function getPhaseRequirements(): array
    {
        return Phase10Engine::getPhaseRequirements($this->state);
    }

    public function getPlayerScores(): array
    {
        return Phase10Engine::getPlayerScores($this->state);
    }

    public function getPlayerPhases(): array
    {
        return Phase10Engine::getPlayerPhases($this->state);
    }

    public function getHintData(): array
    {
        return Phase10Engine::getHint($this->state);
    }

    public function canDrawCard(): bool
    {
        return Phase10Engine::canDrawCard($this->state);
    }

    public function canDiscardCard(): bool
    {
        return Phase10Engine::canDiscardCard($this->state);
    }

    public function canPlayPhase(): bool
    {
        return Phase10Engine::canPlayPhase($this->state);
    }

    public function canGoOut(): bool
    {
        return Phase10Engine::canGoOut($this->state);
    }

    public function isGameOver(): bool
    {
        return Phase10Engine::isGameOver($this->state);
    }

    public function isGameWon(): bool
    {
        return $this->state['gameOver'] && $this->state['winner'] === 0;
    }

    public function getGameState(): array
    {
        return Phase10Engine::getGameState($this->state);
    }

    public function updateBestScore()
    {
        if ($this->state['gameOver'] && $this->state['winner'] === 0 && auth()->check()) {
            $score = $this->getScore();
            $service = app(UserBestScoreService::class);
            $service->updateBestScore(auth()->id(), 'phase10', $score);
        }
    }

    public function render()
    {
        return view('livewire.games.phase10');
    }
}
