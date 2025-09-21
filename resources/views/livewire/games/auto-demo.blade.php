<?php

use App\Games\Connect4\Connect4Engine;
use App\Games\TicTacToe\TicTacToeEngine;
use App\Games\TwentyFortyEight\TwentyFortyEightEngine;
use Livewire\Volt\Component;

new class extends Component {
    public $game;
    public $state;
    public $isPlaying = false;
    public $speed = 1000; // milliseconds between moves
    public $gameOver = false;
    public $winner = null;
    
    public function mount($game = 'connect4')
    {
        $this->game = $game;
        $this->resetDemo();
    }
    
    public function resetDemo()
    {
        $this->state = $this->getInitialState();
        $this->isPlaying = false;
        $this->gameOver = false;
        $this->winner = null;
    }
    
    public function startDemo()
    {
        if (!$this->isPlaying && !$this->gameOver) {
            $this->isPlaying = true;
            $this->dispatch('start-auto-play');
        }
    }
    
    public function stopDemo()
    {
        $this->isPlaying = false;
        $this->dispatch('stop-auto-play');
    }
    
    public function makeAutoMove()
    {
        if (!$this->isPlaying || $this->gameOver) {
            return;
        }
        
        $this->state = $this->calculateNextMove();
        
        if ($this->isGameComplete()) {
            $this->isPlaying = false;
            $this->gameOver = true;
            $this->winner = $this->getWinner();
        }
    }
    
    private function getInitialState(): array
    {
        return match($this->game) {
            'connect4' => Connect4Engine::initialState(),
            'tic-tac-toe' => TicTacToeEngine::initialState(),
            '2048' => TwentyFortyEightEngine::initialState(),
            default => []
        };
    }
    
    private function calculateNextMove(): array
    {
        return match($this->game) {
            'connect4' => $this->makeConnect4Move(),
            'tic-tac-toe' => $this->makeTicTacToeMove(),
            '2048' => $this->make2048Move(),
            default => $this->state
        };
    }
    
    private function makeConnect4Move(): array
    {
        $aiMove = Connect4Engine::calculateAIMove($this->state, 'medium');
        if ($aiMove !== null) {
            return Connect4Engine::dropPiece($this->state, $aiMove);
        }
        return $this->state;
    }
    
    private function makeTicTacToeMove(): array
    {
        $validMoves = TicTacToeEngine::getValidMoves($this->state);
        if (!empty($validMoves)) {
            $randomMove = $validMoves[array_rand($validMoves)];
            return TicTacToeEngine::makeMove($this->state, $randomMove['row'], $randomMove['col']);
        }
        return $this->state;
    }
    
    private function make2048Move(): array
    {
        $directions = ['up', 'down', 'left', 'right'];
        $direction = $directions[array_rand($directions)];
        return TwentyFortyEightEngine::move($this->state, $direction);
    }
    
    private function isGameComplete(): bool
    {
        return match($this->game) {
            'connect4' => $this->state['gameOver'] ?? false,
            'tic-tac-toe' => $this->state['gameOver'] ?? false,
            '2048' => $this->state['gameOver'] ?? false,
            default => false
        };
    }
    
    private function getWinner(): ?string
    {
        return match($this->game) {
            'connect4' => $this->state['winner'] ?? null,
            'tic-tac-toe' => $this->state['winner'] ?? null,
            '2048' => $this->state['score'] > 0 ? 'Player' : null,
            default => null
        };
    }
    
    public function setSpeed($speed)
    {
        $this->speed = max(100, min(5000, $speed)); // Clamp between 100ms and 5s
    }
}; ?>

<div class="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-lg rounded-2xl p-6 border border-white/10">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold text-white">{{ ucfirst(str_replace('-', ' ', $game)) }} Auto-Demo</h3>
        <div class="flex gap-2">
            @if(!$isPlaying && !$gameOver)
                <button wire:click="startDemo" 
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                    ▶ Play
                </button>
            @elseif($isPlaying)
                <button wire:click="stopDemo" 
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                    ⏸ Pause
                </button>
            @endif
            
            <button wire:click="resetDemo" 
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                🔄 Reset
            </button>
        </div>
    </div>
    
    <!-- Speed Control -->
    <div class="mb-4">
        <label class="block text-sm text-gray-300 mb-2">Demo Speed</label>
        <input type="range" 
               min="100" 
               max="3000" 
               step="100"
               wire:model.live="speed"
               class="w-full h-2 bg-gray-600 rounded-lg appearance-none cursor-pointer">
        <div class="flex justify-between text-xs text-gray-400 mt-1">
            <span>Fast</span>
            <span>{{ $speed }}ms</span>
            <span>Slow</span>
        </div>
    </div>
    
    <!-- Game Display -->
    <div class="bg-slate-900/50 rounded-xl p-4">
        @if($game === 'connect4')
            @include('components.auto-demo.connect4')
        @elseif($game === 'tic-tac-toe')
            @include('components.auto-demo.tic-tac-toe')
        @elseif($game === '2048')
            @include('components.auto-demo.2048')
        @else
            <div class="text-center text-gray-400 py-8">
                <div class="text-4xl mb-2"><i class="fas fa-gamepad text-blue-500"></i></div>
                <p>Demo not available for this game</p>
            </div>
        @endif
    </div>
    
    <!-- Game Status -->
    <div class="mt-4 text-center">
        @if($gameOver)
            <div class="text-lg font-semibold text-green-400">
                🏆 Game Complete! 
                @if($winner)
                    Winner: {{ $winner }}
                @endif
            </div>
        @elseif($isPlaying)
            <div class="text-lg font-semibold text-blue-400">
                <i class="fas fa-gamepad mr-2"></i>Auto-playing... {{ ucfirst($state['currentPlayer'] ?? '') }}'s turn
            </div>
        @else
            <div class="text-lg font-semibold text-gray-400">
                ⏸ Demo paused - Click play to watch AI vs AI
            </div>
        @endif
    </div>
    
    <!-- Statistics -->
    @if(isset($state['moves']) || isset($state['score']))
        <div class="mt-4 grid grid-cols-2 gap-4 text-center">
            @if(isset($state['moves']))
                <div class="bg-white/10 rounded-lg p-3">
                    <div class="text-lg font-bold text-white">{{ $state['moves'] }}</div>
                    <div class="text-sm text-gray-300">Moves</div>
                </div>
            @endif
            @if(isset($state['score']))
                <div class="bg-white/10 rounded-lg p-3">
                    <div class="text-lg font-bold text-white">{{ $state['score'] }}</div>
                    <div class="text-sm text-gray-300">Score</div>
                </div>
            @endif
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', () => {
        let autoPlayInterval = null;
        
        Livewire.on('start-auto-play', () => {
            if (autoPlayInterval) {
                clearInterval(autoPlayInterval);
            }
            
            autoPlayInterval = setInterval(() => {
                @this.makeAutoMove();
            }, @this.speed);
        });
        
        Livewire.on('stop-auto-play', () => {
            if (autoPlayInterval) {
                clearInterval(autoPlayInterval);
                autoPlayInterval = null;
            }
        });
        
        // Update interval when speed changes
        Livewire.hook('commit', ({ component, commit }) => {
            if (component.fingerprint.name === 'games.auto-demo' && @this.isPlaying) {
                if (autoPlayInterval) {
                    clearInterval(autoPlayInterval);
                    autoPlayInterval = setInterval(() => {
                        @this.makeAutoMove();
                    }, @this.speed);
                }
            }
        });
    });
</script>
