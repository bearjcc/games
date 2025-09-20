<?php

use App\Games\PegSolitaire\PegSolitaireGame;
use App\Games\PegSolitaire\PegSolitaireEngine;
use App\Services\UserBestScoreService;
use App\Services\HintEngine;
use Livewire\Volt\Component;

new class extends Component
{
    public $state;
    public $selectedPeg = null;
    public $validMoves = [];
    public $showHints = false;
    public $undoStack = [];
    public $difficulty = 'standard';
    
    public function mount()
    {
        $this->resetGame();
    }
    
    public function resetGame()
    {
        if ($this->difficulty === 'random') {
            $emptyHole = rand(0, 14);
            $this->state = PegSolitaireEngine::newGameWithEmptyHole($emptyHole);
        } else {
            $this->state = PegSolitaireEngine::initialState();
        }
        
        $this->selectedPeg = null;
        $this->undoStack = [];
        $this->updateValidMoves();
    }
    
    public function clickPosition($position)
    {
        if ($this->state['gameOver']) {
            return;
        }
        
        if ($this->selectedPeg === null) {
            // Select a peg to move
            if ($this->state['board'][$position]) {
                $this->selectedPeg = $position;
                $this->updateValidMoves();
            }
        } else {
            // Try to make a move
            if ($position === $this->selectedPeg) {
                // Deselect
                $this->selectedPeg = null;
                $this->updateValidMoves();
            } else {
                $this->attemptMove($position);
            }
        }
    }
    
    private function attemptMove($toPosition)
    {
        // Find valid move from selected peg to target position
        foreach ($this->validMoves as $move) {
            if ($move['from'] === $this->selectedPeg && $move['to'] === $toPosition) {
                $this->saveStateForUndo();
                $this->state = PegSolitaireEngine::applyMove($this->state, $move);
                $this->selectedPeg = null;
                $this->updateValidMoves();
                break;
            }
        }
    }
    
    private function updateValidMoves()
    {
        $allMoves = PegSolitaireEngine::getValidMoves($this->state);
        
        if ($this->selectedPeg !== null) {
            // Filter moves for selected peg
            $this->validMoves = array_filter($allMoves, function($move) {
                return $move['from'] === $this->selectedPeg;
            });
        } else {
            $this->validMoves = $allMoves;
        }
    }
    
    public function undo()
    {
        if (!empty($this->undoStack)) {
            $this->state = array_pop($this->undoStack);
            $this->selectedPeg = null;
            $this->updateValidMoves();
        }
    }
    
    private function saveStateForUndo()
    {
        $this->undoStack[] = $this->state;
        
        // Limit undo stack
        if (count($this->undoStack) > 20) {
            array_shift($this->undoStack);
        }
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
        $game = new PegSolitaireGame();
        return HintEngine::getHints($game, $this->state, [
            'difficulty' => 'beginner',
            'maxHints' => 3
        ]);
    }
    
    public function getStats()
    {
        return PegSolitaireEngine::getStats($this->state);
    }
    
    public function canUndo()
    {
        return !empty($this->undoStack);
    }
    
    public function showHint()
    {
        $hint = PegSolitaireEngine::getBestMove($this->state);
        if ($hint) {
            $this->selectedPeg = $hint['from'];
            $this->updateValidMoves();
        }
    }
}; ?>

