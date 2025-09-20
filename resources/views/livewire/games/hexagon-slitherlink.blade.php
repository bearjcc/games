<?php

use App\Games\HexagonSlitherlink\HexagonSlitherlinkGame;
use App\Games\HexagonSlitherlink\HexagonSlitherlinkEngine;
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
        $this->state = HexagonSlitherlinkEngine::newGame($this->selectedDifficulty);
    }

    public function newGame($difficulty = null)
    {
        if ($difficulty) {
            $this->selectedDifficulty = $difficulty;
        }
        $this->resetGame();
    }

    public function toggleLine($lineIndex)
    {
        $move = ['action' => 'toggle_line', 'lineIndex' => $lineIndex];
        $this->applyMove($move);
    }

    public function selectLine($lineIndex)
    {
        $move = ['action' => 'select_line', 'lineIndex' => $lineIndex];
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
        $game = new HexagonSlitherlinkGame();
        if ($game->canAutoSolve($this->state)) {
            $this->state = $game->autoSolve($this->state);
            
            // Update best score if user is authenticated
            if (auth()->check()) {
                $score = $game->getScore($this->state);
                app(UserBestScoreService::class)->updateIfBetter(
                    auth()->user(),
                    'hexagon-slitherlink',
                    $score
                );
            }
        }
    }

    public function solveStep()
    {
        $game = new HexagonSlitherlinkGame();
        $newState = $game->solveStep($this->state);
        if ($newState) {
            $this->state = $newState;
            
            // Update best score if game is complete and user is authenticated
            if ($this->state['gameComplete'] && auth()->check()) {
                $score = $game->getScore($this->state);
                app(UserBestScoreService::class)->updateIfBetter(
                    auth()->user(),
                    'hexagon-slitherlink',
                    $score
                );
            }
        }
    }

    public function applyMove($move)
    {
        $game = new HexagonSlitherlinkGame();
        
        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
            
            // Update best score if game is complete and user is authenticated
            if ($this->state['gameComplete'] && $this->state['gameWon'] && auth()->check()) {
                $score = $game->getScore($this->state);
                app(UserBestScoreService::class)->updateIfBetter(
                    auth()->user(),
                    'hexagon-slitherlink',
                    $score
                );
            }
        }
    }

    public function canUseHint()
    {
        $game = new HexagonSlitherlinkGame();
        return $game->canUseHint($this->state);
    }

    public function getLineClass($lineIndex)
    {
        $classes = ['hex-line'];
        
        if ($this->state['lines'][$lineIndex]) {
            $classes[] = 'active';
        }
        
        // Check if this line is selected
        if ($this->state['selectedLine'] === $lineIndex) {
            $classes[] = 'selected';
        }
        
        // Check for conflicts
        foreach ($this->state['conflicts'] as $conflict) {
            if ($conflict['type'] === 'cell') {
                // Add conflict styling to lines around conflicting cells
                $linesAroundCell = HexagonSlitherlinkEngine::getLinesAroundHexagonCell(
                    $this->state['lines'], 
                    $conflict['row'], 
                    $conflict['col'], 
                    $this->state['radius']
                );
                if (in_array($lineIndex, $linesAroundCell)) {
                    $classes[] = 'conflict';
                    break;
                }
            }
        }
        
        return implode(' ', $classes);
    }

    public function getCellClass($row, $col)
    {
        $classes = ['hex-cell'];
        
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
        $game = new HexagonSlitherlinkGame();
        $puzzleData = $game->getPuzzleForPrinting($this->state);
        
        // Store puzzle data in session for printing
        session(['print_puzzle' => $puzzleData]);
        
        // Redirect to print page
        return redirect()->route('games.print', ['game' => 'hexagon-slitherlink']);
    }

    public function getHexagonGrid()
    {
        $radius = $this->state['radius'];
        $size = $this->state['size'];
        $grid = [];
        $lineIndex = 0;
        
        // Build the hexagonal grid structure
        for ($row = 0; $row < $size; $row++) {
            $grid[$row] = [];
            for ($col = 0; $col < $size; $col++) {
                $grid[$row][$col] = [
                    'type' => 'empty',
                    'content' => null,
                    'isHexagonCell' => false
                ];
                
                // Check if this is a valid hexagon cell position
                if (HexagonSlitherlinkEngine::isHexagonCell($row, $col, $radius)) {
                    $grid[$row][$col]['type'] = 'hexagon';
                    $grid[$row][$col]['isHexagonCell'] = true;
                    
                    // Add clue if present
                    if ($this->state['clues'][$row][$col] !== null) {
                        $grid[$row][$col]['content'] = $this->state['clues'][$row][$col];
                    }
                }
            }
        }
        
        return $grid;
    }

    public function getHexagonLines()
    {
        $radius = $this->state['radius'];
        $size = $this->state['size'];
        $lines = [];
        $lineIndex = 0;
        
        // Collect all horizontal lines
        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size - 1; $col++) {
                if (HexagonSlitherlinkEngine::isValidLinePosition($row, $col, 'horizontal', $radius)) {
                    $lines[] = [
                        'index' => $lineIndex,
                        'type' => 'horizontal',
                        'row' => $row,
                        'col' => $col,
                        'startRow' => $row,
                        'startCol' => $col,
                        'endRow' => $row,
                        'endCol' => $col + 1
                    ];
                    $lineIndex++;
                }
            }
        }
        
        // Collect all diagonal lines
        for ($row = 0; $row < $size - 1; $row++) {
            for ($col = 0; $col < $size; $col++) {
                if (HexagonSlitherlinkEngine::isValidLinePosition($row, $col, 'diagonal1', $radius)) {
                    $lines[] = [
                        'index' => $lineIndex,
                        'type' => 'diagonal1',
                        'row' => $row,
                        'col' => $col,
                        'startRow' => $row,
                        'startCol' => $col,
                        'endRow' => $row + 1,
                        'endCol' => $col
                    ];
                    $lineIndex++;
                }
                if (HexagonSlitherlinkEngine::isValidLinePosition($row, $col, 'diagonal2', $radius)) {
                    $lines[] = [
                        'index' => $lineIndex,
                        'type' => 'diagonal2',
                        'row' => $row,
                        'col' => $col,
                        'startRow' => $row,
                        'startCol' => $col,
                        'endRow' => $row + 1,
                        'endCol' => $col + 1
                    ];
                    $lineIndex++;
                }
            }
        }
        
        return $lines;
    }

    public function getLineStyle($line)
    {
        $cellSize = 40; // Approximate cell size in pixels
        $startX = ($line['startCol'] + 0.5) * $cellSize;
        $startY = ($line['startRow'] + 0.5) * $cellSize;
        $endX = ($line['endCol'] + 0.5) * $cellSize;
        $endY = ($line['endRow'] + 0.5) * $cellSize;
        
        $length = sqrt(pow($endX - $startX, 2) + pow($endY - $startY, 2));
        $angle = atan2($endY - $startY, $endX - $startX) * 180 / M_PI;
        
        return "
            position: absolute;
            left: {$startX}px;
            top: {$startY}px;
            width: {$length}px;
            height: 4px;
            transform: rotate({$angle}deg);
            transform-origin: 0 50%;
        ";
    }
}; ?>

