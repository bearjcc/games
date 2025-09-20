<?php

use App\Games\Sudoku\SudokuGame;
use App\Games\Sudoku\SudokuEngine;
use App\Services\UserBestScoreService;
use Livewire\Volt\Component;

new class extends Component
{
    public array $state;
    public bool $showCustomInput = false;
    public array $customPuzzle = [];
    public string $selectedDifficulty = 'medium';
    public bool $showInstructions = false;
    public int $gameTimer = 0;

    public function mount()
    {
        $this->resetGame();
        $this->initializeCustomPuzzle();
    }

    public function resetGame()
    {
        $game = new SudokuGame();
        $this->state = SudokuEngine::newGame($this->selectedDifficulty);
        $this->gameTimer = 0;
    }

    public function newGame($difficulty = null)
    {
        if ($difficulty) {
            $this->selectedDifficulty = $difficulty;
        }
        $this->resetGame();
        $this->showCustomInput = false;
    }

    public function selectCell($row, $col)
    {
        $move = ['action' => 'select_cell', 'row' => $row, 'col' => $col];
        $this->applyMove($move);
    }

    public function placeNumber($number)
    {
        if (!$this->state['selectedCell']) {
            return;
        }
        
        [$row, $col] = $this->state['selectedCell'];
        
        if ($this->state['notesMode']) {
            $move = ['action' => 'toggle_note', 'row' => $row, 'col' => $col, 'number' => $number];
        } else {
            $move = ['action' => 'place_number', 'row' => $row, 'col' => $col, 'number' => $number];
        }
        
        $this->applyMove($move);
    }

    public function clearSelectedCell()
    {
        $move = ['action' => 'clear_cell'];
        $this->applyMove($move);
    }

    public function toggleNotesMode()
    {
        $move = ['action' => 'toggle_notes_mode'];
        $this->applyMove($move);
    }

    public function useHint()
    {
        $move = ['action' => 'use_hint'];
        $this->applyMove($move);
    }

    public function applyMove($move)
    {
        $game = new SudokuGame();
        
        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
            
            // Update best score if game is complete and user is authenticated
            if ($this->state['gameComplete'] && auth()->check()) {
                $score = $game->getScore($this->state);
                app(UserBestScoreService::class)->updateIfBetter(
                    auth()->user(),
                    'sudoku',
                    $score
                );
            }
        }
    }

    public function showCustomPuzzleInput()
    {
        $this->showCustomInput = true;
        $this->initializeCustomPuzzle();
    }

    public function hideCustomPuzzleInput()
    {
        $this->showCustomInput = false;
    }

    public function initializeCustomPuzzle()
    {
        $this->customPuzzle = array_fill(0, 9, array_fill(0, 9, ''));
    }

    public function loadCustomPuzzle()
    {
        try {
            // Convert string inputs to integers
            $puzzle = [];
            for ($row = 0; $row < 9; $row++) {
                $puzzle[$row] = [];
                for ($col = 0; $col < 9; $col++) {
                    $value = trim($this->customPuzzle[$row][$col]);
                    $puzzle[$row][$col] = $value === '' ? 0 : intval($value);
                }
            }
            
            $game = new SudokuGame();
            $this->state = $game->loadCustomPuzzle($puzzle);
            $this->showCustomInput = false;
            $this->gameTimer = 0;
            
        } catch (\Exception $e) {
            // Handle invalid puzzle
            session()->flash('error', 'Invalid puzzle: ' . $e->getMessage());
        }
    }

    public function getCellClasses($row, $col)
    {
        $classes = ['sudoku-cell'];
        
        // Original puzzle cells (read-only)
        if ($this->state['originalPuzzle'][$row][$col] !== 0) {
            $classes[] = 'original-cell';
        }
        
        // Selected cell
        if ($this->state['selectedCell'] && $this->state['selectedCell'][0] === $row && $this->state['selectedCell'][1] === $col) {
            $classes[] = 'selected-cell';
        }
        
        // Conflict highlighting
        if (in_array([$row, $col], $this->state['conflicts'])) {
            $classes[] = 'conflict-cell';
        }
        
        // Box borders
        if ($row % 3 === 0 && $row > 0) $classes[] = 'border-top-thick';
        if ($col % 3 === 0 && $col > 0) $classes[] = 'border-left-thick';
        
        return implode(' ', $classes);
    }

    public function isInConflict($row, $col)
    {
        return in_array([$row, $col], $this->state['conflicts']);
    }

    public function canUseHint()
    {
        $game = new SudokuGame();
        return $game->canUseHint($this->state);
    }

    public function getProgressPercentage()
    {
        $filled = 0;
        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                if ($this->state['board'][$row][$col] !== 0) {
                    $filled++;
                }
            }
        }
        return round(($filled / 81) * 100);
    }

    public function handleKeyPress($key)
    {
        if (!$this->state['selectedCell']) {
            return;
        }
        
        if ($key >= '1' && $key <= '9') {
            $this->placeNumber(intval($key));
        } elseif ($key === 'Delete' || $key === 'Backspace') {
            $this->clearSelectedCell();
        }
    }
}; ?>

