<?php

use App\Games\Blackjack\BlackjackGame;
use App\Games\Blackjack\BlackjackEngine;
use App\Services\UserBestScoreService;
use Livewire\Volt\Component;

new class extends Component
{
    public array $state;
    public int $betAmount = 25;
    public int $insuranceAmount = 0;
    public bool $showInstructions = false;

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $game = new BlackjackGame();
        $this->state = $game->initialState();
    }

    public function placeBet()
    {
        $move = ['action' => 'place_bet', 'amount' => $this->betAmount];
        $this->applyMove($move);
    }

    public function dealCards()
    {
        $move = ['action' => 'deal_cards'];
        $this->applyMove($move);
    }

    public function hit()
    {
        $move = ['action' => 'hit'];
        $this->applyMove($move);
    }

    public function stand()
    {
        $move = ['action' => 'stand'];
        $this->applyMove($move);
    }

    public function doubleDown()
    {
        $move = ['action' => 'double_down'];
        $this->applyMove($move);
    }

    public function split()
    {
        $move = ['action' => 'split'];
        $this->applyMove($move);
    }

    public function takeInsurance()
    {
        $move = ['action' => 'insurance', 'amount' => $this->insuranceAmount];
        $this->applyMove($move);
    }

    public function newGame()
    {
        $move = ['action' => 'new_game'];
        $this->applyMove($move);
    }

    public function applyMove($move)
    {
        $game = new BlackjackGame();
        
        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
            
            // Update best score if game is complete and user is authenticated
            if ($this->state['gamePhase'] === 'finished' && auth()->check()) {
                $score = $game->getScore($this->state);
                app(UserBestScoreService::class)->updateIfBetter(
                    auth()->user(),
                    'blackjack',
                    $score
                );
            }
        }
    }

    public function getCardImage($card)
    {
        return BlackjackEngine::getCardImagePath($card['image']);
    }

    public function getCardBackImage()
    {
        return BlackjackEngine::getCardBackImage();
    }

    public function getHandValue($cards)
    {
        return BlackjackEngine::calculateHandValue($cards);
    }

    public function isBlackjack($cards)
    {
        return BlackjackEngine::isBlackjack($cards);
    }

    public function isBust($cards)
    {
        return BlackjackEngine::isBust($cards);
    }

    public function canPlaceBet()
    {
        return $this->state['gamePhase'] === 'betting' && 
               $this->betAmount >= BlackjackEngine::MIN_BET && 
               $this->betAmount <= BlackjackEngine::MAX_BET &&
               $this->betAmount <= $this->state['chips'];
    }

    public function canDealCards()
    {
        return $this->state['gamePhase'] === 'betting' && $this->state['bet'] > 0;
    }

    public function canHit()
    {
        return $this->state['gamePhase'] === 'playing' && 
               !$this->isBust($this->state['playerHand']);
    }

    public function canStand()
    {
        return $this->state['gamePhase'] === 'playing';
    }

    public function canDoubleDown()
    {
        $game = new BlackjackGame();
        return $game->canDoubleDown($this->state);
    }

    public function canSplit()
    {
        $game = new BlackjackGame();
        return $game->canSplit($this->state);
    }

    public function canTakeInsurance()
    {
        $game = new BlackjackGame();
        return $game->canTakeInsurance($this->state);
    }

    public function getGameStatusMessage()
    {
        switch ($this->state['gameResult']) {
            case 'win':
                return 'You Win!';
            case 'lose':
                return 'You Lose!';
            case 'push':
                return 'Push!';
            case 'blackjack':
                return 'Blackjack! You Win!';
            default:
                return '';
        }
    }

    public function getGameStatusColor()
    {
        switch ($this->state['gameResult']) {
            case 'win':
            case 'blackjack':
                return 'text-green-600';
            case 'lose':
                return 'text-red-600';
            case 'push':
                return 'text-yellow-600';
            default:
                return 'text-gray-600';
        }
    }

    public function getDealerCardDisplay()
    {
        if ($this->state['gamePhase'] === 'playing' || $this->state['gamePhase'] === 'betting') {
            // Show only first card, hide second
            return [$this->state['dealerHand'][0] ?? null];
        }
        
        // Show all cards when game is finished
        return $this->state['dealerHand'];
    }

    public function getPlayerHandDisplay()
    {
        return $this->state['playerHand'];
    }
}; ?>

