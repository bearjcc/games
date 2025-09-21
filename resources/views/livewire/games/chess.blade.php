<?php

use App\Games\Chess\ChessGame;
use App\Games\Chess\ChessEngine;
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
    public $lastMove = null;
    public $promotionSquare = null;
    public $promotionOptions = ['queen', 'rook', 'bishop', 'knight'];
    public $moveHistory = [];
    
    public function mount()
    {
        $this->resetGame();
    }
    
    public function resetGame()
    {
        $this->state = ChessEngine::initialState();
        $this->state['mode'] = $this->gameMode;
        $this->state['difficulty'] = $this->difficulty;
        $this->selectedSquare = null;
        $this->validMoves = [];
        $this->undoStack = [];
        $this->highlightedSquares = [];
        $this->lastMove = null;
        $this->promotionSquare = null;
        $this->moveHistory = [];
    }
    
    public function selectSquare($row, $col)
    {
        // Skip if game is over
        if ($this->state['gameOver']) {
            return;
        }
        
        // Skip if AI is thinking
        if ($this->gameMode === 'vs_ai' && $this->state['currentPlayer'] === ChessEngine::BLACK) {
            return;
        }
        
        // Skip if promotion dialog is open
        if ($this->promotionSquare) {
            return;
        }
        
        $board = $this->state['board'];
        $piece = $board[$row][$col];
        
        // If clicking on empty square and have selected piece, try to move
        if (!$piece && $this->selectedSquare) {
            $this->attemptMove($this->selectedSquare, [$row, $col]);
            return;
        }
        
        // If clicking on opponent piece and have selected piece, try to capture
        if ($piece && $this->selectedSquare && 
            ChessEngine::getPieceColor($piece) !== $this->state['currentPlayer']) {
            $this->attemptMove($this->selectedSquare, [$row, $col]);
            return;
        }
        
        // If clicking on own piece, select it
        if ($piece && ChessEngine::getPieceColor($piece) === $this->state['currentPlayer']) {
            $this->selectedSquare = [$row, $col];
            $this->validMoves = $this->getValidMovesForPiece($row, $col);
            return;
        }
        
        // Otherwise, deselect
        $this->selectedSquare = null;
        $this->validMoves = [];
    }
    
    private function attemptMove($from, $to)
    {
        // Save state for undo
        $this->saveStateForUndo();
        
        $board = $this->state['board'];
        [$fromRow, $fromCol] = $from;
        
        $move = [
            'from' => $from,
            'to' => $to,
            'piece' => $board[$fromRow][$fromCol],
        ];
        
        $game = new ChessGame();
        if ($game->validateMove($this->state, $move)) {
            // Check for pawn promotion
            [$toRow, $toCol] = $to;
            $piece = $board[$fromRow][$fromCol];
            
            if (ChessEngine::getPieceType($piece) === 'pawn') {
                $promotionRow = ChessEngine::getPieceColor($piece) === ChessEngine::WHITE ? 0 : 7;
                if ($toRow === $promotionRow) {
                    // Show promotion dialog
                    $this->promotionSquare = ['from' => $from, 'to' => $to];
                    return;
                }
            }
            
            $this->applyMove($move);
        }
        
        $this->selectedSquare = null;
        $this->validMoves = [];
    }
    
    public function promote($pieceType)
    {
        if (!$this->promotionSquare) {
            return;
        }
        
        $move = $this->promotionSquare;
        $move['promotion'] = $pieceType;
        
        $this->applyMove($move);
        $this->promotionSquare = null;
    }
    
    private function applyMove($move)
    {
        $game = new ChessGame();
        $this->state = $game->applyMove($this->state, $move);
        $this->lastMove = $move;
        
        // Update move history for display
        $this->updateMoveHistory($move);
        
        // Clear highlights
        $this->highlightedSquares = [];
        
        // If AI mode and now AI's turn, make AI move
        if ($this->gameMode === 'vs_ai' && $this->state['currentPlayer'] === ChessEngine::BLACK && !$this->state['gameOver']) {
            $this->dispatch('ai-move-made');
            $this->makeAiMove();
        }
    }
    
    private function updateMoveHistory($move)
    {
        $game = new ChessGame();
        $notation = $this->moveToAlgebraic($move);
        
        $this->moveHistory[] = [
            'notation' => $notation,
            'player' => $this->state['currentPlayer'] === ChessEngine::WHITE ? ChessEngine::BLACK : ChessEngine::WHITE,
            'move' => $move,
        ];
    }
    
    private function moveToAlgebraic($move)
    {
        $game = new ChessGame();
        [$fromRow, $fromCol] = $move['from'];
        [$toRow, $toCol] = $move['to'];
        
        $piece = $move['piece'] ?? $this->state['board'][$fromRow][$fromCol];
        if (!$piece) {
            return '??'; // Unknown move notation
        }
        $pieceType = ChessEngine::getPieceType($piece);
        $captured = isset($move['captured']);
        
        // Special moves
        if (isset($move['type'])) {
            if ($move['type'] === 'castle_kingside') {
                return 'O-O';
            } elseif ($move['type'] === 'castle_queenside') {
                return 'O-O-O';
            }
        }
        
        $notation = '';
        
        // Piece prefix (except for pawns)
        if ($pieceType !== 'pawn') {
            $notation .= strtoupper(substr($pieceType, 0, 1));
            if ($pieceType === 'knight') {
                $notation = 'N'; // Knight is N, not K
            }
        }
        
        // For pawn captures, include the file
        if ($pieceType === 'pawn' && $captured) {
            $notation .= chr(ord('a') + $fromCol);
        }
        
        // Capture indicator
        if ($captured) {
            $notation .= 'x';
        }
        
        // Destination square
        $notation .= $game->coordsToAlgebraic($toRow, $toCol);
        
        // Promotion
        if (isset($move['promotion'])) {
            $notation .= '=' . strtoupper(substr($move['promotion'], 0, 1));
            if ($move['promotion'] === 'knight') {
                $notation .= 'N';
            }
        }
        
        // Check/checkmate indicators (would need game state after move)
        // This is simplified - proper PGN would check resulting position
        
        return $notation;
    }
    
    public function makeAiMove()
    {
        $game = new ChessGame();
        $aiMove = $game->getAiMove($this->state, $this->difficulty);
        
        if ($aiMove) {
            $this->saveStateForUndo();
            $this->applyMove($aiMove);
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
        $this->state['difficulty'] = $difficulty;
    }
    
    public function undo()
    {
        if (!empty($this->undoStack)) {
            $this->state = array_pop($this->undoStack);
            $this->selectedSquare = null;
            $this->validMoves = [];
            $this->highlightedSquares = [];
            $this->lastMove = null;
            $this->promotionSquare = null;
            
            // Remove last move from history
            if (!empty($this->moveHistory)) {
                array_pop($this->moveHistory);
            }
        }
    }
    
    public function canUndo()
    {
        return !empty($this->undoStack);
    }
    
    private function saveStateForUndo()
    {
        $this->undoStack[] = $this->state;
        
        // Keep only last 20 moves
        if (count($this->undoStack) > 20) {
            array_shift($this->undoStack);
        }
    }
    
    public function showBestMove()
    {
        $game = new ChessGame();
        $bestMove = $game->getAiMove($this->state, 'impossible');
        
        if ($bestMove) {
            $this->highlightedSquares = [$bestMove['from'], $bestMove['to']];
            $this->dispatch('highlight-best-move');
        }
    }
    
    public function clearHighlights()
    {
        $this->highlightedSquares = [];
    }
    
    public function toggleHints()
    {
        $this->showHints = !$this->showHints;
    }
    
    public function getValidMovesForPiece($row, $col)
    {
        $game = new ChessGame();
        $pieceMoves = $game->getPieceMoves($this->state, $row, $col);
        
        // Filter to only valid destinations
        $validSquares = [];
        foreach ($pieceMoves as $move) {
            if ($game->validateMove($this->state, $move)) {
                $validSquares[] = $move['to'];
            }
        }
        
        return $validSquares;
    }
    
    public function getPieceSymbol($piece)
    {
        if (!$piece) return '';
        return ChessEngine::getPieceSymbol($piece);
    }
    
    public function isSquareHighlighted($row, $col)
    {
        foreach ($this->highlightedSquares as $square) {
            if ($square[0] === $row && $square[1] === $col) {
                return true;
            }
        }
        return false;
    }
    
    public function isValidTarget($row, $col)
    {
        foreach ($this->validMoves as $square) {
            if ($square[0] === $row && $square[1] === $col) {
                return true;
            }
        }
        return false;
    }
    
    public function isLastMove($row, $col)
    {
        if (!$this->lastMove) return false;
        
        return ($this->lastMove['from'][0] === $row && $this->lastMove['from'][1] === $col) ||
               ($this->lastMove['to'][0] === $row && $this->lastMove['to'][1] === $col);
    }
    
    public function startDrag($row, $col)
    {
        $this->draggedPiece = [$row, $col];
        $this->selectSquare($row, $col);
    }
    
    public function endDrag($row, $col)
    {
        if ($this->draggedPiece) {
            $this->attemptMove($this->draggedPiece, [$row, $col]);
            $this->draggedPiece = null;
            $this->selectedSquare = null;
            $this->validMoves = [];
        }
    }
    
    public function getStats()
    {
        $game = new ChessGame();
        return $game->getStats($this->state);
    }
    
    public function getHints()
    {
        if (!$this->showHints) {
            return [];
        }
        
        $game = new ChessGame();
        return HintEngine::getHints($game, $this->state, [
            'maxHints' => 3,
            'includeStrategic' => true,
            'includeTactical' => true,
        ]);
    }
    
    public function exportPGN()
    {
        $pgn = $this->generatePGN();
        $this->dispatch('download-pgn', $pgn);
    }
    
    private function generatePGN()
    {
        $headers = [
            '[Event "Chess Game"]',
            '[Site "Laravel Games"]',
            '[Date "' . date('Y.m.d') . '"]',
            '[Round "1"]',
            '[White "White"]',
            '[Black "Black"]',
            '[Result "*"]'
        ];
        
        $moveText = '';
        foreach ($this->moveHistory as $index => $move) {
            if ($index % 2 === 0) {
                $moveNumber = floor($index / 2) + 1;
                $moveText .= "{$moveNumber}. ";
            }
            $moveText .= $move['notation'] . ' ';
        }
        
        // Add result
        if ($this->state['gameOver']) {
            if ($this->state['checkmate']) {
                $result = $this->state['winner'] === 'white' ? '1-0' : '0-1';
            } else {
                $result = '1/2-1/2';
            }
            $moveText .= $result;
        } else {
            $moveText .= '*';
        }
        
        return implode("\n", $headers) . "\n\n" . trim($moveText);
    }
    
    public function saveGame()
    {
        $gameData = [
            'state' => $this->state,
            'moveHistory' => $this->moveHistory,
            'gameMode' => $this->gameMode,
            'difficulty' => $this->difficulty,
            'timestamp' => now()->toISOString(),
        ];
        
        $this->dispatch('save-game', json_encode($gameData));
    }
    
    public function loadGame($gameData)
    {
        $data = json_decode($gameData, true);
        
        if ($data) {
            $this->state = $data['state'];
            $this->moveHistory = $data['moveHistory'] ?? [];
            $this->gameMode = $data['gameMode'] ?? 'pass_and_play';
            $this->difficulty = $data['difficulty'] ?? 'medium';
            $this->selectedSquare = null;
            $this->validMoves = [];
            $this->promotionSquare = null;
        }
    }
}; ?>