<div>
    <x-game-styles />
    
    <x-game-layout title="Hexagon Slitherlink" 
                   description="Connect dots in a beautiful honeycomb pattern to form a single closed loop! Numbers indicate how many lines surround each hexagonal cell."
                   difficulty="Hard" 
                   estimatedDuration="8-45 minutes">
        <!-- Game Status -->
        <div class="game-status">
            @if($state['gameComplete'])
                @if($state['gameWon'])
                    <div class="winner-indicator">
                        🍯 Honeycomb Solved! Score: {{ app(HexagonSlitherlinkGame::class)->getScore($state) }}
                        <div class="text-sm mt-2">
                            Difficulty: {{ HexagonSlitherlinkEngine::DIFFICULTIES[$selectedDifficulty]['label'] }} |
                            Hints Used: {{ $state['hintsUsed'] }}/{{ $state['maxHints'] }}
                        </div>
                    </div>
                @else
                    <div class="game-over-indicator">
                        ❌ Invalid Solution
                        <div class="text-sm mt-2">
                            Check your honeycomb loop and try again!
                        </div>
                    </div>
                @endif
            @else
                <div class="player-indicator">
                    Difficulty: {{ HexagonSlitherlinkEngine::DIFFICULTIES[$selectedDifficulty]['label'] }}
                    <div class="text-sm mt-2">
                        Radius: {{ $state['radius'] }} |
                        Hints: {{ $state['hintsUsed'] }}/{{ $state['maxHints'] }} |
                        Mistakes: {{ $state['mistakes'] }}/{{ $state['maxMistakes'] }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Game Board Container -->
        <div class="game-board-container">
            <div class="game-board hexagon-slitherlink-board">
                <!-- Hexagonal Grid -->
                <div class="hexagon-grid-container">
                    <div class="hexagon-grid" style="--grid-radius: {{ $state['radius'] }}; --grid-size: {{ $state['size'] }}">
                        <!-- Render hexagonal grid -->
                        @php
                            $grid = $this->getHexagonGrid();
                            $lines = $this->getHexagonLines();
                        @endphp
                        
                        <!-- Render grid cells -->
                        @for($row = 0; $row < $state['size']; $row++)
                            @for($col = 0; $col < $state['size']; $col++)
                                @if($grid[$row][$col]['isHexagonCell'])
                                    <div class="hex-cell-container {{ $this->getCellClass($row, $col) }}"
                                         style="grid-row: {{ $row + 1 }}; grid-column: {{ $col + 1 }};">
                                        @if($grid[$row][$col]['content'] !== null)
                                            <span class="hex-clue">{{ $grid[$row][$col]['content'] }}</span>
                                        @endif
                                    </div>
                                @endif
                            @endfor
                        @endfor
                        
                        <!-- Render lines -->
                        @foreach($lines as $line)
                            @php
                                $lineClass = $this->getLineClass($line['index']);
                                $lineStyle = $this->getLineStyle($line);
                            @endphp
                            <div class="{{ $lineClass }}"
                                 style="{{ $lineStyle }}"
                                 wire:click="toggleLine({{ $line['index'] }})">
                            </div>
                        @endforeach
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
                        <h4>New Honeycomb</h4>
                        <div class="difficulty-buttons">
                            @foreach(HexagonSlitherlinkEngine::DIFFICULTIES as $key => $info)
                                <button class="difficulty-button {{ $selectedDifficulty === $key ? 'active' : '' }}"
                                        wire:click="newGame('{{ $key }}')">
                                    {{ $info['label'] }}
                                    <span class="text-xs">Radius {{ $info['radius'] }}</span>
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
                            <li>Click between dots to toggle lines on/off in the honeycomb</li>
                            <li>Numbers indicate how many lines surround that hexagonal cell</li>
                            <li>Draw lines to form exactly one closed loop</li>
                            <li>No lines can cross or branch</li>
                            <li>Use hints for logical deductions</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Difficulty Levels</h4>
                        <ul>
                            <li><strong>Beginner:</strong> Radius 2 honeycomb, 8 hints</li>
                            <li><strong>Easy:</strong> Radius 3 honeycomb, 6 hints</li>
                            <li><strong>Medium:</strong> Radius 4 honeycomb, 5 hints</li>
                            <li><strong>Hard:</strong> Radius 5 honeycomb, 4 hints</li>
                            <li><strong>Expert:</strong> Radius 6 honeycomb, 3 hints</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Tips</h4>
                        <ul>
                            <li>Cells with "0" must have no lines around them</li>
                            <li>Cells with "3" must have exactly 3 lines around them</li>
                            <li>Look for cells that can only be satisfied one way</li>
                            <li>Remember: you're making one continuous honeycomb loop</li>
                            <li>Hexagonal grids have 6 directions around each cell</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </x-game-layout>

    <style>
        /* Hexagon Slitherlink Grid Styles */
        .hexagon-slitherlink-board {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 2rem;
            max-width: 90rem;
            margin: 0 auto;
            padding: 1rem;
        }

        @media (max-width: 1024px) {
            .hexagon-slitherlink-board {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        .hexagon-grid-container {
            display: flex;
            justify-content: center;
            overflow-x: auto;
            padding: 2rem;
        }

        .hexagon-grid {
            display: grid;
            grid-template-columns: repeat(var(--grid-size), 1fr);
            grid-template-rows: repeat(var(--grid-size), 1fr);
            gap: 0;
            border: 2px solid rgb(71 85 105);
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            min-width: 400px;
            max-width: 700px;
            aspect-ratio: 1;
            position: relative;
        }

        .dark .hexagon-grid {
            background: rgb(51 65 85);
            border-color: rgb(203 213 225);
        }

        /* Hexagon cell styling */
        .hex-cell-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            background: transparent;
            border-radius: 50%;
        }

        .hex-cell-container.conflict {
            background: rgba(239, 68, 68, 0.2);
            animation: pulse 0.5s ease-in-out;
        }

        .hex-clue {
            font-size: 1.5rem;
            font-weight: bold;
            color: rgb(71 85 105);
            background: white;
            border: 2px solid rgb(203 213 225);
            border-radius: 50%;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            position: relative;
        }

        .dark .hex-clue {
            color: rgb(203 213 225);
            background: rgb(51 65 85);
            border-color: rgb(71 85 105);
        }

        .hex-clue.conflict {
            background: rgb(254 226 226);
            color: rgb(220 38 38);
            border-color: rgb(239 68 68);
        }

        .dark .hex-clue.conflict {
            background: rgb(127 29 29);
            color: rgb(254 226 226);
        }

        /* Line styling */
        .hex-line {
            position: absolute;
            cursor: pointer;
            transition: all 0.2s;
            z-index: 5;
            background: transparent;
        }

        .hex-line:hover {
            background: rgba(59, 130, 246, 0.5);
            border-radius: 2px;
        }

        .hex-line.active {
            background: rgb(59 130 246);
            border-radius: 2px;
            box-shadow: 0 0 4px rgba(59, 130, 246, 0.5);
        }

        .dark .hex-line.active {
            background: rgb(147 51 234);
            box-shadow: 0 0 4px rgba(147, 51, 234, 0.5);
        }

        .hex-line.selected {
            background: rgb(34 197 94);
            box-shadow: 0 0 6px rgba(34, 197, 94, 0.7);
        }

        .hex-line.conflict {
            background: rgb(239 68 68);
            box-shadow: 0 0 6px rgba(239, 68, 68, 0.7);
            animation: pulse 0.5s ease-in-out;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        /* Controls Panel */
        .controls-panel {
            min-width: 18rem;
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
            .hexagon-grid {
                min-width: 300px;
                max-width: 500px;
                padding: 1rem;
            }

            .hexagon-slitherlink-board {
                padding: 0.5rem;
            }

            .controls-panel {
                min-width: auto;
            }

            .hex-clue {
                font-size: 1.25rem;
                width: 1.75rem;
                height: 1.75rem;
            }
        }

        @media (max-width: 480px) {
            .hexagon-grid {
                min-width: 250px;
                max-width: 400px;
                padding: 0.5rem;
            }

            .hex-clue {
                font-size: 1rem;
                width: 1.5rem;
                height: 1.5rem;
            }
        }
    </style>

</div>
