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
    <x-game.styles />
    <x-game.animations />
    
    <x-game.layout title="Peg Solitaire">
        <!-- Game Header -->
        <div class="game-header">
            <div class="game-status">
                @if($state['gameOver'])
                    @if($state['won'])
                        <div class="winner-indicator">
                            Genius! Only {{ $state['pegsRemaining'] }} peg remaining!
                        </div>
                    @else
                        <div class="draw-indicator">
                            {{ PegSolitaireEngine::getScoreMessage($state['pegsRemaining']) }}
                        </div>
                    @endif
                @else
                    <div class="player-indicator">
                        {{ $state['pegsRemaining'] }} pegs remaining
                        @if($selectedPeg !== null)
                            <span class="selection-hint">- Choose destination</span>
                        @else
                            <span class="selection-hint">- Select peg to move</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Game Settings -->
        <div class="game-settings">
            <div class="setting-row">
                <label class="setting-label">Difficulty:</label>
                <select wire:change="setDifficulty($event.target.value)" class="game-select">
                    <option value="standard" {{ $difficulty === 'standard' ? 'selected' : '' }}>Standard (center empty)</option>
                    <option value="random" {{ $difficulty === 'random' ? 'selected' : '' }}>Random empty hole</option>
                </select>
            </div>
        </div>

        <!-- Game Board -->
        <div class="game-board-container">
            <div class="peg-solitaire-board">
                <!-- Triangular board layout -->
                <div class="pegs-container">
                    @for($pos = 0; $pos < 15; $pos++)
                        @php
                            $coords = PegSolitaireEngine::getPositionCoordinates($pos);
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
                            
                            // Calculate position in triangular grid
                            $row = 0;
                            $colInRow = 0;
                            $posCount = 0;
                            for ($r = 0; $r < 5; $r++) {
                                $pegsInRow = $r + 1;
                                if ($pos < $posCount + $pegsInRow) {
                                    $row = $r;
                                    $colInRow = $pos - $posCount;
                                    break;
                                }
                                $posCount += $pegsInRow;
                            }
                            
                            // Center the position within its row
                            $left = 50 + ($colInRow - ($row / 2)) * 2; // percentage
                            $top = 10 + $row * 15; // percentage
                        @endphp
                        
                        <div class="peg-position" 
                             style="left: {{ $left }}%; top: {{ $top }}%;"
                             wire:click="clickPosition({{ $pos }})"
                             data-position="{{ $pos }}">
                            
                            @if($hasPeg)
                                <x-game.piece
                                    type="peg"
                                    :piece="$pos"
                                    :player="null"
                                    :position="$pos"
                                    :selected="$isSelected"
                                    :highlighted="false"
                                    :validTarget="false"
                                    size="default"
                                    class="game-peg" />
                            @else
                                <x-game.piece
                                    type="peg"
                                    :piece="null"
                                    :player="null"
                                    :position="$pos"
                                    :selected="false"
                                    :highlighted="false"
                                    :validTarget="$canMoveTo"
                                    variant="hole"
                                    size="default"
                                    class="empty-hole" />
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
                    <span class="stat-label">Pegs remaining:</span>
                    <span class="stat-value">{{ $state['pegsRemaining'] }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Moves made:</span>
                    <span class="stat-value">{{ $state['moves'] }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Score:</span>
                    <span class="stat-value">{{ number_format($stats['score']) }}</span>
                </div>
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
                    {{ $stats['scoreMessage'] }}
                    @if($state['won'])
                        <br><strong>Perfect Game!</strong>
                    @endif
                </div>
            @endif
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
            <button wire:click="showHint" class="game-button">
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

        <!-- Instructions -->
        <div class="game-instructions">
            <h4>How to Play</h4>
            <p class="instructions-text">
                Click a peg to select it, then click an empty hole to jump over and remove the adjacent peg.
                Goal: Remove all pegs except one!
            </p>
            <div class="scoring-guide">
                <span class="score-level genius">1 peg = Genius</span>
                <span class="score-level smart">2-3 pegs = Smart</span>
                <span class="score-level try-again">4+ pegs = Try Again</span>
            </div>
        </div>
        
</section>
    </x-game.layout>
</div>

<style>
    /* Peg Solitaire game specific liminal styling */
    .peg-solitaire-board {
        position: relative;
        max-width: 20rem;
        aspect-ratio: 1;
        margin: 0 auto;
        background: rgb(248 250 252);
        border: 2px solid rgb(100 116 139);
        border-radius: 0.5rem;
        padding: 2rem;
    }

    .dark .peg-solitaire-board {
        background: rgb(30 41 59);
        border-color: rgb(71 85 105);
    }

    .pegs-container {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .peg-position {
        position: absolute;
        width: 2rem;
        height: 2rem;
        margin-left: -1rem;
        margin-top: -1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .selection-hint {
        color: rgb(100 116 139);
        font-weight: 500;
        font-size: 0.875rem;
    }

    .dark .selection-hint {
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

    .final-message {
        margin-top: 1rem;
        padding: 0.75rem;
        background: rgb(239 246 255);
        border: 1px solid rgb(147 197 253);
        border-radius: 0.375rem;
        text-align: center;
        font-size: 0.875rem;
        color: rgb(30 58 138);
    }

    .dark .final-message {
        background: rgb(30 58 138 / 0.2);
        border-color: rgb(59 130 246 / 0.3);
        color: rgb(147 197 253);
    }

    .game-instructions {
        background: rgb(248 250 252 / 0.9);
        border: 1px solid rgb(203 213 225);
        border-radius: 0.5rem;
        padding: 1rem;
        margin-top: 1rem;
    }

    .dark .game-instructions {
        background: rgb(30 41 59 / 0.9);
        border-color: rgb(71 85 105);
    }

    .game-instructions h4 {
        margin: 0 0 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: rgb(100 116 139);
    }

    .dark .game-instructions h4 {
        color: rgb(148 163 184);
    }

    .instructions-text {
        margin: 0 0 0.75rem;
        font-size: 0.875rem;
        color: rgb(71 85 105);
        line-height: 1.4;
    }

    .dark .instructions-text {
        color: rgb(148 163 184);
    }

    .scoring-guide {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .score-level {
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .score-level.genius {
        background: rgb(34 197 94 / 0.2);
        color: rgb(22 101 52);
        border: 1px solid rgb(34 197 94 / 0.3);
    }

    .score-level.smart {
        background: rgb(59 130 246 / 0.2);
        color: rgb(29 78 216);
        border: 1px solid rgb(59 130 246 / 0.3);
    }

    .score-level.try-again {
        background: rgb(239 68 68 / 0.2);
        color: rgb(185 28 28);
        border: 1px solid rgb(239 68 68 / 0.3);
    }

    .dark .score-level.genius {
        background: rgb(34 197 94 / 0.2);
        color: rgb(74 222 128);
    }

    .dark .score-level.smart {
        background: rgb(59 130 246 / 0.2);
        color: rgb(147 197 253);
    }

    .dark .score-level.try-again {
        background: rgb(239 68 68 / 0.2);
        color: rgb(248 113 113);
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

    /* Responsive design */
    @media (max-width: 768px) {
        .peg-solitaire-board {
            max-width: 16rem;
        }
    }
</style>
