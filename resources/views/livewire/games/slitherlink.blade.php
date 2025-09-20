<?php

use App\Games\Slitherlink\SlitherlinkGame;
use App\Games\Slitherlink\SlitherlinkEngine;
use App\Services\UserBestScoreService;
use Livewire\Volt\Component;

new class extends Component
{
    public array $state;
    public string $selectedDifficulty = 'medium';
    public bool $showInstructions = false;

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $this->state = SlitherlinkEngine::newGame($this->selectedDifficulty);
    }

    public function newGame($difficulty = null)
    {
        if ($difficulty) {
            $this->selectedDifficulty = $difficulty;
        }
        $this->resetGame();
    }

    public function toggleLine($type, $row, $col)
    {
        $move = ['action' => 'toggle_line', 'type' => $type, 'row' => $row, 'col' => $col];
        $this->applyMove($move);
    }

    public function selectLine($type, $row, $col)
    {
        $move = ['action' => 'select_line', 'type' => $type, 'row' => $row, 'col' => $col];
        $this->applyMove($move);
    }

    public function useHint()
    {
        $move = ['action' => 'use_hint'];
        $this->applyMove($move);
    }

    public function clearAll()
    {
        $move = ['action' => 'clear_all'];
        $this->applyMove($move);
    }

    public function checkSolution()
    {
        $move = ['action' => 'check_solution'];
        $this->applyMove($move);
    }

    public function autoSolve()
    {
        $game = new SlitherlinkGame();
        if ($game->canAutoSolve($this->state)) {
            $this->state = $game->autoSolve($this->state);
            
            // Update best score if user is authenticated
            if (auth()->check()) {
                $score = $game->getScore($this->state);
                app(UserBestScoreService::class)->updateIfBetter(
                    auth()->user(),
                    'slitherlink',
                    $score
                );
            }
        }
    }

    public function solveStep()
    {
        $game = new SlitherlinkGame();
        $newState = $game->solveStep($this->state);
        if ($newState) {
            $this->state = $newState;
            
            // Update best score if game is complete and user is authenticated
            if ($this->state['gameComplete'] && auth()->check()) {
                $score = $game->getScore($this->state);
                app(UserBestScoreService::class)->updateIfBetter(
                    auth()->user(),
                    'slitherlink',
                    $score
                );
            }
        }
    }

    public function applyMove($move)
    {
        $game = new SlitherlinkGame();
        
        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
            
            // Update best score if game is complete and user is authenticated
            if ($this->state['gameComplete'] && $this->state['gameWon'] && auth()->check()) {
                $score = $game->getScore($this->state);
                app(UserBestScoreService::class)->updateIfBetter(
                    auth()->user(),
                    'slitherlink',
                    $score
                );
            }
        }
    }

    public function canUseHint()
    {
        $game = new SlitherlinkGame();
        return $game->canUseHint($this->state);
    }

    public function getLineClass($type, $row, $col)
    {
        $classes = ['line'];
        
        if ($type === 'horizontal') {
            $classes[] = 'horizontal-line';
            if ($this->state['horizontalLines'][$row][$col]) {
                $classes[] = 'active';
            }
        } else {
            $classes[] = 'vertical-line';
            if ($this->state['verticalLines'][$row][$col]) {
                $classes[] = 'active';
            }
        }
        
        // Check if this line is selected
        if ($this->state['selectedLine'] && 
            $this->state['selectedLine']['type'] === $type &&
            $this->state['selectedLine']['row'] === $row &&
            $this->state['selectedLine']['col'] === $col) {
            $classes[] = 'selected';
        }
        
        // Check for conflicts
        foreach ($this->state['conflicts'] as $conflict) {
            if ($conflict['type'] === 'cell') {
                // Add conflict styling to lines around conflicting cells
                if (($type === 'horizontal' && 
                     (($conflict['row'] === $row && $conflict['col'] === $col) ||
                      ($conflict['row'] === $row - 1 && $conflict['col'] === $col))) ||
                    ($type === 'vertical' && 
                     (($conflict['row'] === $row && $conflict['col'] === $col) ||
                      ($conflict['row'] === $row && $conflict['col'] === $col - 1)))) {
                    $classes[] = 'conflict';
                    break;
                }
            }
        }
        
        return implode(' ', $classes);
    }

    public function getCellClass($row, $col)
    {
        $classes = ['cell'];
        
        // Check if this cell has a conflict
        foreach ($this->state['conflicts'] as $conflict) {
            if ($conflict['type'] === 'cell' && 
                $conflict['row'] === $row && 
                $conflict['col'] === $col) {
                $classes[] = 'conflict';
                break;
            }
        }
        
        return implode(' ', $classes);
    }

    public function printPuzzle()
    {
        $game = new SlitherlinkGame();
        $puzzleData = $game->getPuzzleForPrinting($this->state);
        
        // Store puzzle data in session for printing
        session(['print_puzzle' => $puzzleData]);
        
        // Redirect to print page
        return redirect()->route('games.print', ['game' => 'slitherlink']);
    }
}; ?>

