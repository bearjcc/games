<?php

use App\Games\NineMensMorris\NineMensMorrisGame;
use App\Games\NineMensMorris\NineMensMorrisEngine;
use App\Services\UserBestScoreService;
use App\Services\HintEngine;
use Livewire\Volt\Component;

new class extends Component
{
    public $state;
    public $selectedPosition = null;
    public $validMoves = [];
    public $gameMode = 'human_vs_ai';
    public $difficulty = 'medium';
    public $showHints = false;
    public $undoStack = [];
    
    public function mount()
    {
        $this->resetGame();
    }
    
    public function resetGame()
    {
        $this->state = NineMensMorrisEngine::initialState();
        $this->state['mode'] = $this->gameMode;
        $this->state['difficulty'] = $this->difficulty;
        $this->selectedPosition = null;
        $this->validMoves = [];
        $this->undoStack = [];
        $this->updateValidMoves();
    }
    
    public function clickPosition($position)
    {
        if ($this->state['gameOver'] || $this->state['currentPlayer'] !== 'white') {
            return;
        }
        
        if ($this->state['mustCapture']) {
            $this->handleCapture($position);
        } else {
            $this->handleNormalMove($position);
        }
        
        // AI move after human move
        if (!$this->state['gameOver'] && $this->gameMode === 'human_vs_ai' && $this->state['currentPlayer'] === 'black') {
            $this->makeAIMove();
        }
    }
    
    private function handleCapture($position)
    {
        $capturable = NineMensMorrisEngine::getCapturablePieces($this->state);
        
        if (in_array($position, $capturable)) {
            $this->saveStateForUndo();
            $this->state = NineMensMorrisEngine::capturePiece($this->state, $position);
            $this->updateValidMoves();
        }
    }
    
    private function handleNormalMove($position)
    {
        if ($this->state['phase'] === 'placement') {
            if ($this->state['board'][$position] === null) {
                $this->saveStateForUndo();
                $this->state = NineMensMorrisEngine::placePiece($this->state, $position);
                $this->updateValidMoves();
            }
        } else {
            if ($this->selectedPosition === null) {
                // Select a piece to move
                if ($this->state['board'][$position] === 'white') {
                    $this->selectedPosition = $position;
                    $this->updateValidMoves();
                }
            } else {
                // Move selected piece
                if ($position === $this->selectedPosition) {
                    // Deselect
                    $this->selectedPosition = null;
                    $this->updateValidMoves();
                } else if ($this->state['board'][$position] === null) {
                    $this->saveStateForUndo();
                    $this->state = NineMensMorrisEngine::movePiece($this->state, $this->selectedPosition, $position);
                    $this->selectedPosition = null;
                    $this->updateValidMoves();
                }
            }
        }
    }
    
    private function updateValidMoves()
    {
        $this->validMoves = NineMensMorrisEngine::getValidMoves($this->state);
    }
    
    private function makeAIMove()
    {
        $aiMove = NineMensMorrisEngine::calculateAIMove($this->state, $this->difficulty);
        
        if ($aiMove) {
            if ($aiMove['type'] === 'capture') {
                $this->state = NineMensMorrisEngine::capturePiece($this->state, $aiMove['position']);
            } else if ($aiMove['type'] === 'place') {
                $this->state = NineMensMorrisEngine::placePiece($this->state, $aiMove['position']);
            } else if ($aiMove['type'] === 'move') {
                $this->state = NineMensMorrisEngine::movePiece($this->state, $aiMove['from'], $aiMove['to']);
            }
            
            $this->updateValidMoves();
        }
    }
    
    public function undo()
    {
        if (!empty($this->undoStack)) {
            $this->state = array_pop($this->undoStack);
            $this->selectedPosition = null;
            $this->updateValidMoves();
        }
    }
    
    private function saveStateForUndo()
    {
        $this->undoStack[] = $this->state;
        
        // Limit undo stack
        if (count($this->undoStack) > 10) {
            array_shift($this->undoStack);
        }
    }
    
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
    
    public function toggleHints()
    {
        $this->showHints = !$this->showHints;
    }
    
    public function getHints()
    {
        $game = new NineMensMorrisGame();
        return HintEngine::getHints($game, $this->state, [
            'difficulty' => 'beginner',
            'maxHints' => 3
        ]);
    }
    
    public function getStats()
    {
        return NineMensMorrisEngine::getStats($this->state);
    }
    
    public function canUndo()
    {
        return !empty($this->undoStack);
    }
}; ?>

