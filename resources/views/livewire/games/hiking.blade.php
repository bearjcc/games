<?php

use Livewire\Volt\Component;
use App\Games\Hiking\HikingGame;
use App\Games\Hiking\HikingEngine;
use App\Services\UserBestScoreService;

new class extends Component
{
    public array $state;
    public bool $isPlaying = false;
    public bool $canPlay = true;
    public string $lastAction = '';
    public bool $showCards = false;
    public bool $animating = false;
    public bool $showRules = false;

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $game = new HikingGame();
        $this->state = $game->initialState();
        $this->isPlaying = false;
        $this->canPlay = true;
        $this->showCards = false;
        $this->animating = false;
        $this->lastAction = $this->state['lastAction'];
        $this->showRules = false;
    }

    public function playCard($cardIndex)
    {
        if (!$this->canPlay || $this->state['gameOver']) {
            return;
        }

        $this->animating = true;
        
        $game = new HikingGame();
        $move = ['action' => 'play_card', 'cardIndex' => $cardIndex];
        
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
                        'hiking',
                        $score
                    );
                }
            }
        }

        $this->animating = false;
    }

    public function drawCard()
    {
        if (!$this->canPlay || $this->state['gameOver']) {
            return;
        }

        $this->animating = true;
        
        $game = new HikingGame();
        $move = ['action' => 'draw_card'];
        
        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
            $this->lastAction = $this->state['lastAction'];
        }

        $this->animating = false;
    }

    public function discardCard($cardIndex)
    {
        if (!$this->canPlay || $this->state['gameOver']) {
            return;
        }

        $this->animating = true;
        
        $game = new HikingGame();
        $move = ['action' => 'discard_card', 'cardIndex' => $cardIndex];
        
        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
            $this->lastAction = $this->state['lastAction'];
        }

        $this->animating = false;
    }

    public function endTurn()
    {
        if (!$this->canPlay || $this->state['gameOver']) {
            return;
        }

        $this->animating = true;
        
        $game = new HikingGame();
        $move = ['action' => 'end_turn'];
        
        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
            $this->lastAction = $this->state['lastAction'];
            
            // Update best score if game is over and user is authenticated
            if ($this->state['gameOver'] && auth()->check()) {
                $score = $game->getScore($this->state);
                if ($score > 0) {
                    app(UserBestScoreService::class)->updateIfBetter(
                        auth()->user(),
                        'hiking',
                        $score
                    );
                }
            }
        }

        $this->animating = false;
    }

    public function toggleRules()
    {
        $this->showRules = !$this->showRules;
    }

    public function autoplay()
    {
        if ($this->state['gameOver']) {
            return;
        }

        $this->animating = true;
        
        $game = new HikingGame();
        $this->state = $game->autoplay($this->state);
        $this->isPlaying = true;
        $this->showCards = true;
        $this->lastAction = $this->state['lastAction'];
        
        // Update best score if game is over and user is authenticated
        if ($this->state['gameOver'] && auth()->check()) {
            $score = $game->getScore($this->state);
            if ($score > 0) {
                app(UserBestScoreService::class)->updateIfBetter(
                    auth()->user(),
                    'hiking',
                    $score
                );
            }
        }

        $this->animating = false;
    }

    public function getCardIcon($card)
    {
        $icons = [
            // Distance cards
            '25km' => '🥾',
            '50km' => '🥾🥾',
            '75km' => '🥾🥾🥾',
            '100km' => '🏔️',
            '200km' => '🏔️🏔️',
            
            // Hazard cards
            'Injury' => '🩹',
            'Dehydration' => '💧',
            'Blisters' => '🦶',
            'Trail Blockage' => '🪨',
            'Slow Pace' => '🐌',
            
            // Remedy cards
            'First Aid Kit' => '🏥',
            'Water Supply' => '💧',
            'Moleskin' => '🩹',
            'Alternate Route' => '🗺️',
            'Energy Boost' => '⚡',
            
            // Safety cards
            'Medical Training' => '👨‍⚕️',
            'Hydration Plan' => '📋',
            'Proper Footwear' => '👟',
            'Trail Map' => '🗺️',
            'Endurance Training' => '💪',
        ];

        return $icons[$card['name']] ?? '🃏';
    }

    public function getCardColor($card)
    {
        return match($card['type']) {
            'distance' => 'bg-green-100 border-green-500 text-green-800',
            'hazard' => 'bg-red-100 border-red-500 text-red-800',
            'remedy' => 'bg-blue-100 border-blue-500 text-blue-800',
            'safety' => 'bg-purple-100 border-purple-500 text-purple-800',
            default => 'bg-gray-100 border-gray-500 text-gray-800'
        };
    }

    public function canPlayCard($card)
    {
        $game = new HikingGame();
        
        // Find card index in hand
        $cardIndex = array_search($card, $this->state['hand']);
        if ($cardIndex === false) {
            return false;
        }

        $move = ['action' => 'play_card', 'cardIndex' => $cardIndex];
        return $game->validateMove($this->state, $move);
    }

    public function getStats()
    {
        return HikingEngine::getStats($this->state);
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-green-50 to-blue-50 dark:from-gray-900 dark:to-gray-800">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 dark:text-white mb-2">
                🏔️ Mountain Trail
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-300">
                Complete 1000km of hiking in 50 turns!
            </p>
        </div>

        <!-- Game Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-md">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $this->getStats()['distance'] }}km</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Distance</div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-md">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $this->getStats()['turn'] }}/{{ $this->getStats()['maxTurns'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Turns</div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-md">
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-600">{{ $this->getStats()['activeHazards'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Hazards</div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-md">
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $this->getStats()['activeSafety'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Safety</div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-md mb-6">
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                <span>Progress to 1000km</span>
                <span>{{ $this->getStats()['progress'] }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div class="bg-green-500 h-3 rounded-full transition-all duration-500" 
                     style="width: {{ $this->getStats()['progress'] }}%"></div>
            </div>
        </div>

        <!-- Game Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-md mb-6">
            <div class="text-center">
                <div class="text-lg font-semibold text-gray-800 dark:text-white mb-2">
                    {{ $state['message'] }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $state['lastAction'] }}
                </div>
            </div>
        </div>

        <!-- Current Hazard -->
        @if($state['currentHazard'])
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center">
                <span class="text-2xl mr-3">{{ $this->getCardIcon($state['currentHazard']) }}</span>
                <div class="text-center">
                    <div class="font-semibold text-red-800 dark:text-red-200">
                        Current Hazard: {{ $state['currentHazard']['name'] }}
                    </div>
                    <div class="text-sm text-red-600 dark:text-red-400">
                        Play a remedy card to overcome this hazard!
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Active Hazards -->
        @if(!empty($state['activeHazards']))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
            <div class="text-center">
                <div class="font-semibold text-red-800 dark:text-red-200 mb-2">Active Hazards</div>
                <div class="flex flex-wrap justify-center gap-2">
                    @foreach($state['activeHazards'] as $hazard)
                    <span class="px-3 py-1 bg-red-200 dark:bg-red-800 text-red-800 dark:text-red-200 rounded-full text-sm">
                        {{ ucfirst(str_replace('_', ' ', $hazard)) }}
                    </span>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Active Safety Cards -->
        @if(!empty($state['activeSafety']))
        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4 mb-6">
            <div class="text-center">
                <div class="font-semibold text-purple-800 dark:text-purple-200 mb-2">Active Safety Cards</div>
                <div class="flex flex-wrap justify-center gap-2">
                    @foreach($state['activeSafety'] as $safety)
                    <span class="px-3 py-1 bg-purple-200 dark:bg-purple-800 text-purple-800 dark:text-purple-200 rounded-full text-sm">
                        {{ ucfirst(str_replace('_', ' ', $safety)) }}
                    </span>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Player Hand -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md mb-6">
            <div class="text-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-white">Your Hand</h3>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ count($state['hand']) }} cards
                </div>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($state['hand'] as $index => $card)
                <div class="relative">
                    <div class="card {{ $this->getCardColor($card) }} rounded-lg p-3 border-2 cursor-pointer transition-all duration-200 hover:scale-105 {{ $this->canPlayCard($card) ? 'hover:shadow-lg' : 'opacity-60' }}"
                         wire:click="playCard({{ $index }})"
                         @if(!$this->canPlayCard($card)) title="Cannot play this card right now" @endif>
                        <div class="text-center">
                            <div class="text-2xl mb-2">{{ $this->getCardIcon($card) }}</div>
                            <div class="font-semibold text-sm">{{ $card['name'] }}</div>
                        </div>
                    </div>
                    
                    <!-- Discard button -->
                    <button class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 text-xs hover:bg-red-600 transition-colors"
                            wire:click.stop="discardCard({{ $index }})"
                            title="Discard card">
                        ×
                    </button>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Game Controls -->
        <div class="flex flex-wrap justify-center gap-4 mb-6">
            <button class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition-colors {{ $animating ? 'opacity-50 cursor-not-allowed' : '' }}"
                    wire:click="drawCard"
                    @if($animating || $state['gameOver'] || count($state['hand']) >= 6 || empty($state['drawPile'])) disabled @endif>
                Draw Card
            </button>
            
            <button class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition-colors {{ $animating ? 'opacity-50 cursor-not-allowed' : '' }}"
                    wire:click="endTurn"
                    @if($animating || $state['gameOver']) disabled @endif>
                End Turn
            </button>
            
            <button class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-2 rounded-lg transition-colors {{ $animating ? 'opacity-50 cursor-not-allowed' : '' }}"
                    wire:click="autoplay"
                    @if($animating || $state['gameOver']) disabled @endif>
                Autoplay
            </button>
            
            <button class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors"
                    wire:click="resetGame">
                New Game
            </button>
            
            <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-lg transition-colors"
                    wire:click="toggleRules">
                {{ $showRules ? 'Hide' : 'Show' }} Rules
            </button>
        </div>

        <!-- Rules Modal -->
        @if($showRules)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl max-h-96 overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-white">Game Rules</h3>
                    <button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                            wire:click="toggleRules">
                        ×
                    </button>
                </div>
                
                <div class="space-y-3 text-gray-700 dark:text-gray-300">
                    @foreach((new HikingGame())->rules() as $rule)
                    <div class="flex items-start">
                        <span class="text-green-500 mr-2">•</span>
                        <span>{{ $rule }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Game Over Modal -->
        @if($state['gameOver'])
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-8 text-center max-w-md">
                <div class="text-6xl mb-4">
                    @if($state['winner'] === 'player')
                        🏆
                    @elseif($state['winner'] === 'timeout')
                        ⏰
                    @else
                        😔
                    @endif
                </div>
                
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">
                    @if($state['winner'] === 'player')
                        Congratulations!
                    @elseif($state['winner'] === 'timeout')
                        Time's Up!
                    @else
                        Game Over
                    @endif
                </h3>
                
                <p class="text-lg text-gray-600 dark:text-gray-400 mb-6">
                    {{ $state['message'] }}
                </p>
                
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Final Score: {{ (new HikingGame())->getScore($state) }} points
                </div>
                
                <button class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition-colors"
                        wire:click="resetGame">
                    Play Again
                </button>
            </div>
        </div>
        @endif
    </div>
</div>

<style>
.card {
    min-height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card:hover {
    transform: translateY(-2px);
}
</style>
