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
    <x-game.styles />
    <x-game.animations />
    
    <x-game.layout title="Checkers">
        <!-- Game Header -->
        <div class="game-header">
            <div class="game-status">
                @if($state['gameOver'])
                    @if($state['winner'] === 'draw')
                        <div class="draw-indicator">Game ended in a draw</div>
                    @else
                        <div class="winner-indicator">
                            {{ ucfirst($state['winner']) }} wins the game!
                        </div>
                    @endif
                @else
                    <div class="player-indicator">
                        {{ ucfirst($state['currentPlayer']) }}'s turn
                        @if($state['mustCapture'])
                            <span class="capture-required">- Must capture!</span>
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
                    <option value="pass_and_play" {{ $gameMode === 'pass_and_play' ? 'selected' : '' }}>Pass & Play</option>
                    <option value="vs_ai" {{ $gameMode === 'vs_ai' ? 'selected' : '' }}>vs AI</option>
                </select>
            </div>
            
            @if($gameMode === 'vs_ai')
                <div class="setting-row">
                    <label class="setting-label">Difficulty:</label>
                    <select wire:change="setDifficulty($event.target.value)" class="game-select">
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
            <div class="checkers-board">
                @for($row = 0; $row < 8; $row++)
                    @for($col = 0; $col < 8; $col++)
                        @php
                            $isDarkSquare = CheckersEngine::isDarkSquare($row, $col);
                            $piece = $state['board'][$row][$col];
                            $isSelected = $selectedSquare && $selectedSquare[0] === $row && $selectedSquare[1] === $col;
                            $isValidTarget = $this->isValidMoveTarget($row, $col);
                            $isHighlighted = $this->isHighlighted($row, $col);
                            $pieceOwner = $piece ? CheckersEngine::getPieceOwner($piece) : null;
                            $isKing = $piece ? CheckersEngine::isKing($piece) : false;
                        @endphp
                        
                        <div class="board-square {{ $isDarkSquare ? 'dark-square' : 'light-square' }}" 
                             wire:click="selectSquare({{ $row }}, {{ $col }})"
                             ondragover="event.preventDefault()"
                             ondrop="@this.endDrag({{ $row }}, {{ $col }})"
                             data-position="{{ $row }}-{{ $col }}">
                            
                            @if($piece && $isDarkSquare)
                                <x-game.piece
                                    type="image"
                                    :piece="$piece"
                                    :player="$pieceOwner"
                                    :position="$row . '-' . $col"
                                    :selected="$isSelected"
                                    :highlighted="$isHighlighted"
                                    :draggable="true"
                                    :variant="$isKing ? 'king' : 'default'"
                                    :imageUrl="$this->getPieceAsset($piece)"
                                    size="large"
                                    draggable="true"
                                    ondragstart="@this.startDrag({{ $row }}, {{ $col }})"
                                    ondragend="@this.clearHighlights()" />
                            @endif
                            
                            @if($isValidTarget && $isDarkSquare)
                                <div class="move-target-indicator"></div>
                            @endif
                        </div>
                    @endfor
                @endfor
            </div>
        </div>

        <!-- Game Info Panel -->
        @php $stats = $this->getStats(); @endphp
        <div class="game-info">
            <h3>Game Stats</h3>
            <div class="game-stats">
                <div class="stat-item">
                    <span class="stat-label">Red Pieces:</span>
                    <span class="stat-value">{{ $stats['pieceCounts']['red'] + $stats['pieceCounts']['red_king'] }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Black Pieces:</span>
                    <span class="stat-value">{{ $stats['pieceCounts']['black'] + $stats['pieceCounts']['black_king'] }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Kings:</span>
                    <span class="stat-value">{{ $stats['pieceCounts']['red_king'] + $stats['pieceCounts']['black_king'] }}</span>
                </div>
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
    /* Checkers game specific liminal styling */
    .checkers-board {
        display: grid;
        grid-template-columns: repeat(8, 1fr);
        grid-template-rows: repeat(8, 1fr);
        max-width: 32rem;
        aspect-ratio: 1;
        margin: 0 auto;
        border: 2px solid rgb(100 116 139);
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .dark .checkers-board {
        border-color: rgb(71 85 105);
    }

    .board-square {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .light-square {
        background: rgb(248 250 252);
    }

    .dark-square {
        background: rgb(148 163 184);
    }

    .dark .light-square {
        background: rgb(203 213 225);
    }

    .dark .dark-square {
        background: rgb(100 116 139);
    }

    .board-square:hover {
        background-color: rgb(59 130 246 / 0.1);
    }

    .move-target-indicator {
        position: absolute;
        width: 1rem;
        height: 1rem;
        background: rgb(34 197 94);
        border-radius: 50%;
        opacity: 0.8;
        animation: gentle-pulse 2s ease-in-out infinite;
    }

    .capture-required {
        color: rgb(239 68 68);
        font-weight: 600;
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
            transform: scale(1.2);
            opacity: 1;
        }
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .checkers-board {
            max-width: 20rem;
        }
    }
</style>
