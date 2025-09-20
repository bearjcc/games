<?php

use Livewire\Volt\Component;
use App\Games\War\WarGame;
use App\Games\War\WarEngine;
use App\Services\UserBestScoreService;

new class extends Component
{
    public array $state;
    public bool $isPlaying = false;
    public bool $canPlay = true;
    public string $lastAction = '';
    public bool $showCards = false;
    public bool $animating = false;

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $game = new WarGame();
        $this->state = $game->initialState();
        $this->isPlaying = false;
        $this->canPlay = true;
        $this->showCards = false;
        $this->animating = false;
        $this->lastAction = $this->state['lastAction'];
    }

    public function playCard()
    {
        if (!$this->canPlay || $this->state['gameOver']) {
            return;
        }

        $this->animating = true;
        
        $game = new WarGame();
        $move = ['action' => 'play_card'];
        
        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
            $this->isPlaying = true;
            $this->showCards = true;
            $this->lastAction = $this->state['lastAction'];
            
            // Update best score if game is over and user is authenticated
            if ($this->state['gameOver'] && auth()->check()) {
                $score = $game->getScore($this->state);
                if ($score > 0) {
                    app(UserBestScoreService::class)->updateIfBetter(
                        auth()->user(),
                        'war',
                        $score
                    );
                }
            }
        }

        // Auto-continue if in war state
        if ($this->state['isWar'] && WarEngine::canContinue($this->state)) {
            $this->canPlay = true;
        }

        $this->animating = false;
    }

    public function getCardSpriteStyle($card)
    {
        if (!$card) return '';
        
        $sprite = WarEngine::getCardSprite($card);
        return "background-position: {$sprite['x']}px {$sprite['y']}px;";
    }

    public function getStats()
    {
        return WarEngine::getStats($this->state);
    }
}; ?>