<div>
    <x-game-styles />
    
    <x-game-layout title="Slitherlink" 
                   description="Connect dots with lines to form a single closed loop! Numbers indicate how many lines surround each cell."
                   difficulty="Medium" 
                   estimatedDuration="5-30 minutes">
        <!-- Game Status -->
        <div class="game-status">
            @if($state['gameComplete'])
                @if($state['gameWon'])
                    <div class="winner-indicator">
                        🎉 Puzzle Solved! Score: {{ app(SlitherlinkGame::class)->getScore($state) }}
                        <div class="text-sm mt-2">
                            Difficulty: {{ SlitherlinkEngine::DIFFICULTIES[$selectedDifficulty]['label'] }} |
                            Hints Used: {{ $state['hintsUsed'] }}/{{ $state['maxHints'] }}
                        </div>
                    </div>
                @else
                    <div class="game-over-indicator">
                        ❌ Invalid Solution
                        <div class="text-sm mt-2">
                            Check your loop and try again!
                        </div>
                    </div>
                @endif
            @else
                <div class="player-indicator">
                    Difficulty: {{ SlitherlinkEngine::DIFFICULTIES[$selectedDifficulty]['label'] }}
                    <div class="text-sm mt-2">
                        Size: {{ $state['size'] }}×{{ $state['size'] }} |
                        Hints: {{ $state['hintsUsed'] }}/{{ $state['maxHints'] }} |
                        Mistakes: {{ $state['mistakes'] }}/{{ $state['maxMistakes'] }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Game Board Container -->
        <div class="game-board-container">
            <div class="game-board slitherlink-game-board">
                <!-- Slitherlink Grid -->
                <div class="slitherlink-grid-container">
                    <div class="slitherlink-grid" style="--grid-size: {{ $state['size'] }}">
                        <!-- Render dots and lines -->
                        @for($row = 0; $row <= $state['size']; $row++)
                            @for($col = 0; $col <= $state['size']; $col++)
                                <!-- Dot -->
                                <div class="dot" style="grid-row: {{ $row + 1 }}; grid-column: {{ $col + 1 }};"></div>
                                
                                <!-- Horizontal line to the right -->
                                @if($col < $state['size'])
                                    <div class="{{ $this->getLineClass('horizontal', $row, $col) }}"
                                         style="grid-row: {{ $row + 1 }}; grid-column: {{ $col + 1 }} / span 2;"
                                         wire:click="toggleLine('horizontal', {{ $row }}, {{ $col }})">
                                    </div>
                                @endif
                                
                                <!-- Vertical line below -->
                                @if($row < $state['size'])
                                    <div class="{{ $this->getLineClass('vertical', $row, $col) }}"
                                         style="grid-row: {{ $row + 1 }} / span 2; grid-column: {{ $col + 1 }};"
                                         wire:click="toggleLine('vertical', {{ $row }}, {{ $col }})">
                                    </div>
                                @endif
                                
                                <!-- Cell with clue -->
                                @if($row < $state['size'] && $col < $state['size'] && $state['clues'][$row][$col] !== null)
                                    <div class="{{ $this->getCellClass($row, $col) }}"
                                         style="grid-row: {{ $row + 1 }}; grid-column: {{ $col + 1 }};">
                                        {{ $state['clues'][$row][$col] }}
                                    </div>
                                @endif
                            @endfor
                        @endfor
                    </div>
                </div>

                <!-- Game Controls Panel -->
                <div class="controls-panel fade-in">
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button class="action-button hint-button" 
                                wire:click="useHint"
                                {{ !$this->canUseHint() ? 'disabled' : '' }}>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"/>
                            </svg>
                            Hint ({{ $state['maxHints'] - $state['hintsUsed'] }})
                        </button>

                        <button class="action-button solve-step-button" 
                                wire:click="solveStep"
                                {{ $state['gameComplete'] ? 'disabled' : '' }}>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z"/>
                            </svg>
                            Solve Step
                        </button>

                        <button class="action-button auto-solve-button" 
                                wire:click="autoSolve"
                                {{ $state['gameComplete'] ? 'disabled' : '' }}>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                            </svg>
                            Auto Solve
                        </button>

                        <button class="action-button clear-button" 
                                wire:click="clearAll">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
                            </svg>
                            Clear All
                        </button>

                        <button class="action-button check-button" 
                                wire:click="checkSolution">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z"/>
                            </svg>
                            Check Solution
                        </button>

                        <button class="action-button print-button" 
                                wire:click="printPuzzle">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z"/>
                            </svg>
                            Print Puzzle
                        </button>
                    </div>

                    <!-- Difficulty Selection -->
                    <div class="game-options">
                        <h4>New Puzzle</h4>
                        <div class="difficulty-buttons">
                            @foreach(SlitherlinkEngine::DIFFICULTIES as $key => $info)
                                <button class="difficulty-button {{ $selectedDifficulty === $key ? 'active' : '' }}"
                                        wire:click="newGame('{{ $key }}')">
                                    {{ $info['label'] }}
                                    <span class="text-xs">{{ $info['size'] }}×{{ $info['size'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                            <li>Click between dots to toggle lines on/off</li>
                            <li>Numbers indicate how many lines surround that cell</li>
                            <li>Draw lines to form exactly one closed loop</li>
                            <li>No lines can cross or branch</li>
                            <li>Use hints for logical deductions</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Difficulty Levels</h4>
                        <ul>
                            <li><strong>Beginner:</strong> 5×5 grid, 6 hints</li>
                            <li><strong>Easy:</strong> 6×6 grid, 5 hints</li>
                            <li><strong>Medium:</strong> 7×7 grid, 4 hints</li>
                            <li><strong>Hard:</strong> 8×8 grid, 3 hints</li>
                            <li><strong>Expert:</strong> 9×9 grid, 2 hints</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Tips</h4>
                        <ul>
                            <li>Cells with "0" must have no lines around them</li>
                            <li>Cells with "3" must have exactly 3 lines around them</li>
                            <li>Look for cells that can only be satisfied one way</li>
                            <li>Remember: you're making one continuous loop</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </x-game-layout>

    <style>
        /* Slitherlink Grid Styles */
        .slitherlink-game-board {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 2rem;
            max-width: 80rem;
            margin: 0 auto;
            padding: 1rem;
        }

        @media (max-width: 1024px) {
            .slitherlink-game-board {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        .slitherlink-grid-container {
            display: flex;
            justify-content: center;
            overflow-x: auto;
        }

        .slitherlink-grid {
            display: grid;
            grid-template-columns: repeat(var(--grid-size), 1fr);
            grid-template-rows: repeat(var(--grid-size), 1fr);
            gap: 0;
            border: 2px solid rgb(71 85 105);
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
            min-width: 400px;
            max-width: 600px;
            aspect-ratio: 1;
        }

        .dark .slitherlink-grid {
            background: rgb(51 65 85);
            border-color: rgb(203 213 225);
        }

        .dot {
            width: 8px;
            height: 8px;
            background: rgb(71 85 105);
            border-radius: 50%;
            position: relative;
            z-index: 10;
        }

        .dark .dot {
            background: rgb(203 213 225);
        }

        .line {
            position: relative;
            cursor: pointer;
            transition: all 0.2s;
            z-index: 5;
        }

        .horizontal-line {
            height: 4px;
            width: 100%;
            margin: -2px 0;
        }

        .vertical-line {
            width: 4px;
            height: 100%;
            margin: 0 -2px;
        }

        .line:hover {
            background: rgba(59, 130, 246, 0.5);
            border-radius: 2px;
        }

        .line.active {
            background: rgb(59 130 246);
            border-radius: 2px;
            box-shadow: 0 0 4px rgba(59, 130, 246, 0.5);
        }

        .dark .line.active {
            background: rgb(147 51 234);
            box-shadow: 0 0 4px rgba(147, 51, 234, 0.5);
        }

        .line.selected {
            background: rgb(34 197 94);
            box-shadow: 0 0 6px rgba(34, 197, 94, 0.7);
        }

        .line.conflict {
            background: rgb(239 68 68);
            box-shadow: 0 0 6px rgba(239, 68, 68, 0.7);
            animation: pulse 0.5s ease-in-out;
        }

        .cell {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.125rem;
            color: rgb(71 85 105);
            background: white;
            border-radius: 4px;
            z-index: 15;
        }

        .dark .cell {
            color: rgb(203 213 225);
            background: rgb(51 65 85);
        }

        .cell.conflict {
            background: rgb(254 226 226);
            color: rgb(220 38 38);
            animation: pulse 0.5s ease-in-out;
        }

        .dark .cell.conflict {
            background: rgb(127 29 29);
            color: rgb(254 226 226);
        }

        @keyframes pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); }
            50% { transform: translate(-50%, -50%) scale(1.1); }
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

        .action-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .hint-button:not(:disabled) {
            background: rgb(34 197 94);
            color: white;
            border-color: rgb(34 197 94);
        }

        .solve-step-button:not(:disabled) {
            background: rgb(168 85 247);
            color: white;
            border-color: rgb(168 85 247);
        }

        .auto-solve-button:not(:disabled) {
            background: rgb(239 68 68);
            color: white;
            border-color: rgb(239 68 68);
        }

        .clear-button:not(:disabled) {
            background: rgb(107 114 128);
            color: white;
            border-color: rgb(107 114 128);
        }

        .check-button:not(:disabled) {
            background: rgb(245 158 11);
            color: white;
            border-color: rgb(245 158 11);
        }

        .print-button:not(:disabled) {
            background: rgb(59 130 246);
            color: white;
            border-color: rgb(59 130 246);
        }

        .difficulty-buttons {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }

        .difficulty-button {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.75rem;
            background: white;
            border: 2px solid rgb(203 213 225);
            border-radius: 0.375rem;
            color: rgb(71 85 105);
            cursor: pointer;
            transition: all 0.2s;
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

        .difficulty-button span {
            font-size: 0.75rem;
            opacity: 0.8;
        }

        /* Game Status */
        .game-over-indicator {
            background: rgb(254 226 226);
            border: 2px solid rgb(239 68 68);
            color: rgb(127 29 29);
            padding: 1rem;
            border-radius: 0.5rem;
            text-align: center;
            font-weight: bold;
        }

        .dark .game-over-indicator {
            background: rgb(127 29 29);
            border-color: rgb(239 68 68);
            color: rgb(254 226 226);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .slitherlink-grid {
                min-width: 300px;
                max-width: 400px;
            }

            .slitherlink-game-board {
                padding: 0.5rem;
            }

            .controls-panel {
                min-width: auto;
            }
        }

        @media (max-width: 480px) {
            .slitherlink-grid {
                min-width: 250px;
                max-width: 300px;
                padding: 0.5rem;
            }

            .cell {
                width: 20px;
                height: 20px;
                font-size: 1rem;
            }
        }
    </style>
</div>
