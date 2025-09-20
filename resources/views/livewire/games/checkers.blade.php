<?php

use App\Games\Checkers\CheckersGame;
use App\Games\Checkers\CheckersEngine;
use App\Services\UserBestScoreService;
use App\Services\HintEngine;
use Livewire\Volt\Component;

new class extends Component
{
    public $state;
    public $selectedSquare = null;
    public $validMoves = [];
    public $gameMode = 'pass_and_play';
    public $difficulty = 'medium';
    public $showHints = false;
    public $undoStack = [];
    public $draggedPiece = null;
    public $highlightedSquares = [];
    
    public function mount()
    {
        $this->resetGame();
    }
    
    public function resetGame()
    {
        $this->state = CheckersEngine::initialState();
        $this->state['mode'] = $this->gameMode;
        $this->state['difficulty'] = $this->difficulty;
        $this->selectedSquare = null;
        $this->validMoves = [];
        $this->undoStack = [];
        $this->highlightedSquares = [];
    }
    
    public function selectSquare($row, $col)
    {
        // Skip if game is over
        if ($this->state['gameOver']) {
            return;
        }
        
        // Skip if AI is thinking
        if ($this->gameMode === 'vs_ai' && $this->state['currentPlayer'] === CheckersEngine::BLACK) {
            return;
        }
        
        $board = $this->state['board'];
        $piece = $board[$row][$col];
        
        // If clicking on empty square and have selected piece, try to move
        if (!$piece && $this->selectedSquare) {
            $this->attemptMove($this->selectedSquare, [$row, $col]);
            return;
        }
        
        // If clicking on piece of current player, select it
        if ($piece && CheckersEngine::getPieceOwner($piece) === $this->state['currentPlayer']) {
            $this->selectedSquare = [$row, $col];
            $this->updateValidMoves();
        } else {
            // Clear selection
            $this->selectedSquare = null;
            $this->validMoves = [];
        }
    }
    
    public function attemptMove($from, $to)
    {
        $move = [
            'from' => $from,
            'to' => $to
        ];
        
        if (!$this->validateAndApplyMove($move)) {
            // Invalid move, clear selection
            $this->selectedSquare = null;
            $this->validMoves = [];
        }
    }
    
    private function validateAndApplyMove($move): bool
    {
        $game = new CheckersGame();
        
        if (!$game->validateMove($this->state, $move)) {
            return false;
        }
        
        $this->saveStateForUndo();
        $this->state = $game->applyMove($this->state, $move);
        
        // Clear selection after successful move
        $this->selectedSquare = null;
        $this->validMoves = [];
        
        // Handle AI move if needed
        if (!$this->state['gameOver'] && 
            $this->gameMode === 'vs_ai' && 
            $this->state['currentPlayer'] === CheckersEngine::BLACK &&
            !$this->state['mustCapture']) { // Don't make AI move if player must continue capturing
            
            $this->makeAIMove();
        }
        
        return true;
    }
    
    private function makeAIMove()
    {
        $aiMove = CheckersEngine::calculateAIMove($this->state, $this->difficulty);
        
        if ($aiMove) {
            $this->saveStateForUndo();
            $this->state = CheckersEngine::applyMove($this->state, $aiMove);
            $this->dispatch('ai-move-made');
        }
    }
    
    private function updateValidMoves()
    {
        if (!$this->selectedSquare) {
            $this->validMoves = [];
            return;
        }
        
        [$row, $col] = $this->selectedSquare;
        $allValidMoves = CheckersEngine::getValidMoves($this->state);
        
        // Filter moves for the selected piece
        $this->validMoves = array_filter($allValidMoves, function($move) use ($row, $col) {
            return $move['from'][0] === $row && $move['from'][1] === $col;
        });
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
    
    public function undo()
    {
        if (!empty($this->undoStack)) {
            $this->state = array_pop($this->undoStack);
            $this->selectedSquare = null;
            $this->validMoves = [];
        }
    }
    
    private function saveStateForUndo()
    {
        $this->undoStack[] = json_decode(json_encode($this->state), true);
        
        // Limit undo stack
        if (count($this->undoStack) > 20) {
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
        $game = new CheckersGame();
        return HintEngine::getHints($game, $this->state, [
            'difficulty' => 'beginner',
            'maxHints' => 3
        ]);
    }
    
    public function showBestMove()
    {
        $bestMove = CheckersEngine::getBestMove($this->state);
        if ($bestMove) {
            $this->selectedSquare = $bestMove['from'];
            $this->updateValidMoves();
            $this->highlightedSquares = [$bestMove['to']];
            $this->dispatch('highlight-best-move');
        }
    }
    
    public function getStats()
    {
        return CheckersEngine::getStats($this->state);
    }
    
    public function getPieceAsset($piece)
    {
        if (!$piece) return null;
        
        return match($piece) {
            CheckersEngine::RED => '/images/Pieces%20(Red)/pieceRed_single00.png',
            CheckersEngine::BLACK => '/images/Pieces%20(Black)/pieceBlack_single00.png',
            CheckersEngine::RED_KING => '/images/Pieces%20(Red)/pieceRed_multi00.png',
            CheckersEngine::BLACK_KING => '/images/Pieces%20(Black)/pieceBlack_multi00.png',
            default => null
        };
    }
    
    public function isValidMoveTarget($row, $col)
    {
        foreach ($this->validMoves as $move) {
            if ($move['to'][0] === $row && $move['to'][1] === $col) {
                return true;
            }
        }
        return false;
    }
    
    public function isHighlighted($row, $col)
    {
        foreach ($this->highlightedSquares as $square) {
            if ($square[0] === $row && $square[1] === $col) {
                return true;
            }
        }
        return false;
    }
    
    // Drag and drop methods
    public function startDrag($row, $col)
    {
        if ($this->gameMode === 'vs_ai' && $this->state['currentPlayer'] === CheckersEngine::BLACK) {
            return;
        }
        
        $piece = $this->state['board'][$row][$col];
        if ($piece && CheckersEngine::getPieceOwner($piece) === $this->state['currentPlayer']) {
            $this->draggedPiece = [$row, $col];
            $this->selectedSquare = [$row, $col];
            $this->updateValidMoves();
        }
    }
    
    public function endDrag($row, $col)
    {
        if ($this->draggedPiece) {
            $this->attemptMove($this->draggedPiece, [$row, $col]);
            $this->draggedPiece = null;
        }
    }
    
    public function clearHighlights()
    {
        $this->highlightedSquares = [];
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
            <div>{{ ucfirst($state['currentPlayer']) }}'s turn</div>
            @if($state['mustCapture'])
                <div>Must continue capturing!</div>
            @endif
        @endif
    </x-game.accessibility>

    <div class="checkers-game">
        <!-- Game Header -->
        <div class="game-header">
            <h1 class="game-title">Checkers</h1>
            <div class="game-status">
                @if(!$state['gameOver'])
                    <div class="current-player">
                        <div class="player-indicator {{ $state['currentPlayer'] }}">
                            <img src="{{ $this->getPieceAsset($state['currentPlayer']) }}" 
                                 alt="{{ ucfirst($state['currentPlayer']) }}"
                                 class="player-piece-icon">
                            {{ ucfirst($state['currentPlayer']) }}'s Turn
                            @if($state['mustCapture'])
                                <span class="capture-indicator">- Must Capture!</span>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="game-result">
                        @if($state['winner'] === 'draw')
                            <span class="draw-indicator">🤝 Draw Game!</span>
                        @else
                            <span class="winner-indicator {{ $state['winner'] }}">
                                <img src="{{ $this->getPieceAsset($state['winner']) }}" 
                                     alt="{{ ucfirst($state['winner']) }}"
                                     class="player-piece-icon">
                                {{ ucfirst($state['winner']) }} Wins! 🎉
                            </span>
                        @endif
                    </div>
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
                @for($row = 0; $row < 8; $row++)
                    @for($col = 0; $col < 8; $col++)
                        @php
                            $isDarkSquare = CheckersEngine::isDarkSquare($row, $col);
                            $piece = $state['board'][$row][$col];
                            $isSelected = $selectedSquare && $selectedSquare[0] === $row && $selectedSquare[1] === $col;
                            $isValidTarget = $this->isValidMoveTarget($row, $col);
                            $isHighlighted = $this->isHighlighted($row, $col);
                        @endphp
                        
                        <div class="board-square {{ $isDarkSquare ? 'dark' : 'light' }} 
                                    {{ $isSelected ? 'selected' : '' }}
                                    {{ $isValidTarget ? 'valid-target' : '' }}
                                    {{ $isHighlighted ? 'highlighted' : '' }}"
                             wire:click="selectSquare({{ $row }}, {{ $col }})"
                             ondragover="event.preventDefault()"
                             ondrop="@this.endDrag({{ $row }}, {{ $col }})"
                             data-row="{{ $row }}" 
                             data-col="{{ $col }}">
                            
                            @if($piece)
                                <div class="game-piece {{ $piece }} {{ $isSelected ? 'selected' : '' }}"
                                     draggable="true"
                                     ondragstart="@this.startDrag({{ $row }}, {{ $col }})"
                                     ondragend="@this.clearHighlights()">
                                    <img src="{{ $this->getPieceAsset($piece) }}" 
                                         alt="{{ $piece }}"
                                         class="piece-image">
                                    @if(CheckersEngine::isKing($piece))
                                        <div class="king-crown">♔</div>
                                    @endif
                                </div>
                            @endif
                            
                            @if($isValidTarget)
                                <div class="move-indicator"></div>
                            @endif
                        </div>
                    @endfor
                @endfor
            </div>
        </div>

        <!-- Game Info -->
        @php $stats = $this->getStats(); @endphp
        <div class="game-info">
            <div class="piece-counts">
                <div class="count-item red">
                    <img src="{{ $this->getPieceAsset(CheckersEngine::RED) }}" alt="Red" class="count-icon">
                    <span>{{ $stats['pieceCounts']['red'] + $stats['pieceCounts']['red_king'] }}</span>
                </div>
                <div class="count-item black">
                    <img src="{{ $this->getPieceAsset(CheckersEngine::BLACK) }}" alt="Black" class="count-icon">
                    <span>{{ $stats['pieceCounts']['black'] + $stats['pieceCounts']['black_king'] }}</span>
                </div>
            </div>
            
            <div class="kings-count">
                <div class="king-item red">
                    <img src="{{ $this->getPieceAsset(CheckersEngine::RED_KING) }}" alt="Red King" class="count-icon">
                    <span>{{ $stats['pieceCounts']['red_king'] }}</span>
                </div>
                <div class="king-item black">
                    <img src="{{ $this->getPieceAsset(CheckersEngine::BLACK_KING) }}" alt="Black King" class="count-icon">
                    <span>{{ $stats['pieceCounts']['black_king'] }}</span>
                </div>
            </div>
            
            <div class="stats-section">
                <div class="stat-item">
                    <span class="stat-label">Moves:</span>
                    <span class="stat-value">{{ $stats['moves'] }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Score:</span>
                    <span class="stat-value">{{ $stats['score']['red'] }}-{{ $stats['score']['black'] }}</span>
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
            <button wire:click="showBestMove" class="game-btn hint-move-btn">
                🎯 Best Move
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
                    @if($state['winner'] === 'draw')
                        <h2 class="win-title">🤝 It's a Draw!</h2>
                        <p class="win-details">Great game!</p>
                    @else
                        <h2 class="win-title">🏆 {{ ucfirst($state['winner']) }} Wins! 🏆</h2>
                        <p class="win-details">Excellent strategy!</p>
                        <img src="{{ $this->getPieceAsset($state['winner']) }}" 
                             alt="Winner" class="winning-piece-large">
                    @endif
                    
                    <div class="win-stats">
                        <div>Game finished in {{ $stats['moves'] }} moves</div>
                        <div>Final Score: Red {{ $stats['score']['red'] }} - {{ $stats['score']['black'] }} Black</div>
                        <div>Pieces: Red {{ $stats['pieceCounts']['red'] + $stats['pieceCounts']['red_king'] }} - {{ $stats['pieceCounts']['black'] + $stats['pieceCounts']['black_king'] }} Black</div>
                    </div>
                    <button wire:click="resetGame" class="game-btn new-game-btn">Play Again</button>
                </div>
            </div>
        @endif
    </div>

    <style>
        .checkers-game {
            min-height: 100vh;
            background: linear-gradient(135deg, #8b4513 0%, #a0522d 50%, #654321 100%);
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

        .player-piece-icon, .count-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
        }

        .capture-indicator {
            color: #ef4444;
            font-weight: bold;
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
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            grid-template-rows: repeat(8, 1fr);
            gap: 2px;
            background: #3c2414;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            max-width: 480px;
            width: 100%;
            aspect-ratio: 1;
        }

        .board-square {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .board-square.light {
            background: #f5deb3;
        }

        .board-square.dark {
            background: #8b4513;
        }

        .board-square.selected {
            background: rgba(34, 197, 94, 0.4) !important;
            box-shadow: inset 0 0 0 3px #22c55e;
        }

        .board-square.valid-target {
            background: rgba(59, 130, 246, 0.4) !important;
        }

        .board-square.highlighted {
            background: rgba(251, 191, 36, 0.4) !important;
            box-shadow: inset 0 0 0 3px #fbbf24;
        }

        .board-square:hover {
            background: rgba(255,255,255,0.1) !important;
        }

        .game-piece {
            position: relative;
            cursor: grab;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .game-piece:active {
            cursor: grabbing;
        }

        .game-piece.selected {
            transform: scale(1.1);
            filter: drop-shadow(0 0 10px rgba(34, 197, 94, 0.8));
        }

        .piece-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .king-crown {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 12px;
            color: #fbbf24;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        .move-indicator {
            width: 20px;
            height: 20px;
            background: rgba(59, 130, 246, 0.7);
            border-radius: 50%;
            border: 2px solid white;
            animation: pulse 1s ease-in-out infinite alternate;
        }

        @keyframes pulse {
            from { transform: scale(1); opacity: 0.7; }
            to { transform: scale(1.2); opacity: 1; }
        }

        .game-info {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .piece-counts, .kings-count {
            display: flex;
            gap: 20px;
            background: rgba(255,255,255,0.1);
            padding: 12px 16px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }

        .count-item, .king-item {
            display: flex;
            align-items: center;
            gap: 8px;
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
            animation: victoryBounce 1s ease-in-out infinite alternate;
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
            .checkers-game {
                padding: 10px;
            }

            .game-board {
                padding: 15px;
                max-width: 100%;
            }

            .piece-image {
                width: 32px;
                height: 32px;
            }

            .player-piece-icon, .count-icon {
                width: 20px;
                height: 20px;
            }

            .game-info {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }

            .piece-counts, .kings-count {
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
            Livewire.on('ai-move-made', () => {
                // Add sound effect or additional animation here if needed
            });
            
            Livewire.on('highlight-best-move', () => {
                // Best move highlighted - could add additional effects
                setTimeout(() => {
                    @this.clearHighlights();
                }, 3000);
            });
        });
    </script>
</div>