<div>
<x-game.layout title="War" description="Classic high-card wins battle. Prepare for war when cards tie!">
    <x-game.accessibility>
        @if($state['gameOver'])
            {{ $state['winner'] === 'player' ? 'Victory! You won the war!' : 'Defeat! AI conquered all cards.' }}
        @elseif($state['isWar'])
            War declared! Both players add 3 cards to the pot.
        @else
            Round {{ $state['round'] }}. Click play card to reveal next cards.
        @endif
    </x-game.accessibility>

    <div class="max-w-4xl mx-auto">
        <!-- Game Stats Header -->
        <div class="mb-8">
            <x-game.score-display class="mb-4">
                <div class="flex items-center gap-6 text-lg">
                    <div class="text-blue-600 dark:text-blue-400">
                        <span class="font-bold">{{ $this->getStats()['playerCards'] }}</span> cards
                    </div>
                    <div class="text-gray-500">Round {{ $state['round'] }}</div>
                    <div class="text-red-600 dark:text-red-400">
                        <span class="font-bold">{{ $this->getStats()['aiCards'] }}</span> cards
                    </div>
                </div>
            </x-game.score-display>

            <!-- Progress Bar -->
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-4">
                <div class="bg-gradient-to-r from-blue-500 to-red-500 h-3 rounded-full transition-all duration-1000"
                     style="width: {{ $this->getStats()['playerPercentage'] }}%"></div>
            </div>
        </div>

        <!-- Game Board -->
        <x-game.board size="600" bg-color="#1a472a" border-radius="16px" padding="40px" class="mb-8">
            <div class="relative w-full h-full flex flex-col justify-between">
                
                <!-- AI Section (Top) -->
                <div class="text-center">
                    <div class="text-white text-lg mb-4 font-semibold">AI Opponent</div>
                    <div class="flex justify-center items-center space-x-4">
                        <!-- AI Deck -->
                        <div class="relative">
                            <div class="playing-card card-back {{ empty($state['aiDeck']) ? 'opacity-50' : '' }}"
                                 title="AI has {{ count($state['aiDeck']) }} cards">
                                <div class="absolute top-2 left-2 text-xs text-white bg-black bg-opacity-50 px-1 rounded">
                                    {{ count($state['aiDeck']) }}
                                </div>
                            </div>
                        </div>

                        <!-- AI Card Slot -->
                        <div class="card-slot">
                            @if($showCards && $state['aiCard'])
                                <div class="playing-card animate-flip-in {{ $state['isWar'] ? 'war-glow' : '' }}"
                                     style="{{ $this->getCardSpriteStyle($state['aiCard']) }}"
                                     title="AI played {{ $state['aiCard']['rank'] }} of {{ $state['aiCard']['suit'] }}">
                                </div>
                            @else
                                <div class="card-placeholder">AI</div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- War Zone (Middle) -->
                @if($state['isWar'] || !empty($state['warCards']))
                <div class="text-center my-6">
                    <div class="text-red-500 text-2xl font-bold mb-2 animate-pulse">
                        ⚔️ WAR! ⚔️
                    </div>
                    <div class="text-white text-sm">
                        {{ count($state['warCards']) }} cards in the pot
                    </div>
                    <div class="flex justify-center space-x-1 mt-2">
                        @for($i = 0; $i < min(6, count($state['warCards'])); $i++)
                            <div class="w-4 h-6 bg-red-600 rounded-sm opacity-80"></div>
                        @endfor
                        @if(count($state['warCards']) > 6)
                            <div class="text-white text-xs">+{{ count($state['warCards']) - 6 }}</div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Player Section (Bottom) -->
                <div class="text-center">
                    <div class="flex justify-center items-center space-x-4 mb-4">
                        <!-- Player Card Slot -->
                        <div class="card-slot">
                            @if($showCards && $state['playerCard'])
                                <div class="playing-card animate-flip-in {{ $state['isWar'] ? 'war-glow' : '' }}"
                                     style="{{ $this->getCardSpriteStyle($state['playerCard']) }}"
                                     title="You played {{ $state['playerCard']['rank'] }} of {{ $state['playerCard']['suit'] }}">
                                </div>
                            @else
                                <div class="card-placeholder">YOU</div>
                            @endif
                        </div>

                        <!-- Player Deck -->
                        <div class="relative">
                            <div class="playing-card card-back {{ empty($state['playerDeck']) ? 'opacity-50' : '' }}"
                                 title="You have {{ count($state['playerDeck']) }} cards">
                                <div class="absolute top-2 left-2 text-xs text-white bg-black bg-opacity-50 px-1 rounded">
                                    {{ count($state['playerDeck']) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-white text-lg font-semibold">Your Cards</div>
                </div>
            </div>
        </x-game.board>

        <!-- Game Status & Controls -->
        <div class="text-center mb-8">
            <!-- Status Message -->
            <div class="mb-4">
                <div class="text-xl font-semibold mb-2
                    {{ $state['gameOver'] ? ($state['winner'] === 'player' ? 'text-green-600' : 'text-red-600') : 'text-gray-800 dark:text-gray-200' }}">
                    {{ $state['message'] }}
                </div>
                @if($lastAction && !$state['gameOver'])
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $lastAction }}
                    </div>
                @endif
            </div>

            <!-- Game Controls -->
            <x-game.controls>
                @if(!$state['gameOver'])
                    <button wire:click="playCard" 
                            wire:loading.attr="disabled"
                            @disabled(!$canPlay || $animating)
                            class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-bold py-3 px-8 rounded-lg transition-colors text-lg">
                        <span wire:loading.remove wire:target="playCard">
                            {{ $state['isWar'] ? 'Continue War!' : 'Play Card' }}
                        </span>
                        <span wire:loading wire:target="playCard">Playing...</span>
                    </button>
                @endif

                <button wire:click="resetGame" 
                        class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-8 rounded-lg transition-colors ml-4">
                    New Game
                </button>
            </x-game.controls>
        </div>

        <!-- Detailed Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center text-sm">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <div class="text-2xl font-bold text-blue-600">{{ $this->getStats()['playerWins'] }}</div>
                <div class="text-gray-600 dark:text-gray-400">Rounds Won</div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <div class="text-2xl font-bold text-red-600">{{ $this->getStats()['aiWins'] }}</div>
                <div class="text-gray-600 dark:text-gray-400">Rounds Lost</div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <div class="text-2xl font-bold text-yellow-600">{{ $this->getStats()['wars'] }}</div>
                <div class="text-gray-600 dark:text-gray-400">Wars Fought</div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <div class="text-2xl font-bold text-purple-600">{{ $this->getStats()['totalRounds'] }}</div>
                <div class="text-gray-600 dark:text-gray-400">Total Rounds</div>
            </div>
        </div>

        <!-- Rules & Tips -->
        <div class="mt-8 text-center">
            <details class="inline-block text-left">
                <summary class="cursor-pointer text-blue-600 hover:text-blue-800 font-medium">
                    📖 How to Play War
                </summary>
                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm max-w-2xl">
                    <ul class="space-y-2">
                        <li>• Each player gets 26 cards from a shuffled deck</li>
                        <li>• Click "Play Card" to reveal both players' top cards</li>
                        <li>• Higher card wins both cards (Ace = 14, King = 13, etc.)</li>
                        <li>• When cards tie: <strong>WAR!</strong> Each player adds 3 cards to the pot</li>
                        <li>• Winner of the war takes all cards in the pot</li>
                        <li>• Game ends when one player has all 52 cards</li>
                    </ul>
                </div>
            </details>
        </div>
    </div>

    <style>
    .playing-card {
        width: 72px;
        height: 96px;
        background-image: url('{{ asset('resources/img/playingCards.svg') }}');
        background-size: 936px 384px; /* 13 cards × 72px, 4 suits × 96px */
        border-radius: 8px;
        border: 2px solid #fff;
        box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        transition: all 0.3s ease;
    }

    .card-back {
        background-image: url('{{ asset('resources/img/playingCards_back.svg') }}');
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
    }

    .card-slot {
        width: 72px;
        height: 96px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px dashed rgba(255,255,255,0.3);
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .card-placeholder {
        color: rgba(255,255,255,0.7);
        font-size: 12px;
        font-weight: bold;
        text-align: center;
    }

    .war-glow {
        box-shadow: 0 0 20px #ff4444, 0 4px 8px rgba(0,0,0,0.3);
        animation: glow-pulse 1s ease-in-out infinite alternate;
    }

    @keyframes glow-pulse {
        from { box-shadow: 0 0 20px #ff4444, 0 4px 8px rgba(0,0,0,0.3); }
        to { box-shadow: 0 0 30px #ff6666, 0 4px 8px rgba(0,0,0,0.3); }
    }

    @keyframes flip-in {
        from {
            transform: rotateY(-180deg);
            opacity: 0;
        }
        to {
            transform: rotateY(0deg);
            opacity: 1;
        }
    }

    .animate-flip-in {
        animation: flip-in 0.6s ease-out;
    }

    /* Mobile responsiveness */
    @media (max-width: 640px) {
        .playing-card {
            width: 60px;
            height: 80px;
        }
        
        .card-slot {
            width: 60px;
            height: 80px;
        }
    }
    </style>
</x-game.layout>
</div>