<div>
    <x-game.accessibility>
        @if($state['gameOver'])
            <div>Game Over! {{ $state['pegsRemaining'] }} pegs remaining. {{ PegSolitaireEngine::getScoreMessage($state['pegsRemaining']) }}</div>
        @else
            <div>{{ $state['pegsRemaining'] }} pegs remaining. Click a peg to select it, then click an empty hole to jump.</div>
        @endif
    </x-game.accessibility>

    <div class="peg-solitaire-game">
        <!-- Game Header -->
        <div class="game-header">
            <h1 class="game-title">Peg Solitaire</h1>
            <div class="game-subtitle">The Cracker Barrel Challenge</div>
        </div>

        <!-- Game Settings -->
        <div class="game-settings">
            <div class="setting-group">
                <label>Difficulty:</label>
                <select wire:change="setDifficulty($event.target.value)" class="setting-select">
                    <option value="standard" {{ $difficulty === 'standard' ? 'selected' : '' }}>Standard (center empty)</option>
                    <option value="random" {{ $difficulty === 'random' ? 'selected' : '' }}>Random empty hole</option>
                </select>
            </div>
        </div>

        <!-- Game Board -->
        <div class="game-board">
            <svg class="board-svg" viewBox="0 0 300 200" xmlns="http://www.w3.org/2000/svg">
                <!-- Background -->
                <rect width="300" height="200" fill="#8B4513" rx="10"/>
                
                <!-- Board holes -->
                @for($pos = 0; $pos < 15; $pos++)
                    @php
                        $coords = PegSolitaireEngine::getPositionCoordinates($pos);
                        $x = $coords[0] * 3; // Scale to SVG coordinates
                        $y = $coords[1] * 2;
                        $hasPeg = $state['board'][$pos];
                        $isSelected = $selectedPeg === $pos;
                        $canMoveTo = false;
                        
                        // Check if this is a valid destination
                        foreach ($validMoves as $move) {
                            if ($move['to'] === $pos) {
                                $canMoveTo = true;
                                break;
                            }
                        }
                    @endphp
                    
                    <!-- Hole -->
                    <circle cx="{{ $x }}" cy="{{ $y }}" r="10" 
                            fill="#654321" 
                            stroke="#4A4A4A" 
                            stroke-width="1"/>
                    
                    <!-- Peg -->
                    @if($hasPeg)
                        <circle cx="{{ $x }}" cy="{{ $y }}" r="8" 
                                fill="{{ $isSelected ? '#FFD700' : '#8B0000' }}"
                                stroke="{{ $isSelected ? '#FFA500' : '#654321' }}" 
                                stroke-width="2"
                                class="peg {{ $isSelected ? 'selected' : '' }}"
                                wire:click="clickPosition({{ $pos }})"
                                style="cursor: pointer; transition: all 0.2s ease;">
                        </circle>
                    @else
                        <!-- Empty hole with possible move indicator -->
                        <circle cx="{{ $x }}" cy="{{ $y }}" r="6" 
                                fill="{{ $canMoveTo ? '#90EE90' : 'transparent' }}"
                                stroke="{{ $canMoveTo ? '#228B22' : 'transparent' }}" 
                                stroke-width="2"
                                class="empty-hole {{ $canMoveTo ? 'can-move-to' : '' }}"
                                wire:click="clickPosition({{ $pos }})"
                                style="cursor: {{ $canMoveTo ? 'pointer' : 'default' }}; transition: all 0.2s ease;">
                        </circle>
                    @endif
                    
                    <!-- Position number for reference -->
                    <text x="{{ $x }}" y="{{ $y + 3 }}" text-anchor="middle" 
                          font-size="6" fill="#DDD" class="position-number">{{ $pos }}</text>
                @endfor
            </svg>
        </div>

        <!-- Game Info -->
        @php $stats = $this->getStats(); @endphp
        <div class="game-info">
            <div class="main-stats">
                <div class="pegs-remaining">
                    <div class="stat-big">{{ $state['pegsRemaining'] }}</div>
                    <div class="stat-label">Pegs Left</div>
                </div>
                
                <div class="moves-made">
                    <div class="stat-big">{{ $state['moves'] }}</div>
                    <div class="stat-label">Moves</div>
                </div>
                
                <div class="current-score">
                    <div class="stat-big">{{ number_format($stats['score']) }}</div>
                    <div class="stat-label">Score</div>
                </div>
            </div>
            
            <div class="performance-stats">
                <div class="stat-item">
                    <span class="stat-label">Efficiency:</span>
                    <span class="stat-value">{{ $stats['efficiency'] }}%</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Completion:</span>
                    <span class="stat-value">{{ $stats['completion'] }}%</span>
                </div>
            </div>

            @if($state['gameOver'])
                <div class="final-message">
                    <div class="score-message">{{ $stats['scoreMessage'] }}</div>
                    @if($state['won'])
                        <div class="perfect-game">🎉 Perfect Game! Only 1 peg remaining! 🎉</div>
                    @endif
                </div>
            @endif
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
            <button wire:click="showHint" class="game-btn hint-move-btn">
                🔍 Show Best Move
            </button>
            <button wire:click="toggleHints" class="game-btn hint-btn {{ $showHints ? 'active' : '' }}">
                💡 Hints
            </button>
        </div>

        <!-- Hint Panel -->
        <x-game.hint-panel 
            :hints="$this->getHints()" 
            :show="$showHints" 
            position="bottom-left" />

        <!-- Instructions -->
        <div class="instructions">
            <h3>How to Play:</h3>
            <ul>
                <li>Click a peg to select it (highlighted in gold)</li>
                <li>Click an empty hole to jump over adjacent peg</li>
                <li>Jumped pegs are removed from the board</li>
                <li>Goal: Remove all pegs except one!</li>
                <li><strong>Genius:</strong> 1 peg | <strong>Smart:</strong> 2-3 pegs | <strong>Try Again:</strong> 4+ pegs</li>
            </ul>
        </div>

        <!-- Win Message -->
        @if($state['gameOver'])
            <div class="win-overlay">
                <div class="win-message">
                    <h2 class="win-title">🎯 Game Complete! 🎯</h2>
                    <div class="final-score">
                        <div class="pegs-left">{{ $state['pegsRemaining'] }} peg{{ $state['pegsRemaining'] !== 1 ? 's' : '' }} remaining</div>
                        <div class="score-message">{{ $stats['scoreMessage'] }}</div>
                        <div class="final-stats">
                            <div>Final Score: <strong>{{ number_format($stats['score']) }}</strong></div>
                            <div>Moves: <strong>{{ $state['moves'] }}</strong></div>
                            <div>Efficiency: <strong>{{ $stats['efficiency'] }}%</strong></div>
                        </div>
                    </div>
                    <button wire:click="resetGame" class="game-btn new-game-btn">Play Again</button>
                </div>
            </div>
        @endif
    </div>

    <style>
        .peg-solitaire-game {
            min-height: 100vh;
            background: linear-gradient(135deg, #2C5F2D 0%, #97BC62 50%, #2C5F2D 100%);
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: white;
        }

        .game-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .game-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .game-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-style: italic;
        }

        .game-settings {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .setting-group {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            padding: 8px 12px;
            border-radius: 8px;
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
            height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        .peg {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .peg:hover {
            transform: scale(1.2);
        }

        .peg.selected {
            filter: drop-shadow(0 0 8px #FFD700);
            animation: pulse 1s ease-in-out infinite alternate;
        }

        .empty-hole.can-move-to {
            animation: glow 1s ease-in-out infinite alternate;
        }

        @keyframes pulse {
            from { opacity: 0.8; }
            to { opacity: 1; }
        }

        @keyframes glow {
            from { opacity: 0.6; }
            to { opacity: 1; }
        }

        .position-number {
            font-family: monospace;
            pointer-events: none;
        }

        .game-info {
            max-width: 800px;
            margin: 0 auto 30px;
        }

        .main-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .main-stats > div {
            text-align: center;
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            min-width: 100px;
        }

        .stat-big {
            font-size: 2.5rem;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .performance-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .stat-item {
            background: rgba(255,255,255,0.1);
            padding: 12px 16px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-weight: bold;
            margin-left: 8px;
        }

        .final-message {
            text-align: center;
            background: rgba(255,255,255,0.2);
            padding: 20px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .score-message {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .perfect-game {
            font-size: 1.2rem;
            color: #FFD700;
            animation: celebration 2s ease-in-out infinite;
        }

        @keyframes celebration {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .game-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
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

        .hint-move-btn {
            background: #f59e0b;
            color: white;
        }

        .hint-move-btn:hover {
            background: #d97706;
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

        .instructions {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .instructions h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #FFD700;
        }

        .instructions ul {
            margin: 0;
            padding-left: 20px;
        }

        .instructions li {
            margin-bottom: 8px;
            line-height: 1.4;
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
            background: linear-gradient(135deg, #97BC62, #2C5F2D);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            max-width: 400px;
            color: white;
        }

        .win-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .final-score {
            margin-bottom: 30px;
        }

        .pegs-left {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #FFD700;
        }

        .final-stats {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 15px;
            font-size: 14px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .peg-solitaire-game {
                padding: 10px;
            }

            .board-svg {
                width: 320px;
                height: 240px;
            }

            .main-stats {
                gap: 20px;
            }

            .main-stats > div {
                padding: 15px;
                min-width: 80px;
            }

            .stat-big {
                font-size: 2rem;
            }

            .performance-stats {
                gap: 15px;
            }

            .game-controls {
                gap: 10px;
            }

            .game-btn {
                padding: 8px 16px;
                font-size: 14px;
            }
        }
    </style>
</div>
