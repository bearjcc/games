{{-- 
    GAME TEMPLATE for Liminal Design System
    
    Copy this file to create new games with consistent UX.
    Replace {{GAME_NAME}} with your actual game name throughout.
    
    This template demonstrates all common patterns and components.
--}}

<?php
use App\Games\{{GAME_NAME}}\{{GAME_NAME}}Game;
use App\Games\{{GAME_NAME}}\{{GAME_NAME}}Engine;
use App\Services\UserBestScoreService;
use App\Services\HintEngine;
use Livewire\Volt\Component;

new class extends Component
{
    // Standard game state properties
    public array $state;
    public string $gameMode = 'single_player'; // or 'vs_ai', 'pass_and_play'
    public string $difficulty = 'medium';
    public bool $showHints = false;
    public array $undoStack = [];
    
    // Game-specific properties (customize as needed)
    public $selectedPosition = null;
    public array $validMoves = [];

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $game = new {{GAME_NAME}}Game();
        $this->state = $game->newGameState();
        $this->state['mode'] = $this->gameMode;
        $this->state['difficulty'] = $this->difficulty;
        $this->selectedPosition = null;
        $this->validMoves = [];
        $this->undoStack = [];
        $this->updateValidMoves();
    }

    // Standard game mode management
    public function setGameMode($mode)
    {
        $this->gameMode = $mode;
        $this->resetGame();
    }

    public function setDifficulty($difficulty)
    {
        $this->difficulty = $difficulty;
        $this->resetGame();
    }

    // Game interaction methods (customize as needed)
    public function makeMove($moveData)
    {
        if ($this->state['gameOver']) {
            return;
        }

        $game = new {{GAME_NAME}}Game();
        $move = ['type' => 'move', 'data' => $moveData];

        if ($game->validateMove($this->state, $move)) {
            $this->saveStateForUndo();
            $this->state = $game->applyMove($this->state, $move);
            $this->updateValidMoves();

            // Update best score if applicable
            if ($this->state['gameOver'] && auth()->check()) {
                $score = $game->getScore($this->state);
                app(UserBestScoreService::class)->updateIfBetter(
                    auth()->user(),
                    '{{GAME_SLUG}}',
                    $score
                );
            }
        }
    }

    // Standard undo functionality
    public function undo()
    {
        if (!empty($this->undoStack)) {
            $this->state = array_pop($this->undoStack);
            $this->updateValidMoves();
        }
    }

    public function canUndo()
    {
        return !empty($this->undoStack);
    }

    // Standard hint system
    public function toggleHints()
    {
        $this->showHints = !$this->showHints;
    }

    public function getHints()
    {
        if (!$this->showHints) return [];
        
        return {{GAME_NAME}}Engine::getHints($this->state);
    }

    public function showBestMove()
    {
        $hint = {{GAME_NAME}}Engine::getBestMove($this->state);
        if ($hint) {
            // Highlight the best move somehow
            $this->selectedPosition = $hint['from'] ?? null;
            $this->updateValidMoves();
        }
    }

    // Helper methods
    private function saveStateForUndo()
    {
        $this->undoStack[] = $this->state;
        
        // Limit undo stack size
        if (count($this->undoStack) > 10) {
            array_shift($this->undoStack);
        }
    }

    private function updateValidMoves()
    {
        $this->validMoves = {{GAME_NAME}}Engine::getValidMoves($this->state, $this->selectedPosition);
    }

    public function getStats()
    {
        return {{GAME_NAME}}Engine::getStats($this->state);
    }
}; ?>