<div>
    <x-game.styles />
    <x-game.animations />
    
    <x-game.layout title="Blackjack">
        <!-- Game Header -->
        <div class="game-header">
            <div class="game-status">
                @if($state['gamePhase'] === 'finished')
                    <div class="winner-indicator {{ $this->getGameStatusColor() }}">
                        {{ $this->getGameStatusMessage() }}
                        <div class="text-sm">
                            Round {{ $state['roundNumber'] }} | 
                            Wins: {{ $state['totalWins'] }} | 
                            Losses: {{ $state['totalLosses'] }} | 
                            Pushes: {{ $state['totalPushes'] }}
                        </div>
                    </div>
                @else
                    <div class="player-indicator">
                        Chips: ${{ number_format($state['chips']) }}
                        <div class="text-sm">
                            Bet: ${{ number_format($state['bet']) }} | 
                            Round {{ $state['roundNumber'] }} | 
                            {{ ucfirst(str_replace('_', ' ', $state['gamePhase'])) }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Game Board Container -->
        <div class="game-board-container">
            <div class="blackjack-game-board">
                <!-- Dealer Area -->
                <div class="dealer-area">
                    <h3 class="area-title">Dealer</h3>
                    <div class="hand-container">
                        @foreach($this->getDealerCardDisplay() as $card)
                            @if($card)
                                <div class="card">
                                    <img src="{{ $this->getCardImage($card) }}" 
                                         alt="{{ $card['rank'] }} of {{ $card['suit'] }}" 
                                         class="card-image">
                                </div>
                            @endif
                        @endforeach
                        
                        @if(($state['gamePhase'] === 'playing' || $state['gamePhase'] === 'betting') && count($state['dealerHand']) > 1)
                            <div class="card">
                                <img src="{{ $this->getCardBackImage() }}" 
                                     alt="Card Back" 
                                     class="card-image">
                            </div>
                        @endif
                    </div>
                    
                    @if($state['gamePhase'] === 'finished')
                        <div class="hand-value">
                            Value: {{ $this->getHandValue($state['dealerHand']) }}
                            @if($this->isBlackjack($state['dealerHand']))
                                <span class="blackjack-indicator">BLACKJACK!</span>
                            @elseif($this->isBust($state['dealerHand']))
                                <span class="bust-indicator">BUST!</span>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Player Area -->
                <div class="player-area">
                    <h3 class="area-title">Your Hand</h3>
                    <div class="hand-container">
                        @foreach($this->getPlayerHandDisplay() as $card)
                            <div class="card">
                                <img src="{{ $this->getCardImage($card) }}" 
                                     alt="{{ $card['rank'] }} of {{ $card['suit'] }}" 
                                     class="card-image">
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="hand-value">
                        Value: {{ $this->getHandValue($state['playerHand']) }}
                        @if($this->isBlackjack($state['playerHand']))
                            <span class="blackjack-indicator">BLACKJACK!</span>
                        @elseif($this->isBust($state['playerHand']))
                            <span class="bust-indicator">BUST!</span>
                        @endif
                    </div>
                </div>

                <!-- Game Controls -->
                <div class="controls-panel">
                    @if($state['gamePhase'] === 'betting')
                        <!-- Betting Phase -->
                        <div class="betting-controls">
                            <h4>Place Your Bet</h4>
                            <div class="bet-input">
                                <label for="betAmount">Amount: $</label>
                                <input type="number" 
                                       id="betAmount"
                                       wire:model="betAmount" 
                                       min="{{ BlackjackEngine::MIN_BET }}" 
                                       max="{{ min(BlackjackEngine::MAX_BET, $state['chips']) }}"
                                       class="bet-input-field">
                            </div>
                            <div class="quick-bets">
                                <button class="quick-bet-btn" wire:click="$set('betAmount', 10)">$10</button>
                                <button class="quick-bet-btn" wire:click="$set('betAmount', 25)">$25</button>
                                <button class="quick-bet-btn" wire:click="$set('betAmount', 50)">$50</button>
                                <button class="quick-bet-btn" wire:click="$set('betAmount', 100)">$100</button>
                            </div>
                            <button class="action-button primary" 
                                    wire:click="placeBet"
                                    {{ !$this->canPlaceBet() ? 'disabled' : '' }}>
                                Place Bet
                            </button>
                        </div>
                    @elseif($state['gamePhase'] === 'playing')
                        <!-- Playing Phase -->
                        <div class="playing-controls">
                            <h4>Your Turn</h4>
                            <div class="action-buttons">
                                <button class="action-button hit" 
                                        wire:click="hit"
                                        {{ !$this->canHit() ? 'disabled' : '' }}>
                                    Hit
                                </button>
                                <button class="action-button stand" 
                                        wire:click="stand"
                                        {{ !$this->canStand() ? 'disabled' : '' }}>
                                    Stand
                                </button>
                            </div>
                            
                            @if($this->canDoubleDown())
                                <button class="action-button double-down" 
                                        wire:click="doubleDown">
                                    Double Down
                                </button>
                            @endif
                            
                            @if($this->canSplit())
                                <button class="action-button split" 
                                        wire:click="split">
                                    Split
                                </button>
                            @endif
                            
                            @if($this->canTakeInsurance())
                                <div class="insurance-controls">
                                    <h5>Insurance?</h5>
                                    <div class="insurance-input">
                                        <label>Amount: $</label>
                                        <input type="number" 
                                               wire:model="insuranceAmount" 
                                               min="0" 
                                               max="{{ $state['bet'] / 2 }}"
                                               class="insurance-input-field">
                                    </div>
                                    <button class="action-button insurance" 
                                            wire:click="takeInsurance">
                                        Take Insurance
                                    </button>
                                </div>
                            @endif
                        </div>
                    @elseif($state['gamePhase'] === 'finished')
                        <!-- Game Over Phase -->
                        <div class="game-over-controls">
                            <h4>Game Over</h4>
                            <div class="game-result">
                                <p class="result-message {{ $this->getGameStatusColor() }}">
                                    {{ $this->getGameStatusMessage() }}
                                </p>
                                @if($state['gameResult'] === 'win' || $state['gameResult'] === 'blackjack')
                                    <p class="winnings">
                                        You won ${{ number_format($state['bet'] * 2) }}!
                                    </p>
                                @elseif($state['gameResult'] === 'push')
                                    <p class="push-message">
                                        Your bet has been returned.
                                    </p>
                                @endif
                            </div>
                            <button class="action-button primary" wire:click="newGame">
                                New Game
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="instructions">
            <button class="instruction-toggle" @click="$wire.showInstructions = !$wire.showInstructions">
                {{ $showInstructions ? 'Hide' : 'Show' }} Instructions
            </button>
            
            @if($showInstructions)
                <div class="instruction-content">
                    <div class="instruction-section">
                        <h4>How to Play</h4>
                        <ul>
                            <li>Place your bet and click "Place Bet"</li>
                            <li>Click "Deal Cards" to start the hand</li>
                            <li>Hit to get another card, Stand to keep your hand</li>
                            <li>Get as close to 21 as possible without going over</li>
                            <li>Beat the dealer's hand to win</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Special Actions</h4>
                        <ul>
                            <li><strong>Double Down:</strong> Double your bet and take exactly one more card</li>
                            <li><strong>Split:</strong> Split pairs into two separate hands</li>
                            <li><strong>Insurance:</strong> Bet half your wager when dealer shows Ace</li>
                            <li><strong>Blackjack:</strong> 21 with 2 cards pays 3:2</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </x-game.layout>

    <style>
        /* Blackjack Game Styles */
        .blackjack-game-board {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 2rem;
            max-width: 80rem;
            margin: 0 auto;
            padding: 1rem;
        }

        @media (max-width: 1024px) {
            .blackjack-game-board {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        .dealer-area, .player-area {
            background: rgb(34 197 94);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .dark .dealer-area, .dark .player-area {
            background: rgb(21 128 61);
        }

        .area-title {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 1rem;
            text-align: center;
            color: white;
        }

        .hand-container {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .card {
            width: 4rem;
            height: 5.5rem;
            border-radius: 0.375rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .card-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hand-value {
            text-align: center;
            font-weight: bold;
            font-size: 1.125rem;
            color: white;
        }

        .blackjack-indicator {
            color: rgb(251 191 36);
            font-weight: bold;
            margin-left: 0.5rem;
        }

        .bust-indicator {
            color: rgb(239 68 68);
            font-weight: bold;
            margin-left: 0.5rem;
        }

        /* Controls Panel */
        .controls-panel {
            min-width: 20rem;
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .dark .controls-panel {
            background: rgb(51 65 85);
        }

        .controls-panel h4 {
            font-weight: bold;
            margin-bottom: 1rem;
            color: rgb(71 85 105);
        }

        .dark .controls-panel h4 {
            color: rgb(203 213 225);
        }

        .betting-controls, .playing-controls, .game-over-controls {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .bet-input {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .bet-input-field, .insurance-input-field {
            padding: 0.5rem;
            border: 2px solid rgb(203 213 225);
            border-radius: 0.375rem;
            width: 6rem;
        }

        .dark .bet-input-field, .dark .insurance-input-field {
            background: rgb(55 65 81);
            border-color: rgb(71 85 105);
            color: rgb(243 244 246);
        }

        .quick-bets {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }

        .quick-bet-btn {
            padding: 0.5rem;
            background: rgb(243 244 246);
            border: 2px solid rgb(203 213 225);
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .dark .quick-bet-btn {
            background: rgb(55 65 81);
            border-color: rgb(71 85 105);
            color: rgb(243 244 246);
        }

        .quick-bet-btn:hover {
            background: rgb(229 231 235);
        }

        .dark .quick-bet-btn:hover {
            background: rgb(71 85 105);
        }

        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .action-button {
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
        }

        .action-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .action-button.primary {
            background: rgb(59 130 246);
            color: white;
        }

        .action-button.primary:hover:not(:disabled) {
            background: rgb(37 99 235);
        }

        .action-button.hit {
            background: rgb(34 197 94);
            color: white;
        }

        .action-button.hit:hover:not(:disabled) {
            background: rgb(21 128 61);
        }

        .action-button.stand {
            background: rgb(239 68 68);
            color: white;
        }

        .action-button.stand:hover:not(:disabled) {
            background: rgb(185 28 28);
        }

        .action-button.double-down {
            background: rgb(168 85 247);
            color: white;
            width: 100%;
            margin-top: 0.5rem;
        }

        .action-button.double-down:hover:not(:disabled) {
            background: rgb(147 51 234);
        }

        .action-button.split {
            background: rgb(245 158 11);
            color: white;
            width: 100%;
            margin-top: 0.5rem;
        }

        .action-button.split:hover:not(:disabled) {
            background: rgb(217 119 6);
        }

        .action-button.insurance {
            background: rgb(107 114 128);
            color: white;
            width: 100%;
            margin-top: 0.5rem;
        }

        .action-button.insurance:hover:not(:disabled) {
            background: rgb(75 85 99);
        }

        .insurance-controls {
            border-top: 2px solid rgb(203 213 225);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .dark .insurance-controls {
            border-color: rgb(71 85 105);
        }

        .insurance-controls h5 {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: rgb(71 85 105);
        }

        .dark .insurance-controls h5 {
            color: rgb(203 213 225);
        }

        .insurance-input {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .game-result {
            text-align: center;
            margin-bottom: 1rem;
        }

        .result-message {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .winnings {
            color: rgb(34 197 94);
            font-weight: bold;
        }

        .push-message {
            color: rgb(245 158 11);
            font-weight: bold;
        }

        /* Instructions */
        .instructions {
            margin-top: 1rem;
            text-align: center;
        }

        .instruction-toggle {
            background: rgb(107 114 128);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
        }

        .instruction-toggle:hover {
            background: rgb(75 85 99);
        }

        .instruction-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 1rem;
            text-align: left;
        }

        @media (max-width: 768px) {
            .instruction-content {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        .instruction-section h4 {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .instruction-section ul {
            list-style: disc;
            margin-left: 1.5rem;
        }

        .instruction-section li {
            margin-bottom: 0.25rem;
        }
    </style>
</div>