<div>
    <x-game.styles />
    <x-game.animations />
    
    <x-game.layout title="Nine Men's Morris">
        <!-- Game Header -->
        <div class="game-header">
            <div class="game-status">
                @if($state['gameOver'])
                    @if($state['winner'])
                        <div class="winner-indicator">
                            {{ ucfirst($state['winner']) }} wins the game!
                        </div>
                    @else
                        <div class="draw-indicator">Game ended in a draw</div>
                    @endif
                @else
                    <div class="player-indicator">
                        {{ ucfirst($state['currentPlayer']) }}'s turn
                        @if($state['mustCapture'])
                            <span class="capture-required">- Choose piece to remove</span>
                        @endif
                    </div>
                    <div class="phase-indicator">
                        @if($state['phase'] === 'placement')
                            Placing pieces ({{ $state['whitePieces'] + $state['blackPieces'] }} remaining)
                        @elseif($state['phase'] === 'movement')
                            Moving pieces
                        @elseif($state['phase'] === 'flying')
                            Flying phase ({{ $state['currentPlayer'] }} has 3 pieces)
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Game Settings -->
        <div class="game-settings">
            <div class="setting-row">
                <label class="setting-label">Mode:</label>
                <select wire:change="setGameMode($event.target.value)" class="game-select">
                    <option value="human_vs_ai" {{ $gameMode === 'human_vs_ai' ? 'selected' : '' }}>vs AI</option>
                    <option value="human_vs_human" {{ $gameMode === 'human_vs_human' ? 'selected' : '' }}>vs Human</option>
                </select>
            </div>
            
            @if($gameMode === 'human_vs_ai')
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

        <!-- Game Board -->
        <div class="game-board-container">
            <div class="morris-board">
                <svg viewBox="0 0 300 300" class="board-grid">
                    <!-- Board Lines with liminal styling -->
                    <!-- Outer square -->
                    <rect x="20" y="20" width="260" height="260" fill="none" stroke="rgb(100 116 139)" stroke-width="2"/>
                    <!-- Middle square -->
                    <rect x="60" y="60" width="180" height="180" fill="none" stroke="rgb(100 116 139)" stroke-width="2"/>
                    <!-- Inner square -->
                    <rect x="100" y="100" width="100" height="100" fill="none" stroke="rgb(100 116 139)" stroke-width="2"/>
                    
                    <!-- Connecting lines -->
                    <line x1="150" y1="20" x2="150" y2="100" stroke="rgb(100 116 139)" stroke-width="2"/>
                    <line x1="150" y1="200" x2="150" y2="280" stroke="rgb(100 116 139)" stroke-width="2"/>
                    <line x1="20" y1="150" x2="100" y2="150" stroke="rgb(100 116 139)" stroke-width="2"/>
                    <line x1="200" y1="150" x2="280" y2="150" stroke="rgb(100 116 139)" stroke-width="2"/>
                </svg>
                
                <!-- Game Pieces -->
                <div class="pieces-container">
                    @for($pos = 0; $pos < 24; $pos++)
                        @php
                            $coords = NineMensMorrisEngine::getPositionCoordinates($pos);
                            $piece = $state['board'][$pos];
                            $isSelected = $selectedPosition === $pos;
                            $isValidTarget = in_array($pos, $validMoves);
                            $isCapturable = $state['mustCapture'] && in_array($pos, NineMensMorrisEngine::getCapturablePieces($state));
                            $x = 20 + ($coords[0] / 100) * 260;
                            $y = 20 + ($coords[1] / 100) * 260;
                        @endphp
                        
                        <div class="position-slot" 
                             style="left: {{ $x - 12 }}px; top: {{ $y - 12 }}px;"
                             wire:click="clickPosition({{ $pos }})"
                             data-position="{{ $pos }}">
                            
                            @if($piece)
                                <x-game.piece
                                    type="circle"
                                    :piece="$piece"
                                    :player="$piece"
                                    :position="$pos"
                                    :selected="$isSelected"
                                    :highlighted="$isCapturable"
                                    :validTarget="false"
                                    size="default"
                                    class="{{ $isCapturable ? 'capturable-piece' : '' }}" />
                            @else
                                <x-game.piece
                                    type="circle"
                                    :piece="null"
                                    :player="null"
                                    :position="$pos"
                                    :selected="false"
                                    :highlighted="false"
                                    :validTarget="$isValidTarget"
                                    variant="empty"
                                    size="default"
                                    class="empty-position" />
                            @endif
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <!-- Game Info Panel -->
        @php $stats = $this->getStats(); @endphp
        <div class="game-info">
            <h3>Game Stats</h3>
            <div class="game-stats">
                <div class="stat-item">
                    <span class="stat-label">White on board:</span>
                    <span class="stat-value">{{ $state['whitePiecesOnBoard'] }}</span>
                </div>
                @if($state['whitePieces'] > 0)
                    <div class="stat-item">
                        <span class="stat-label">White to place:</span>
                        <span class="stat-value">{{ $state['whitePieces'] }}</span>
                    </div>
                @endif
                <div class="stat-item">
                    <span class="stat-label">Black on board:</span>
                    <span class="stat-value">{{ $state['blackPiecesOnBoard'] }}</span>
                </div>
                @if($state['blackPieces'] > 0)
                    <div class="stat-item">
                        <span class="stat-label">Black to place:</span>
                        <span class="stat-value">{{ $state['blackPieces'] }}</span>
                    </div>
                @endif
                <div class="stat-item">
                    <span class="stat-label">Moves:</span>
                    <span class="stat-value">{{ $stats['moves'] }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Mills formed:</span>
                    <span class="stat-value">{{ $stats['whiteMills'] + $stats['blackMills'] }}</span>
                </div>
            </div>
        </div>

        <!-- Game Controls -->
        <div class="game-controls">
            <button wire:click="resetGame" class="game-button">
                New Game
            </button>
            <button wire:click="undo" 
                    class="game-button {{ $this->canUndo() ? '' : 'disabled' }}" 
                    {{ $this->canUndo() ? '' : 'disabled' }}>
                Undo
            </button>
            <button wire:click="toggleHints" class="game-button {{ $showHints ? 'primary' : '' }}">
                {{ $showHints ? 'Hide Hints' : 'Show Hints' }}
            </button>
        </div>

        <!-- Hints Display -->
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
        
</section>
    </x-game.layout>
</div>
<style>
    /* Nine Men's Morris game specific liminal styling */
    .morris-board {
        position: relative;
        max-width: 24rem;
        margin: 0 auto;
        background: rgb(248 250 252);
        border: 2px solid rgb(100 116 139);
        border-radius: 0.5rem;
        padding: 1rem;
    }

    .dark .morris-board {
        background: rgb(30 41 59);
        border-color: rgb(71 85 105);
    }

    .board-grid {
        width: 100%;
        height: auto;
        display: block;
    }

    .dark .board-grid rect,
    .dark .board-grid line {
        stroke: rgb(148 163 184);
    }

    .pieces-container {
        position: absolute;
        inset: 1rem;
        pointer-events: none;
    }

    .position-slot {
        position: absolute;
        width: 1.5rem;
        height: 1.5rem;
        pointer-events: all;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .empty-position {
        background: transparent;
        border: 1px solid rgb(148 163 184 / 0.3);
    }

    .dark .empty-position {
        border-color: rgb(100 116 139 / 0.3);
    }

    .empty-position.piece-valid-target {
        background: rgb(34 197 94 / 0.2);
        border-color: rgb(34 197 94 / 0.5);
    }

    .capturable-piece {
        animation: gentle-pulse 2s ease-in-out infinite;
    }

    .capture-required {
        color: rgb(239 68 68);
        font-weight: 600;
    }

    .phase-indicator {
        font-size: 0.875rem;
        color: rgb(100 116 139);
        margin-top: 0.25rem;
    }

    .dark .phase-indicator {
        color: rgb(148 163 184);
    }

    .setting-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .setting-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: rgb(100 116 139);
        min-width: 5rem;
    }

    .dark .setting-label {
        color: rgb(148 163 184);
    }

    .game-select {
        padding: 0.25rem 0.5rem;
        border: 1px solid rgb(203 213 225);
        border-radius: 0.25rem;
        background: rgb(248 250 252);
        color: rgb(51 65 85);
        font-size: 0.875rem;
    }

    .dark .game-select {
        border-color: rgb(100 116 139);
        background: rgb(71 85 105);
        color: rgb(203 213 225);
    }

    .game-hints {
        background: rgb(248 250 252 / 0.9);
        border: 1px solid rgb(203 213 225);
        border-radius: 0.5rem;
        padding: 1rem;
        margin-top: 1rem;
    }

    .dark .game-hints {
        background: rgb(30 41 59 / 0.9);
        border-color: rgb(71 85 105);
    }

    .game-hints h4 {
        margin: 0 0 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: rgb(100 116 139);
    }

    .dark .game-hints h4 {
        color: rgb(148 163 184);
    }

    .hints-list {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .hint-item {
        padding: 0.25rem 0;
        font-size: 0.875rem;
        color: rgb(71 85 105);
        border-bottom: 1px solid rgb(226 232 240);
    }

    .hint-item:last-child {
        border-bottom: none;
    }

    .dark .hint-item {
        color: rgb(148 163 184);
        border-color: rgb(71 85 105);
    }

    @keyframes gentle-pulse {
        0%, 100% {
            transform: scale(1);
            opacity: 0.8;
        }
        50% {
            transform: scale(1.1);
            opacity: 1;
        }
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .morris-board {
            max-width: 18rem;
        }
    }
</style>
