<?php

use App\Games\Connect4\Connect4Game;
use App\Games\Connect4\Connect4Engine;
use App\Services\UserBestScoreService;
use App\Services\HintEngine;
use Livewire\Volt\Component;

new class extends Component
{
    public $state;
    public $hoveredColumn = null;
    public $gameMode = 'pass_and_play';
    public $difficulty = 'medium';
    public $showHints = false;
    public $undoStack = [];
    public $animatingPiece = null;
    
    public function mount()
    {
        $this->resetGame();
    }
    
    public function resetGame()
    {
        $this->state = Connect4Engine::initialState();
        $this->state['mode'] = $this->gameMode;
        $this->state['difficulty'] = $this->difficulty;
        $this->hoveredColumn = null;
        $this->undoStack = [];
        $this->animatingPiece = null;
    }
    
    public function dropPiece($column)
    {
        if ($this->state['gameOver'] || 
            ($this->gameMode === 'vs_ai' && $this->state['currentPlayer'] === Connect4Engine::YELLOW)) {
            return;
        }
        
        if (!Connect4Engine::canDropInColumn($this->state, $column)) {
            return;
        }
        
        $this->saveStateForUndo();
        
        // Animate piece drop
        $row = Connect4Engine::getLowestAvailableRow($this->state, $column);
        $this->animatingPiece = [
            'column' => $column,
            'row' => $row,
            'player' => $this->state['currentPlayer']
        ];
        
        $this->state = Connect4Engine::dropPiece($this->state, $column);
        
        // Clear animation after piece lands
        $this->dispatch('piece-dropped');
        
        // AI move if needed
        if (!$this->state['gameOver'] && 
            $this->gameMode === 'vs_ai' && 
            $this->state['currentPlayer'] === Connect4Engine::YELLOW) {
            $this->makeAIMove();
        }
    }
    
    private function makeAIMove()
    {
        $aiColumn = Connect4Engine::calculateAIMove($this->state, $this->difficulty);
        
        if ($aiColumn !== null) {
            $this->saveStateForUndo();
            
            // Animate AI piece
            $row = Connect4Engine::getLowestAvailableRow($this->state, $aiColumn);
            $this->animatingPiece = [
                'column' => $aiColumn,
                'row' => $row,
                'player' => $this->state['currentPlayer']
            ];
            
            $this->state = Connect4Engine::dropPiece($this->state, $aiColumn);
            $this->dispatch('ai-piece-dropped');
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
    
    public function hoverColumn($column)
    {
        if (!$this->state['gameOver'] && Connect4Engine::canDropInColumn($this->state, $column)) {
            $this->hoveredColumn = $column;
        }
    }
    
    public function leaveColumn()
    {
        $this->hoveredColumn = null;
    }
    
    public function undo()
    {
        if (!empty($this->undoStack)) {
            $this->state = array_pop($this->undoStack);
            $this->animatingPiece = null;
        }
    }
    
    private function saveStateForUndo()
    {
        $this->undoStack[] = $this->state;
        
        // Limit undo stack
        if (count($this->undoStack) > 15) {
            array_shift($this->undoStack);
        }
    }
    
    public function canUndo()
    {
        return !empty($this->undoStack);
    }
    
    public function toggleHints()
    {
        $this->showHints = !$this->showHints;
    }
    
    public function getHints()
    {
        $game = new Connect4Game();
        return HintEngine::getHints($game, $this->state, [
            'difficulty' => 'beginner',
            'maxHints' => 3
        ]);
    }
    
    public function showBestMove()
    {
        $bestMove = Connect4Engine::getBestMove($this->state);
        if ($bestMove !== null) {
            $this->hoveredColumn = $bestMove;
            $this->dispatch('highlight-best-move', column: $bestMove);
        }
    }
    
    public function getStats()
    {
        return Connect4Engine::getStats($this->state);
    }
    
    public function clearAnimation()
    {
        $this->animatingPiece = null;
    }
}; ?>

<div>
    <x-game.accessibility>
        @if($state['gameOver'])
            @if($state['winner'] === 'draw')
                <div>Game ended in a draw!</div>
            @else
                <div>{{ ucfirst($state['winner']) }} wins the game!</div>
            @endif
        @else
            <div>{{ ucfirst($state['currentPlayer']) }}'s turn - choose a column to drop your piece</div>
        @endif
    </x-game.accessibility>

    <x-game.styles />
    <div class="connect4-game">
        <!-- Game Header -->
        <div class="game-header">
            <div class="game-status">
                @if(!$state['gameOver'])
                    <div class="player-indicator">
                        <div class="player-piece {{ $state['currentPlayer'] }}"></div>
                        {{ ucfirst($state['currentPlayer']) }}'s Turn
                    </div>
                @else
                    @if($state['winner'] === 'draw')
                        <div class="draw-indicator">Draw Game!</div>
                    @else
                        <div class="winner-indicator">
                            <div class="player-piece {{ $state['winner'] }}"></div>
                            {{ ucfirst($state['winner']) }} Wins!
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <!-- Game Settings -->
        <div class="game-settings">
            <div class="setting-group">
                <label>Mode:</label>
                <select wire:change="setGameMode($event.target.value)" class="setting-select">
                    <option value="pass_and_play" {{ $gameMode === 'pass_and_play' ? 'selected' : '' }}>Pass & Play</option>
                    <option value="vs_ai" {{ $gameMode === 'vs_ai' ? 'selected' : '' }}>vs AI</option>
                </select>
            </div>
            
            @if($gameMode === 'vs_ai')
                <div class="setting-group">
                    <label>AI Difficulty:</label>
                    <select wire:change="setDifficulty($event.target.value)" class="setting-select">
                        <option value="easy" {{ $difficulty === 'easy' ? 'selected' : '' }}>Easy</option>
                        <option value="medium" {{ $difficulty === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="hard" {{ $difficulty === 'hard' ? 'selected' : '' }}>Hard</option>
                        <option value="impossible" {{ $difficulty === 'impossible' ? 'selected' : '' }}>Impossible</option>
                    </select>
                </div>
            @endif
        </div>

        <!-- Game Board -->
        <div class="game-board-container">
            <div class="game-board">
                <!-- Column Headers for Dropping -->
                <div class="column-headers">
                    @for($col = 0; $col < 7; $col++)
                        <div class="column-header"
                             wire:click="dropPiece({{ $col }})"
                             wire:mouseenter="hoverColumn({{ $col }})"
                             wire:mouseleave="leaveColumn"
                             style="cursor: {{ Connect4Engine::canDropInColumn($state, $col) && !$state['gameOver'] ? 'pointer' : 'default' }}">
                            
                            <!-- Preview piece -->
                            @if($hoveredColumn === $col && !$state['gameOver'])
                                <div class="preview-piece {{ $state['currentPlayer'] }}"></div>
                            @endif
                        </div>
                    @endfor
                </div>

                <!-- Game Grid -->
                <div class="board-grid">
                    @for($row = 0; $row < 6; $row++)
                        @for($col = 0; $col < 7; $col++)
                            @php
                                $piece = $state['board'][$row][$col];
                                $isWinning = false;
                                
                                // Check if this cell is part of winning line
                                if ($state['winningLine']) {
                                    foreach ($state['winningLine'] as $winCell) {
                                        if ($winCell['row'] === $row && $winCell['col'] === $col) {
                                            $isWinning = true;
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            
                            <div class="board-cell {{ $isWinning ? 'winning' : '' }}"
                                 data-row="{{ $row }}" data-col="{{ $col }}">
                                
                                @if($piece)
                                    <div class="game-piece {{ $piece }} {{ $isWinning ? 'winning-piece' : '' }}">
                                        @if($isWinning)
                                            <div class="winning-glow"></div>
                                        @endif
                                    </div>
                                @endif
                                
                                <!-- Animating piece -->
                                @if($animatingPiece && $animatingPiece['column'] === $col && $animatingPiece['row'] === $row)
                                    <div class="game-piece {{ $animatingPiece['player'] }} animating"
                                         x-data="{ dropping: true }"
                                         x-init="setTimeout(() => { dropping = false; $wire.clearAnimation(); }, 500)">
                                    </div>
                                @endif
                            </div>
                        @endfor
                    @endfor
                </div>
            </div>
        </div>

        <!-- Game Info -->
        @php $stats = $this->getStats(); @endphp
        <div class="game-info">
            <div class="score-section">
                <div class="score-item red">
                    <div class="player-piece red"></div>
                    <span>Red: {{ $stats['score']['red'] }}</span>
                </div>
                <div class="score-item yellow">
                    <div class="player-piece yellow"></div>
                    <span>Yellow: {{ $stats['score']['yellow'] }}</span>
                </div>
            </div>
            
            <div class="stats-section">
                <div class="stat-item">
                    <span class="stat-label">Moves:</span>
                    <span class="stat-value">{{ $stats['moves'] }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Pieces:</span>
                    <span class="stat-value">{{ $stats['piecesPlayed'] }}/42</span>
                </div>
            </div>
        </div>

        <!-- Game Controls -->
        <div class="game-controls">
            <button wire:click="resetGame" class="game-button">
                New Game
            </button>
            <button wire:click="undo" 
                    class="game-button {{ $this->canUndo() ? 'primary' : '' }}" 
                    {{ $this->canUndo() ? '' : 'disabled' }}>
                Undo
            </button>
            <button wire:click="showBestMove" class="game-button">
                Best Move
            </button>
            <button wire:click="toggleHints" class="game-button {{ $showHints ? 'primary' : '' }}">
                Hints
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
                    @if($state['winner'] === 'draw')
                        <h2 class="win-title">🤝 It's a Draw!</h2>
                        <p class="win-details">Great game - the board is full!</p>
                    @else
                        <h2 class="win-title">🎉 {{ ucfirst($state['winner']) }} Wins! 🎉</h2>
                        <p class="win-details">Four in a row - well played!</p>
                        <div class="winning-piece-large {{ $state['winner'] }}"></div>
                    @endif
                    
                    <div class="win-stats">
                        <div>Game finished in {{ $stats['moves'] }} moves</div>
                        @if($state['winner'] !== 'draw')
                            <div>Final Score: Red {{ $stats['score']['red'] }} - {{ $stats['score']['yellow'] }} Yellow</div>
                        @endif
                    </div>
                    <button wire:click="resetGame" class="game-btn new-game-btn">Play Again</button>
                </div>
            </div>
        @endif
    </div>

    <style>
        /* Connect4-specific styles that complement the liminal base styles */
        .connect4-game {
            background: transparent;
        }

        .game-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .game-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            background: linear-gradient(45deg, #fbbf24, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .game-status {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .player-indicator, .winner-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: rgba(255,255,255,0.1);
            padding: 8px 16px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .draw-indicator {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .player-piece {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.3);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
        }

        .player-piece.red {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .player-piece.yellow {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
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
            backdrop-filter: blur(10px);
        }

        .setting-select {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 4px;
            color: white;
            padding: 4px 8px;
        }

        .game-board-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .game-board {
            background: linear-gradient(135deg, #1e40af, #3730a3);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .column-headers {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 10px;
        }

        .column-header {
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: background 0.2s ease;
        }

        .column-header:hover {
            background: rgba(255,255,255,0.1);
        }

        .preview-piece {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            opacity: 0.7;
            border: 2px solid rgba(255,255,255,0.3);
            animation: gentle-pulse 2s ease-in-out infinite;
        }

        .preview-piece.red {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .preview-piece.yellow {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
        }


        .board-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            grid-template-rows: repeat(6, 1fr);
            gap: 8px;
            background: #1e3a8a;
            padding: 15px;
            border-radius: 8px;
        }

        .board-cell {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: all 0.2s ease;
        }

        .board-cell.winning {
            background: rgba(34, 197, 94, 0.3);
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.6);
        }

        .game-piece {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.3);
            box-shadow: 
                inset 0 2px 4px rgba(255,255,255,0.3),
                0 4px 8px rgba(0,0,0,0.2);
            position: relative;
            transition: all 0.3s ease;
        }

        .game-piece.red {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .game-piece.yellow {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
        }

        .game-piece.winning-piece {
            animation: winPulse 1s ease-in-out infinite alternate;
        }

        .game-piece.animating {
            animation: dropPiece 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes dropPiece {
            0% { transform: translateY(-300px); }
            70% { transform: translateY(5px); }
            100% { transform: translateY(0); }
        }

        @keyframes winPulse {
            from { 
                transform: scale(1);
                box-shadow: 
                    inset 0 2px 4px rgba(255,255,255,0.3),
                    0 4px 8px rgba(0,0,0,0.2);
            }
            to { 
                transform: scale(1.1);
                box-shadow: 
                    inset 0 2px 4px rgba(255,255,255,0.5),
                    0 8px 16px rgba(34, 197, 94, 0.4);
            }
        }

        .winning-glow {
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 50%;
            background: linear-gradient(45deg, #22c55e, #16a34a);
            z-index: -1;
            animation: glow 1s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { opacity: 0.6; }
            to { opacity: 1; }
        }

        .game-info {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .score-section {
            display: flex;
            gap: 30px;
        }

        .score-item {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            padding: 12px 16px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
            font-weight: 600;
        }

        .stats-section {
            display: flex;
            gap: 20px;
        }

        .stat-item {
            background: rgba(255,255,255,0.1);
            padding: 12px 16px;
            border-radius: 8px;
            text-align: center;
            backdrop-filter: blur(10px);
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

        .winning-piece-large {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin: 0 auto 20px;
            border: 3px solid rgba(255,255,255,0.3);
            animation: victoryBounce 1s ease-in-out infinite alternate;
        }

        .winning-piece-large.red {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .winning-piece-large.yellow {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
        }

        @keyframes victoryBounce {
            from { transform: scale(1); }
            to { transform: scale(1.1); }
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
            .connect4-game {
                padding: 10px;
            }

            .game-board {
                padding: 15px;
            }

            .board-cell, .game-piece {
                width: 40px;
                height: 40px;
            }

            .game-piece {
                width: 34px;
                height: 34px;
            }

            .preview-piece {
                width: 30px;
                height: 30px;
            }

            .game-info {
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }

            .score-section, .stats-section {
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

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('piece-dropped', () => {
                // Add sound effect or additional animation here if needed
            });
            
            Livewire.on('ai-piece-dropped', () => {
                // AI move feedback
            });
            
            Livewire.on('highlight-best-move', (event) => {
                // Highlight the best move column
                const column = event.column;
                setTimeout(() => {
                    // Clear highlight after 2 seconds
                    @this.hoveredColumn = null;
                }, 2000);
            });
        });
    </script>
</div>