<div class="chess-game">
    <x-game.styles />
    <x-game.layout title="Chess">
        <!-- Game Header -->
        <div class="game-header">
            <div class="game-status">
                @if($state['gameOver'])
                    @if($state['checkmate'])
                        <div class="winner-indicator">
                            <span class="chess-piece">{{ $this->getPieceSymbol($state['winner'] === 'white' ? ChessEngine::WHITE_KING : ChessEngine::BLACK_KING) }}</span>
                            Checkmate! {{ ucfirst($state['winner']) }} Wins
                        </div>
                    @elseif($state['stalemate'])
                        <div class="draw-indicator">
                            Stalemate - Draw
                        </div>
                    @else
                        <div class="draw-indicator">
                            Draw by 50-move rule
                        </div>
                    @endif
                @else
                    <div class="player-indicator">
                        <span class="chess-piece">{{ $this->getPieceSymbol($state['currentPlayer'] === 'white' ? ChessEngine::WHITE_KING : ChessEngine::BLACK_KING) }}</span>
                        {{ ucfirst($state['currentPlayer']) }}'s Turn
                        @if($state['check'])
                            <span class="check-indicator">Check</span>
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
                        <option value="impossible" {{ $difficulty === 'impossible' ? 'selected' : '' }}>Grandmaster</option>
                    </select>
                </div>
            @endif
        </div>

        <!-- Main Game Area -->
        <div class="game-layout">
            <!-- Chess Board -->
            <div class="game-board-container">
                <div class="chess-board">
                    <!-- Rank Labels -->
                    <div class="rank-labels">
                        @for($rank = 8; $rank >= 1; $rank--)
                            <div class="rank-label">{{ $rank }}</div>
                        @endfor
                    </div>
                    
                    <!-- Board Squares -->
                    <div class="board-grid">
                        @for($row = 0; $row < 8; $row++)
                            @for($col = 0; $col < 8; $col++)
                                <div class="board-square {{ ($row + $col) % 2 === 0 ? 'light' : 'dark' }}
                                            {{ $selectedSquare && $selectedSquare[0] === $row && $selectedSquare[1] === $col ? 'selected' : '' }}
                                            {{ $this->isValidTarget($row, $col) ? 'valid-target' : '' }}
                                            {{ $this->isSquareHighlighted($row, $col) ? 'highlighted' : '' }}
                                            {{ $this->isLastMove($row, $col) ? 'last-move' : '' }}
                                            {{ $state['check'] && $state['board'][$row][$col] && 
                                               ChessEngine::getPieceType($state['board'][$row][$col]) === 'king' && 
                                               ChessEngine::getPieceColor($state['board'][$row][$col]) === $state['currentPlayer'] ? 'in-check' : '' }}"
                                     wire:click="selectSquare({{ $row }}, {{ $col }})"
                                     ondragover="event.preventDefault()"
                                     ondrop="window.Livewire.find('{{ $this->getId() }}').endDrag({{ $row }}, {{ $col }})"
                                     data-row="{{ $row }}" 
                                     data-col="{{ $col }}">
                                    
                                    @if($state['board'][$row][$col])
                                        <div class="chess-piece {{ $selectedSquare && $selectedSquare[0] === $row && $selectedSquare[1] === $col ? 'selected' : '' }}"
                                             draggable="true"
                                             @mousedown="startDrag({{ $row }}, {{ $col }})"
                                             ondragstart="window.Livewire.find('{{ $this->getId() }}').startDrag({{ $row }}, {{ $col }})"
                                             ondragend="window.Livewire.find('{{ $this->getId() }}').clearHighlights()">
                                            {{ $this->getPieceSymbol($state['board'][$row][$col]) }}
                                        </div>
                                    @endif
                                    
                                    @if($this->isValidTarget($row, $col))
                                        <div class="move-indicator"></div>
                                    @endif
                                </div>
                            @endfor
                        @endfor
                    </div>
                    
                    <!-- File Labels -->
                    <div class="file-labels">
                        @foreach(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'] as $file)
                            <div class="file-label">{{ $file }}</div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Game Info Sidebar -->
            <div class="game-sidebar">
                <!-- Captured Pieces -->
                <div class="captured-pieces">
                    <div class="captured-section">
                        <h3>Captured by White</h3>
                        <div class="captured-list">
                            @foreach($state['capturedPieces']['black'] as $piece)
                                <span class="captured-piece">{{ $this->getPieceSymbol($piece) }}</span>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="captured-section">
                        <h3>Captured by Black</h3>
                        <div class="captured-list">
                            @foreach($state['capturedPieces']['white'] as $piece)
                                <span class="captured-piece">{{ $this->getPieceSymbol($piece) }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Move History -->
                <div class="move-history">
                    <h3>Move History</h3>
                    <div class="moves-list">
                        @foreach($moveHistory as $index => $move)
                            <div class="move-entry">
                                <span class="move-number">{{ floor($index / 2) + 1 }}{{ $index % 2 === 0 ? '.' : '...' }}</span>
                                <span class="move-notation">{{ $move['notation'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Game Stats -->
                <div class="game-stats">
                    <div class="stat-item">
                        <span class="stat-label">Moves:</span>
                        <span class="stat-value">{{ $state['moves'] }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Material:</span>
                        <span class="stat-value">{{ $this->getStats()['materialScore'] }}</span>
                    </div>
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
            <button wire:click="exportPGN" class="game-button">
                Export PGN
            </button>
            <button wire:click="saveGame" class="game-button">
                Save Game
            </button>
            <label for="load-game" class="game-button">
                Load Game
                <input type="file" id="load-game" accept=".json" style="display: none;" 
                       onchange="handleGameLoad(this, window.Livewire.find('{{ $this->getId() }}'))">
            </label>
        </div>

        <!-- Hint Panel -->
        @if($showHints)
            <div class="hint-panel">
                <h4>Hints:</h4>
                <p>Hint system not yet implemented</p>
            </div>
        @endif

        <!-- Promotion Dialog -->
        @if($promotionSquare)
            <div class="promotion-overlay">
                <div class="promotion-dialog">
                    <h3>Choose Promotion Piece</h3>
                    <div class="promotion-options">
                        @foreach($promotionOptions as $pieceType)
                            <button wire:click="promote('{{ $pieceType }}')" class="promotion-btn">
                                {{ $this->getPieceSymbol($state['currentPlayer'] === 'white' ? 'white_' . $pieceType : 'black_' . $pieceType) }}
                                <span>{{ ucfirst($pieceType) }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </x-game-layout>

    <style>
        /* Chess-specific styles that complement the liminal base styles */
        .chess-game {
            background: transparent;
        }

        /* Chess board styling - clean and liminal */
        .chess-board {
            position: relative;
            background: rgb(248 250 252);
            border-radius: 0.75rem;
            padding: 2rem;
            border: 1px solid rgb(203 213 225);
        }

        .dark .chess-board {
            background: rgb(30 41 59);
            border-color: rgb(71 85 105);
        }

        /* Board layout and labels */
        .rank-labels {
            position: absolute;
            left: 0.5rem;
            top: 2rem;
            height: 30rem;
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            align-items: center;
        }

        .file-labels {
            display: flex;
            justify-content: space-around;
            margin-top: 0.5rem;
            height: 1.25rem;
            align-items: center;
        }

        .rank-label, .file-label {
            font-weight: 500;
            color: rgb(100 116 139);
            font-size: 0.75rem;
        }

        .dark .rank-label, .dark .file-label {
            color: rgb(148 163 184);
        }

        .board-grid {
            display: grid;
            grid-template-columns: repeat(8, 3.75rem);
            grid-template-rows: repeat(8, 3.75rem);
            gap: 0;
            border-radius: 0.375rem;
            overflow: hidden;
        }

        .board-square {
            width: 3.75rem;
            height: 3.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .board-square.light {
            background: rgb(254 252 232);
        }

        .board-square.dark {
            background: rgb(180 157 124);
        }

        .dark .board-square.light {
            background: rgb(71 85 105);
        }

        .dark .board-square.dark {
            background: rgb(51 65 85);
        }

        .board-square.selected {
            background: rgb(34 197 94 / 0.3) !important;
            box-shadow: inset 0 0 0 2px rgb(34 197 94);
        }

        .board-square.valid-target {
            background: rgb(59 130 246 / 0.2) !important;
        }

        .board-square.highlighted {
            background: rgb(251 191 36 / 0.3) !important;
            box-shadow: inset 0 0 0 2px rgb(251 191 36);
        }

        .board-square.last-move {
            background: rgb(168 85 247 / 0.2) !important;
            box-shadow: inset 0 0 0 1px rgb(168 85 247);
        }

        .board-square.in-check {
            background: rgb(239 68 68 / 0.3) !important;
            box-shadow: inset 0 0 0 2px rgb(239 68 68);
        }

        .board-square:hover {
            background: rgb(100 116 139 / 0.1) !important;
        }

        .chess-piece {
            font-size: 2.5rem;
            cursor: grab;
            transition: transform 0.2s;
            z-index: 10;
            line-height: 1;
        }

        .chess-piece:active {
            cursor: grabbing;
        }

        .chess-piece.selected {
            transform: scale(1.05);
        }

        /* Move indicator */
        .move-indicator {
            width: 1rem;
            height: 1rem;
            background: rgb(59 130 246);
            border-radius: 50%;
            border: 2px solid rgb(255 255 255);
            position: absolute;
            opacity: 0.8;
        }

        .dark .move-indicator {
            border-color: rgb(30 41 59);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .chess-board {
                padding: 1rem;
            }
            
            .board-grid {
                grid-template-columns: repeat(8, 3rem);
                grid-template-rows: repeat(8, 3rem);
            }
            
            .board-square {
                width: 3rem;
                height: 3rem;
            }
            
            .chess-piece {
                font-size: 2rem;
            }
        }
    </style>

    <script>
        document.addEventListener('livewire:init', () => {
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .captured-list {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .captured-piece {
            font-size: 20px;
            opacity: 0.8;
        }

        .moves-list {
            max-height: 200px;
            overflow-y: auto;
        }

        .move-entry {
            display: flex;
            gap: 8px;
            margin-bottom: 4px;
            font-size: 0.9rem;
        }

        .move-number {
            color: #ffd700;
            font-weight: bold;
            min-width: 30px;
        }

        .game-stats {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-label {
            color: #ffd700;
            font-weight: 600;
        }

        .stat-value {
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
        
        .pgn-btn {
            background: #10b981;
            color: white;
        }
        
        .pgn-btn:hover {
            background: #059669;
        }
        
        .save-btn {
            background: #8b5cf6;
            color: white;
        }
        
        .save-btn:hover {
            background: #7c3aed;
        }
        
        .load-btn {
            background: #06b6d4;
            color: white;
            cursor: pointer;
            display: inline-block;
        }
        
        .load-btn:hover {
            background: #0891b2;
        }

        .game-btn.disabled {
            background: #6b7280;
            color: #9ca3af;
            cursor: not-allowed;
            opacity: 0.5;
        }

        .promotion-overlay {
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

        .promotion-dialog {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            color: #1f2937;
            max-width: 400px;
        }

        .promotion-dialog h3 {
            margin: 0 0 20px 0;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .promotion-options {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .promotion-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.3);
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 15px 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 2rem;
        }

        .promotion-btn:hover {
            background: rgba(255,255,255,0.5);
            border-color: #1f2937;
            transform: scale(1.05);
        }

        .promotion-btn span {
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .chess-game {
                padding: 10px;
            }

            .game-layout {
                flex-direction: column;
                align-items: center;
            }

            .board-grid {
                grid-template-columns: repeat(8, 40px);
                grid-template-rows: repeat(8, 40px);
            }

            .board-square {
                width: 40px;
                height: 40px;
            }

            .chess-piece {
                font-size: 28px;
            }

            .chess-board {
                padding: 20px;
            }

            .game-sidebar {
                max-width: 100%;
                width: 100%;
            }

            .promotion-options {
                flex-wrap: wrap;
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
                    window.Livewire.find('{{ $this->getId() }}').clearHighlights();
                }, 3000);
            });

            // Handle PGN download
            Livewire.on('download-pgn', (pgn) => {
                const blob = new Blob([pgn], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'chess-game-' + new Date().toISOString().split('T')[0] + '.pgn';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });

            // Handle game save
            Livewire.on('save-game', (gameData) => {
                const blob = new Blob([gameData], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'chess-game-' + new Date().toISOString().split('T')[0] + '.json';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });
        });

        // Handle game load
        function handleGameLoad(input, component) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        component.loadGame(e.target.result);
                    } catch (error) {
                        alert('Error loading game file: ' + error.message);
                    }
                };
                reader.readAsText(file);
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Only handle if not in an input field
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }
            
            const chessComponent = document.querySelector('[wire\\:id]');
            if (!chessComponent) return;
            
            const component = window.Livewire.find(chessComponent.getAttribute('wire:id'));
            
            switch(e.key.toLowerCase()) {
                case 'u':
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        component.call('undo');
                    }
                    break;
                case 'n':
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        component.call('resetGame');
                    }
                    break;
                case 'h':
                    e.preventDefault();
                    component.call('toggleHints');
                    break;
                case 's':
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        component.call('saveGame');
                    }
                    break;
                case 'e':
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        component.call('exportPGN');
                    }
                    break;
            }
        });
    </script>
</div>
