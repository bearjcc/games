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
    <x-game.accessibility>
        @if($state['gameOver'])
            <div>Game Over! {{ $state['winner'] ? ucfirst($state['winner']) . ' wins!' : 'Game ended in a draw.' }}</div>
        @elseif($state['mustCapture'])
            <div>{{ ucfirst($state['currentPlayer']) }} must capture an opponent piece.</div>
        @else
            <div>{{ ucfirst($state['currentPlayer']) }}'s turn - {{ $state['phase'] }} phase</div>
        @endif
    </x-game.accessibility>

    <div class="morris-game">
        <!-- Game Header -->
        <div class="game-header">
            <h1 class="game-title">9 Men's Morris</h1>
            <div class="game-status">
                <div class="current-player">
                    <span class="player-indicator {{ $state['currentPlayer'] }}">
                        {{ ucfirst($state['currentPlayer']) }}'s Turn
                    </span>
                </div>
                <div class="game-phase">
                    Phase: {{ ucfirst($state['phase']) }}
                </div>
                @if($state['mustCapture'])
                    <div class="capture-notice">Must capture opponent piece!</div>
                @endif
            </div>
        </div>

        <!-- Game Settings -->
        <div class="game-settings">
            <div class="setting-group">
                <label>Mode:</label>
                <select wire:change="setGameMode($event.target.value)" class="setting-select">
                    <option value="human_vs_ai" {{ $gameMode === 'human_vs_ai' ? 'selected' : '' }}>vs AI</option>
                    <option value="human_vs_human" {{ $gameMode === 'human_vs_human' ? 'selected' : '' }}>vs Human</option>
                </select>
            </div>
            
            @if($gameMode === 'human_vs_ai')
                <div class="setting-group">
                    <label>Difficulty:</label>
                    <select wire:change="setDifficulty($event.target.value)" class="setting-select">
                        <option value="easy" {{ $difficulty === 'easy' ? 'selected' : '' }}>Easy</option>
                        <option value="medium" {{ $difficulty === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="hard" {{ $difficulty === 'hard' ? 'selected' : '' }}>Hard</option>
                    </select>
                </div>
            @endif
        </div>

        <!-- Game Board -->
        <div class="game-board">
            <svg class="board-svg" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
                <!-- Board Lines -->
                <!-- Outer square -->
                <rect x="20" y="20" width="260" height="260" fill="none" stroke="#8B4513" stroke-width="2"/>
                <!-- Middle square -->
                <rect x="60" y="60" width="180" height="180" fill="none" stroke="#8B4513" stroke-width="2"/>
                <!-- Inner square -->
                <rect x="100" y="100" width="100" height="100" fill="none" stroke="#8B4513" stroke-width="2"/>
                
                <!-- Connecting lines -->
                <line x1="150" y1="20" x2="150" y2="100" stroke="#8B4513" stroke-width="2"/>
                <line x1="150" y1="200" x2="150" y2="280" stroke="#8B4513" stroke-width="2"/>
                <line x1="20" y1="150" x2="100" y2="150" stroke="#8B4513" stroke-width="2"/>
                <line x1="200" y1="150" x2="280" y2="150" stroke="#8B4513" stroke-width="2"/>

                <!-- Position circles -->
                @for($pos = 0; $pos < 24; $pos++)
                    @php
                        $coords = NineMensMorrisEngine::getPositionCoordinates($pos);
                        $x = 20 + ($coords[0] / 100) * 260;
                        $y = 20 + ($coords[1] / 100) * 260;
                        $piece = $state['board'][$pos];
                    @endphp
                    
                    <circle cx="{{ $x }}" cy="{{ $y }}" r="12" 
                            fill="{{ $piece ? ($piece === 'white' ? '#F5F5F5' : '#2C3E50') : '#D4A574' }}"
                            stroke="#8B4513" stroke-width="2"
                            class="board-position {{ $selectedPosition === $pos ? 'selected' : '' }} 
                                   {{ $state['mustCapture'] && in_array($pos, NineMensMorrisEngine::getCapturablePieces($state)) ? 'capturable' : '' }}"
                            wire:click="clickPosition({{ $pos }})"
                            style="cursor: pointer; transition: all 0.2s ease;">
                    </circle>
                    
                    <!-- Position number for debugging -->
                    <text x="{{ $x }}" y="{{ $y + 4 }}" text-anchor="middle" 
                          font-size="8" fill="#666" class="position-number">{{ $pos }}</text>
                @endfor
            </svg>
        </div>

        <!-- Game Info -->
        <div class="game-info">
            <div class="pieces-info">
                <div class="white-info">
                    <div class="pieces-display">
                        <div class="piece white-piece"></div>
                        <span>White: {{ $state['whitePiecesOnBoard'] }} on board</span>
                        @if($state['whitePieces'] > 0)
                            <span>({{ $state['whitePieces'] }} to place)</span>
                        @endif
                    </div>
                </div>
                
                <div class="black-info">
                    <div class="pieces-display">
                        <div class="piece black-piece"></div>
                        <span>Black: {{ $state['blackPiecesOnBoard'] }} on board</span>
                        @if($state['blackPieces'] > 0)
                            <span>({{ $state['blackPieces'] }} to place)</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Game Statistics -->
            @php $stats = $this->getStats(); @endphp
            <div class="game-stats">
                <div class="stat-item">
                    <span class="stat-label">Moves:</span>
                    <span class="stat-value">{{ $stats['moves'] }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">White Mills:</span>
                    <span class="stat-value">{{ $stats['whiteMills'] }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Black Mills:</span>
                    <span class="stat-value">{{ $stats['blackMills'] }}</span>
                </div>
            </div>
        </div>

        <!-- Game Controls -->
        <div class="game-controls">
            <button wire:click="resetGame" class="game-btn new-game-btn">
                New Game
            </button>
            <button wire:click="undo" 
                    class="game-btn undo-btn {{ $this->canUndo() ? '' : 'disabled' }}" 
                    {{ $this->canUndo() ? '' : 'disabled' }}>
                ↶ Undo
            </button>
            <button wire:click="toggleHints" class="game-btn hint-btn {{ $showHints ? 'active' : '' }}">
                💡 Hints
            </button>
        </div>

        <!-- Hint Panel -->
        <x-game.hint-panel 
            :hints="$this->getHints()" 
            :show="$showHints" 
            position="bottom-right" />

        <!-- Win Message -->
        @if($state['gameOver'])
            <div class="win-overlay">
                <div class="win-message">
                    <h2 class="win-title">🎉 Game Over! 🎉</h2>
                    @if($state['winner'])
                        <p class="win-details">{{ ucfirst($state['winner']) }} wins!</p>
                        @php $scores = NineMensMorrisEngine::getScore($state); @endphp
                        <div class="win-stats">
                            <div>Final Score: <strong>{{ number_format($scores[$state['winner']]) }}</strong></div>
                            <div>Moves: <strong>{{ $stats['moves'] }}</strong></div>
                        </div>
                    @else
                        <p class="win-details">The game ended in a draw!</p>
                    @endif
                    <button wire:click="resetGame" class="game-btn new-game-btn">Play Again</button>
                </div>
            </div>
        @endif
    </div>

    <style>
        .morris-game {
            min-height: 100vh;
            background: linear-gradient(135deg, #8B4513 0%, #A0522D 50%, #CD853F 100%);
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .game-header {
            text-align: center;
            margin-bottom: 20px;
            color: white;
        }

        .game-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .game-status {
            display: flex;
            justify-content: center;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .current-player {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .player-indicator.white {
            color: #F5F5F5;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }

        .player-indicator.black {
            color: #2C3E50;
            background: rgba(255,255,255,0.2);
            padding: 4px 8px;
            border-radius: 4px;
        }

        .game-phase {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.9rem;
        }

        .capture-notice {
            background: #FF6B6B;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.9rem;
            animation: pulse 1s ease-in-out infinite alternate;
        }

        @keyframes pulse {
            from { opacity: 0.8; }
            to { opacity: 1; }
        }

        .game-settings {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .setting-group {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            padding: 8px 12px;
            border-radius: 8px;
            color: white;
        }

        .setting-select {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 4px;
            color: white;
            padding: 4px 8px;
        }

        .game-board {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .board-svg {
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .board-position {
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .board-position:hover {
            transform: scale(1.2);
        }

        .board-position.selected {
            stroke: #FFD700;
            stroke-width: 4;
            filter: drop-shadow(0 0 6px #FFD700);
        }

        .board-position.capturable {
            stroke: #FF6B6B;
            stroke-width: 4;
            animation: blink 0.8s ease-in-out infinite alternate;
        }

        @keyframes blink {
            from { opacity: 0.7; }
            to { opacity: 1; }
        }

        .position-number {
            font-family: monospace;
            pointer-events: none;
        }

        .game-info {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .pieces-info {
            display: flex;
            gap: 30px;
        }

        .pieces-display {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            padding: 12px 16px;
            border-radius: 8px;
            color: white;
        }

        .piece {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid #8B4513;
        }

        .white-piece {
            background: #F5F5F5;
        }

        .black-piece {
            background: #2C3E50;
        }

        .game-stats {
            display: flex;
            gap: 20px;
        }

        .stat-item {
            background: rgba(255,255,255,0.1);
            padding: 12px 16px;
            border-radius: 8px;
            color: white;
            text-align: center;
        }

        .stat-label {
            display: block;
            font-size: 0.8rem;
            opacity: 0.8;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .game-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .game-btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .new-game-btn {
            background: #ef4444;
            color: white;
        }

        .new-game-btn:hover {
            background: #dc2626;
        }

        .undo-btn {
            background: #3b82f6;
            color: white;
        }

        .undo-btn:hover:not(.disabled) {
            background: #2563eb;
        }

        .hint-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .hint-btn:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }

        .hint-btn.active {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .game-btn.disabled {
            background: #6b7280;
            color: #9ca3af;
            cursor: not-allowed;
            opacity: 0.5;
        }

        .win-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .win-message {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            max-width: 400px;
            color: #1f2937;
        }

        .win-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 16px;
        }

        .win-details {
            font-size: 16px;
            margin-bottom: 20px;
        }

        .win-stats {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .morris-game {
                padding: 10px;
            }

            .board-svg {
                width: 300px;
                height: 300px;
            }

            .game-info {
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }

            .pieces-info {
                flex-direction: column;
                gap: 15px;
            }

            .game-stats {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</div>