<div x-data="{
    timer: 0,
    timerInterval: null,
    startTimer() {
        if (this.timerInterval) return;
        this.timerInterval = setInterval(() => {
            if (!@json($state['gameComplete']) && @json($state['gameStarted'])) {
                this.timer++;
                $wire.gameTimer = this.timer;
            }
        }, 1000);
    },
    stopTimer() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
    },
    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return mins.toString().padStart(2, '0') + ':' + secs.toString().padStart(2, '0');
    }
}" 
x-init="startTimer()"
x-on:keydown.window="
    if (['1','2','3','4','5','6','7','8','9'].includes($event.key)) {
        $wire.placeNumber(parseInt($event.key));
        $event.preventDefault();
    } else if (['Delete', 'Backspace'].includes($event.key)) {
        $wire.clearSelectedCell();
        $event.preventDefault();
    }
">
    <x-game-styles />
    
    <x-game-layout title="Sudoku" 
                   description="Classic number puzzle - fill the 9×9 grid so each row, column, and 3×3 box contains digits 1-9 exactly once!"
                   difficulty="Medium" 
                   estimatedDuration="10-60 minutes">
        <!-- Game Status -->
        <div class="game-status">
            @if($state['gameComplete'])
                <div class="winner-indicator">
                    🎉 Puzzle Complete! Score: {{ app(SudokuGame::class)->getScore($state) }}
                    <div class="text-sm mt-2">
                        Time: <span x-text="formatTime(timer)"></span> | 
                        Hints: {{ $state['hintsUsed'] }}/{{ $state['maxHints'] }} |
                        Mistakes: {{ $state['mistakes'] }}/{{ $state['maxMistakes'] }}
                    </div>
                </div>
            @else
                <div class="player-indicator">
                    Difficulty: {{ ucfirst($selectedDifficulty) }}
                    <div class="text-sm mt-2">
                        Progress: {{ $this->getProgressPercentage() }}% |
                        Time: <span x-text="formatTime(timer)"></span> |
                        Hints: {{ $state['hintsUsed'] }}/{{ $state['maxHints'] }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Game Board Container -->
        <div class="game-board-container">
            <div class="game-board sudoku-game-board">
                <!-- Main Sudoku Grid -->
                <div class="sudoku-grid-container">
                    <div class="sudoku-grid">
                        @for($row = 0; $row < 9; $row++)
                            @for($col = 0; $col < 9; $col++)
                                <div class="{{ $this->getCellClasses($row, $col) }}"
                                     wire:click="selectCell({{ $row }}, {{ $col }})"
                                     data-row="{{ $row }}" data-col="{{ $col }}">
                                    
                                    @if($state['board'][$row][$col] !== 0)
                                        <!-- Number -->
                                        <span class="cell-number {{ $this->isInConflict($row, $col) ? 'conflict-number' : '' }}">
                                            {{ $state['board'][$row][$col] }}
                                        </span>
                                    @else
                                        <!-- Notes -->
                                        <div class="cell-notes">
                                            @foreach($state['notes'][$row][$col] as $note)
                                                <span class="note">{{ $note }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endfor
                        @endfor
                    </div>
                </div>

                <!-- Game Controls Panel -->
                <div class="controls-panel fade-in">
                    <!-- Number Input -->
                    <div class="number-input">
                        <h4>Numbers</h4>
                        <div class="number-grid">
                            @for($num = 1; $num <= 9; $num++)
                                <button class="number-button" 
                                        wire:click="placeNumber({{ $num }})"
                                        {{ !$state['selectedCell'] ? 'disabled' : '' }}>
                                    {{ $num }}
                                </button>
                            @endfor
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button class="action-button {{ $state['notesMode'] ? 'active' : '' }}"
                                wire:click="toggleNotesMode">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.828-2.828z"/>
                            </svg>
                            Notes
                        </button>

                        <button class="action-button" 
                                wire:click="clearSelectedCell"
                                {{ !$state['selectedCell'] ? 'disabled' : '' }}>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
                            </svg>
                            Clear
                        </button>

                        <button class="action-button hint-button" 
                                wire:click="useHint"
                                {{ !$this->canUseHint() ? 'disabled' : '' }}>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"/>
                            </svg>
                            Hint ({{ $state['maxHints'] - $state['hintsUsed'] }})
                        </button>
                    </div>

                    <!-- Game Options -->
                    <div class="game-options">
                        <h4>New Game</h4>
                        <div class="difficulty-buttons">
                            @foreach(SudokuEngine::DIFFICULTIES as $key => $info)
                                <button class="difficulty-button {{ $selectedDifficulty === $key ? 'active' : '' }}"
                                        wire:click="newGame('{{ $key }}')">
                                    {{ $info['label'] }}
                                </button>
                            @endforeach
                        </div>

                        <button class="custom-puzzle-button" wire:click="showCustomPuzzleInput">
                            Custom Puzzle
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Puzzle Input Modal -->
        @if($showCustomInput)
            <div class="modal-overlay" wire:click="hideCustomPuzzleInput">
                <div class="modal-content" wire:click.stop>
                    <h3>Enter Custom Puzzle</h3>
                    <p class="text-sm text-gray-600 mb-4">Enter numbers 1-9, leave empty cells blank</p>
                    
                    <div class="custom-puzzle-grid">
                        @for($row = 0; $row < 9; $row++)
                            @for($col = 0; $col < 9; $col++)
                                <input type="text" 
                                       class="custom-cell {{ ($row % 3 === 0 && $row > 0) ? 'border-top-thick' : '' }} {{ ($col % 3 === 0 && $col > 0) ? 'border-left-thick' : '' }}"
                                       wire:model="customPuzzle.{{ $row }}.{{ $col }}"
                                       maxlength="1"
                                       pattern="[1-9]">
                            @endfor
                        @endfor
                    </div>

                    <div class="modal-buttons">
                        <button class="modal-button primary" wire:click="loadCustomPuzzle">
                            Load Puzzle
                        </button>
                        <button class="modal-button secondary" wire:click="hideCustomPuzzleInput">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Instructions -->
        <div class="instructions">
            <button class="instruction-toggle" @click="$wire.showInstructions = !$wire.showInstructions">
                {{ $showInstructions ? 'Hide' : 'Show' }} Instructions
            </button>
            
            @if($showInstructions)
                <div class="instruction-content slide-up">
                    <div class="instruction-section">
                        <h4>How to Play</h4>
                        <ul>
                            <li>Click a cell to select it, then click a number to place it</li>
                            <li>Use Notes mode to add possible candidates to empty cells</li>
                            <li>Invalid placements will be highlighted in red</li>
                            <li>Use hints when stuck (limited per puzzle)</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Keyboard Shortcuts</h4>
                        <ul>
                            <li>Numbers 1-9: Place number in selected cell</li>
                            <li>Delete/Backspace: Clear selected cell</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </x-game-layout>

    <style>
        /* Sudoku Grid Styles */
        .sudoku-game-board {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 2rem;
            max-width: 70rem;
            margin: 0 auto;
            padding: 1rem;
        }

        @media (max-width: 1024px) {
            .sudoku-game-board {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        .sudoku-grid-container {
            display: flex;
            justify-content: center;
        }

        .sudoku-grid {
            display: grid;
            grid-template-columns: repeat(9, 1fr);
            grid-template-rows: repeat(9, 1fr);
            width: 27rem;
            height: 27rem;
            border: 3px solid rgb(71 85 105);
            background: white;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .dark .sudoku-grid {
            background: rgb(51 65 85);
            border-color: rgb(203 213 225);
        }

        .sudoku-cell {
            width: 3rem;
            height: 3rem;
            border: 1px solid rgb(203 213 225);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            transition: all 0.2s;
        }

        .dark .sudoku-cell {
            border-color: rgb(71 85 105);
        }

        .sudoku-cell:hover {
            background: rgb(243 244 246);
        }

        .dark .sudoku-cell:hover {
            background: rgb(71 85 105);
        }

        .original-cell {
            background: rgb(249 250 251);
            font-weight: bold;
        }

        .dark .original-cell {
            background: rgb(55 65 81);
        }

        .selected-cell {
            background: rgb(219 234 254) !important;
            box-shadow: inset 0 0 0 2px rgb(59 130 246);
        }

        .dark .selected-cell {
            background: rgb(30 58 138) !important;
        }

        .conflict-cell {
            background: rgb(254 226 226) !important;
        }

        .dark .conflict-cell {
            background: rgb(127 29 29) !important;
        }

        .border-top-thick {
            border-top: 3px solid rgb(71 85 105) !important;
        }

        .border-left-thick {
            border-left: 3px solid rgb(71 85 105) !important;
        }

        .dark .border-top-thick {
            border-top-color: rgb(203 213 225) !important;
        }

        .dark .border-left-thick {
            border-left-color: rgb(203 213 225) !important;
        }

        .cell-number {
            font-size: 1.25rem;
            font-weight: 600;
            color: rgb(17 24 39);
        }

        .dark .cell-number {
            color: rgb(243 244 246);
        }

        .conflict-number {
            color: rgb(220 38 38) !important;
        }

        .cell-notes {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            width: 100%;
            height: 100%;
            font-size: 0.625rem;
            color: rgb(107 114 128);
        }

        .dark .cell-notes {
            color: rgb(156 163 175);
        }

        .note {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Controls Panel */
        .controls-panel {
            min-width: 16rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .controls-panel h4 {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: rgb(71 85 105);
        }

        .dark .controls-panel h4 {
            color: rgb(203 213 225);
        }

        .number-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
        }

        .number-button {
            width: 3rem;
            height: 3rem;
            border: 2px solid rgb(203 213 225);
            background: white;
            border-radius: 0.375rem;
            font-weight: bold;
            font-size: 1.125rem;
            color: rgb(71 85 105);
            cursor: pointer;
            transition: all 0.2s;
        }

        .dark .number-button {
            background: rgb(51 65 85);
            border-color: rgb(71 85 105);
            color: rgb(203 213 225);
        }

        .number-button:hover:not(:disabled) {
            background: rgb(59 130 246);
            color: white;
            border-color: rgb(59 130 246);
        }

        .number-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .action-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            background: white;
            border: 2px solid rgb(203 213 225);
            border-radius: 0.375rem;
            color: rgb(71 85 105);
            cursor: pointer;
            transition: all 0.2s;
        }

        .dark .action-button {
            background: rgb(51 65 85);
            border-color: rgb(71 85 105);
            color: rgb(203 213 225);
        }

        .action-button:hover:not(:disabled) {
            background: rgb(243 244 246);
            border-color: rgb(156 163 175);
        }

        .dark .action-button:hover:not(:disabled) {
            background: rgb(71 85 105);
        }

        .action-button.active {
            background: rgb(59 130 246);
            color: white;
            border-color: rgb(59 130 246);
        }

        .action-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .hint-button:not(:disabled) {
            background: rgb(34 197 94);
            color: white;
            border-color: rgb(34 197 94);
        }

        .difficulty-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .difficulty-button {
            padding: 0.5rem;
            background: white;
            border: 2px solid rgb(203 213 225);
            border-radius: 0.375rem;
            color: rgb(71 85 105);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }

        .dark .difficulty-button {
            background: rgb(51 65 85);
            border-color: rgb(71 85 105);
            color: rgb(203 213 225);
        }

        .difficulty-button:hover {
            background: rgb(243 244 246);
        }

        .dark .difficulty-button:hover {
            background: rgb(71 85 105);
        }

        .difficulty-button.active {
            background: rgb(59 130 246);
            color: white;
            border-color: rgb(59 130 246);
        }

        .custom-puzzle-button {
            width: 100%;
            padding: 0.75rem;
            background: rgb(168 85 247);
            color: white;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .custom-puzzle-button:hover {
            background: rgb(147 51 234);
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .modal-content {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            max-width: 32rem;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .dark .modal-content {
            background: rgb(51 65 85);
            color: rgb(243 244 246);
        }

        .custom-puzzle-grid {
            display: grid;
            grid-template-columns: repeat(9, 1fr);
            grid-template-rows: repeat(9, 1fr);
            gap: 1px;
            width: 18rem;
            height: 18rem;
            margin: 1rem auto;
            border: 2px solid rgb(71 85 105);
            border-radius: 0.25rem;
            overflow: hidden;
        }

        .custom-cell {
            width: 100%;
            height: 100%;
            text-align: center;
            border: 1px solid rgb(203 213 225);
            background: white;
            font-weight: bold;
        }

        .dark .custom-cell {
            background: rgb(55 65 81);
            border-color: rgb(71 85 105);
            color: rgb(243 244 246);
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        .modal-button {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .modal-button.primary {
            background: rgb(59 130 246);
            color: white;
            border: none;
        }

        .modal-button.primary:hover {
            background: rgb(37 99 235);
        }

        .modal-button.secondary {
            background: white;
            color: rgb(71 85 105);
            border: 2px solid rgb(203 213 225);
        }

        .dark .modal-button.secondary {
            background: rgb(51 65 85);
            color: rgb(203 213 225);
            border-color: rgb(71 85 105);
        }

        .modal-button.secondary:hover {
            background: rgb(243 244 246);
        }

        .dark .modal-button.secondary:hover {
            background: rgb(71 85 105);
        }

        /* Instructions */
        .instructions {
            margin-top: 1rem;
            text-align: center;
        }

        .instruction-toggle {
            background: rgb(107 114 128);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
        }

        .instruction-toggle:hover {
            background: rgb(75 85 99);
        }

        .instruction-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 1rem;
            text-align: left;
        }

        @media (max-width: 768px) {
            .instruction-content {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        .instruction-section h4 {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .instruction-section ul {
            list-style: disc;
            margin-left: 1.5rem;
        }

        .instruction-section li {
            margin-bottom: 0.25rem;
        }
    </style>
</div>