<div>
    {{-- Always include these core components --}}
    <x-game.styles />
    <x-game.animations />
    
    <x-game.layout title="{{GAME_DISPLAY_NAME}}">
        {{-- Game Header - Standard Pattern --}}
        <div class="game-header">
            <div class="game-status">
                @if($state['gameOver'])
                    @if($state['winner'] ?? false)
                        <div class="winner-indicator">
                            {{ ucfirst($state['winner']) }} wins the game!
                        </div>
                    @elseif(isset($state['isDraw']) && $state['isDraw'])
                        <div class="draw-indicator">Game ended in a draw</div>
                    @else
                        <div class="draw-indicator">Game over</div>
                    @endif
                @else
                    <div class="player-indicator">
                        {{-- Customize based on your game's state --}}
                        {{ $state['currentPlayer'] ?? 'Your' }} turn
                        @if($state['specialCondition'] ?? false)
                            <span class="special-condition">- Special condition message</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Game Settings - Standard Pattern --}}
        <div class="game-settings">
            <div class="setting-row">
                <label class="setting-label">Mode:</label>
                <select wire:change="setGameMode($event.target.value)" class="game-select">
                    <option value="single_player" {{ $gameMode === 'single_player' ? 'selected' : '' }}>Single Player</option>
                    <option value="vs_ai" {{ $gameMode === 'vs_ai' ? 'selected' : '' }}>vs AI</option>
                    <option value="pass_and_play" {{ $gameMode === 'pass_and_play' ? 'selected' : '' }}>Pass & Play</option>
                </select>
            </div>
            
            @if($gameMode === 'vs_ai')
                <div class="setting-row">
                    <label class="setting-label">Difficulty:</label>
                    <select wire:change="setDifficulty($event.target.value)" class="game-select">
                        <option value="easy" {{ $difficulty === 'easy' ? 'selected' : '' }}>Easy</option>
                        <option value="medium" {{ $difficulty === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="hard" {{ $difficulty === 'hard' ? 'selected' : '' }}>Hard</option>
                    </select>
                </div>
            @endif
        </div>

        {{-- Game Board - Customize this section --}}
        <div class="game-board-container">
            <div class="{{GAME_SLUG}}-board">
                {{-- 
                    REPLACE THIS SECTION WITH YOUR GAME BOARD
                    
                    Examples:
                    - Grid-based: Use CSS Grid with game-board-grid class
                    - Card-based: Use x-game.card components
                    - Piece-based: Use x-game.piece components
                    - Tile-based: Use x-game.tile components
                    - Custom: Create game-specific layout
                --}}
                
                {{-- Example: Grid-based board --}}
                @for($row = 0; $row < 8; $row++)
                    @for($col = 0; $col < 8; $col++)
                        <div class="board-square" 
                             wire:click="makeMove(['row' => {{ $row }}, 'col' => {{ $col }}])"
                             data-position="{{ $row }}-{{ $col }}">
                            
                            {{-- Example: Using game pieces --}}
                            @if($state['board'][$row][$col] ?? false)
                                <x-game.piece
                                    type="circle"
                                    :piece="$state['board'][$row][$col]"
                                    :player="$state['board'][$row][$col]['player'] ?? null"
                                    :position="$row . '-' . $col"
                                    :selected="false"
                                    size="default" />
                            @endif
                        </div>
                    @endfor
                @endfor
            </div>
        </div>

        {{-- Game Info Panel - Standard Pattern --}}
        @php $stats = $this->getStats(); @endphp
        <div class="game-info">
            <h3>Game Stats</h3>
            <div class="game-stats">
                {{-- Customize these stats based on your game --}}
                <div class="stat-item">
                    <span class="stat-label">Moves:</span>
                    <span class="stat-value">{{ $stats['moves'] ?? 0 }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Score:</span>
                    <span class="stat-value">{{ $stats['score'] ?? 0 }}</span>
                </div>
                @if(isset($stats['time']))
                    <div class="stat-item">
                        <span class="stat-label">Time:</span>
                        <span class="stat-value">{{ $stats['time'] }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Game Controls - Standard Pattern --}}
        <div class="game-controls">
            <button wire:click="resetGame" class="game-button">
                New Game
            </button>
            <button wire:click="undo" 
                    class="game-button {{ $this->canUndo() ? '' : 'disabled' }}" 
                    {{ $this->canUndo() ? '' : 'disabled' }}>
                Undo
            </button>
            <button wire:click="showBestMove" class="game-button">
                Best Move
            </button>
            <button wire:click="toggleHints" class="game-button {{ $showHints ? 'primary' : '' }}">
                {{ $showHints ? 'Hide Hints' : 'Show Hints' }}
            </button>
        </div>

        {{-- Hints Display - Standard Pattern --}}
        @if($showHints && count($this->getHints()) > 0)
            <div class="game-hints">
                <h4>Hints</h4>
                <ul class="hints-list">
                    @foreach($this->getHints() as $hint)
                        <li class="hint-item">{{ $hint }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Instructions (Optional) --}}
        <div class="game-instructions">
            <h4>How to Play</h4>
            <p class="instructions-text">
                {{-- Add your game instructions here --}}
                Brief explanation of how to play your game.
            </p>
        </div>
        
    </x-game.layout>
</div>

<style>
    /* Game-specific liminal styling */
    .{{GAME_SLUG}}-board {
        {{-- Customize this for your specific board layout --}}
        display: grid;
        grid-template-columns: repeat(8, 1fr);
        max-width: 32rem;
        aspect-ratio: 1;
        margin: 0 auto;
        border: 2px solid rgb(100 116 139);
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .dark .{{GAME_SLUG}}-board {
        border-color: rgb(71 85 105);
    }

    .board-square {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        background: rgb(248 250 252);
        border: 1px solid rgb(226 232 240);
    }

    .dark .board-square {
        background: rgb(30 41 59);
        border-color: rgb(71 85 105);
    }

    .board-square:hover {
        background-color: rgb(59 130 246 / 0.1);
    }

    .special-condition {
        color: rgb(239 68 68);
        font-weight: 600;
    }

    /* Add more game-specific styles as needed */
</style>
