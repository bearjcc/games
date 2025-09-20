<div x-data="{
    timer: 0,
    gameStarted: false,
    gameOver: false,
    gameWon: false,
    difficulty: 'beginner',
    
    init() {
        this.startTimer();
    },
    
    startTimer() {
        setInterval(() => {
            if (this.gameStarted && !this.gameOver) {
                this.timer++;
            }
        }, 1000);
    },
    
    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    },
    
    startGame() {
        this.gameStarted = true;
        this.gameOver = false;
        this.gameWon = false;
        this.timer = 0;
        $wire.startGame();
    },
    
    newGame() {
        this.gameStarted = false;
        this.gameOver = false;
        this.gameWon = false;
        this.timer = 0;
        $wire.newGame();
    },
    
    changeDifficulty(difficulty) {
        this.difficulty = difficulty;
        $wire.changeDifficulty(difficulty);
    }
}">
    <x-game-styles />
    
    <x-game-layout title="Minesweeper" 
                   description="Classic puzzle game! Find all mines without triggering them. Use numbers to deduce mine locations and flag suspicious squares."
                   difficulty="Medium" 
                   estimatedDuration="5-30 minutes">
        
        <!-- Game Status -->
        <div class="game-status">
            @if($state['gameWon'])
                <div class="winner-indicator">
                    🎉 You Won! Perfect Game!
                    <div class="text-sm mt-2">
                        Time: <span x-text="formatTime(timer)"></span> |
                        Score: {{ $state['score'] }} |
                        Difficulty: {{ ucfirst($state['difficulty']) }}
                    </div>
                </div>
            @elseif($state['gameOver'])
                <div class="winner-indicator" style="color: var(--game-danger);">
                    💥 Game Over! You hit a mine!
                    <div class="text-sm mt-2">
                        Time: <span x-text="formatTime(timer)"></span> |
                        Score: {{ $state['score'] }} |
                        Difficulty: {{ ucfirst($state['difficulty']) }}
                    </div>
                </div>
            @elseif($state['gameStarted'])
                <div class="player-indicator">
                    Difficulty: {{ ucfirst($state['difficulty']) }} |
                    Mines: {{ $state['mineCount'] - $state['flagsUsed'] }} |
                    Revealed: {{ $state['squaresRevealed'] }}
                    <div class="text-sm mt-2">
                        Time: <span x-text="formatTime(timer)"></span> |
                        Flags: {{ $state['flagsUsed'] }}/{{ $state['mineCount'] }}
                    </div>
                </div>
            @else
                <div class="player-indicator">
                    Choose your difficulty and start playing!
                    <div class="text-sm mt-2">
                        Left-click to reveal, right-click to flag
                    </div>
                </div>
            @endif
        </div>

        <!-- Game Board Container -->
        <div class="game-board-container">
            <div class="game-board minesweeper-game-board">
                <!-- Difficulty Selection -->
                @if(!$state['gameStarted'])
                    <div class="difficulty-selection">
                        <h4>Choose Difficulty</h4>
                        <div class="difficulty-buttons">
                            <button class="action-button primary" @click="changeDifficulty('beginner')">
                                Beginner<br>
                                <span class="text-sm">9×9, 10 mines</span>
                            </button>
                            <button class="action-button primary" @click="changeDifficulty('intermediate')">
                                Intermediate<br>
                                <span class="text-sm">16×16, 40 mines</span>
                            </button>
                            <button class="action-button primary" @click="changeDifficulty('expert')">
                                Expert<br>
                                <span class="text-sm">16×30, 99 mines</span>
                            </button>
                        </div>
                        <button class="action-button success" @click="startGame()">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                            </svg>
                            Start Game
                        </button>
                    </div>
                @else
                    <!-- Minesweeper Grid -->
                    <div class="minesweeper-grid-container">
                        <div class="minesweeper-grid" 
                             style="grid-template-columns: repeat({{ $state['width'] }}, 1fr);">
                            @for($y = 0; $y < $state['height']; $y++)
                                @for($x = 0; $x < $state['width']; $x++)
                                    @php
                                        $cell = $state['board'][$y][$x];
                                        $isRevealed = $cell['revealed'] ?? false;
                                        $isFlagged = $cell['flagged'] ?? false;
                                        $isMine = $cell['type'] === 'mine' || $cell['type'] === 'exploded';
                                        $number = $cell['number'] ?? 0;
                                    @endphp
                                    
                                    <div class="minesweeper-cell 
                                        @if($isRevealed) revealed @endif
                                        @if($isFlagged) flagged @endif
                                        @if($isMine) mine @endif
                                        @if($cell['type'] === 'exploded') exploded @endif
                                        @if($number > 0) number-{{ $number }} @endif
                                    " 
                                    @click="
                                        if (!$wire.state.gameOver && !$wire.state.gameWon) {
                                            $wire.revealCell({{ $x }}, {{ $y }});
                                        }
                                    "
                                    @contextmenu.prevent="
                                        if (!$wire.state.gameOver && !$wire.state.gameWon) {
                                            $wire.flagCell({{ $x }}, {{ $y }});
                                        }
                                    ">
                                        
                                        @if($isFlagged)
                                            <span class="flag">🚩</span>
                                        @elseif($isRevealed)
                                            @if($isMine)
                                                <span class="mine">💣</span>
                                            @elseif($number > 0)
                                                <span class="number">{{ $number }}</span>
                                            @endif
                                        @endif
                                    </div>
                                @endfor
                            @endfor
                        </div>
                    </div>
                @endif

                <!-- Game Controls Panel -->
                <div class="controls-panel fade-in">
                    <!-- Game Controls -->
                    <div class="game-controls">
                        <h4>Game Controls</h4>
                        @if($state['gameOver'] || $state['gameWon'])
                            <button class="action-button primary" @click="newGame()">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"/>
                                </svg>
                                New Game
                            </button>
                        @elseif($state['gameStarted'])
                            <div class="control-buttons">
                                <button class="action-button" @click="newGame()">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"/>
                                    </svg>
                                    New Game
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Game Stats -->
                    @if($state['gameStarted'])
                        <div class="game-stats">
                            <h4>Game Stats</h4>
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <span class="stat-label">Mines Left:</span>
                                    <span class="stat-value">{{ $state['mineCount'] - $state['flagsUsed'] }}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Revealed:</span>
                                    <span class="stat-value">{{ $state['squaresRevealed'] }}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Score:</span>
                                    <span class="stat-value">{{ $state['score'] }}</span>
                                </div>
                                @if($state['bestTime'])
                                    <div class="stat-item">
                                        <span class="stat-label">Best Time:</span>
                                        <span class="stat-value">{{ $this->formatTime($state['bestTime']) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Controls Help -->
                    <div class="controls-help">
                        <h4>Controls</h4>
                        <div class="help-grid">
                            <div class="help-item">
                                <span class="help-icon">🖱️</span>
                                <span>Left-click to reveal</span>
                            </div>
                            <div class="help-item">
                                <span class="help-icon">🖱️</span>
                                <span>Right-click to flag</span>
                            </div>
                            <div class="help-item">
                                <span class="help-icon">🔢</span>
                                <span>Numbers show adjacent mines</span>
                            </div>
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
                            <li>Left-click to reveal a square</li>
                            <li>Right-click to flag/unflag a square</li>
                            <li>Numbers show how many mines are adjacent to that square</li>
                            <li>Use logic to determine where mines are located</li>
                            <li>Game ends when you hit a mine or clear all safe squares</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Scoring</h4>
                        <ul>
                            <li>Each revealed square: 1 point</li>
                            <li>Correctly flagged mine: 10 points</li>
                            <li>Incorrectly flagged square: -5 points</li>
                            <li>Time bonus for quick completion</li>
                            <li>Perfect game bonus for no mistakes</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </x-game-layout>

    <style>
        /* Minesweeper Grid Styles */
        .minesweeper-game-board {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 2rem;
            align-items: start;
        }

        .difficulty-selection {
            text-align: center;
            padding: 2rem;
        }

        .difficulty-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }

        .minesweeper-grid-container {
            display: flex;
            justify-content: center;
            overflow-x: auto;
        }

        .minesweeper-grid {
            display: grid;
            gap: 1px;
            background: var(--game-border);
            border: 2px solid var(--game-border);
            border-radius: 0.5rem;
            padding: 0.5rem;
        }

        .minesweeper-cell {
            width: 30px;
            height: 30px;
            background: var(--game-bg-primary);
            border: 1px solid var(--game-border);
            border-radius: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .minesweeper-cell:hover:not(.revealed):not(.flagged) {
            background: var(--game-bg-secondary);
            border-color: var(--game-border-hover);
        }

        .minesweeper-cell.revealed {
            background: var(--game-bg-tertiary);
            cursor: default;
        }

        .minesweeper-cell.flagged {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.3);
        }

        .minesweeper-cell.mine {
            background: rgba(239, 68, 68, 0.3);
            border-color: rgba(239, 68, 68, 0.5);
        }

        .minesweeper-cell.exploded {
            background: rgba(239, 68, 68, 0.5);
            border-color: rgba(239, 68, 68, 0.8);
            animation: shake 0.5s ease-in-out;
        }

        /* Number colors */
        .number-1 { color: rgb(59, 130, 246); }
        .number-2 { color: rgb(34, 197, 94); }
        .number-3 { color: rgb(239, 68, 68); }
        .number-4 { color: rgb(147, 51, 234); }
        .number-5 { color: rgb(245, 158, 11); }
        .number-6 { color: rgb(236, 72, 153); }
        .number-7 { color: rgb(0, 0, 0); }
        .number-8 { color: rgb(107, 114, 128); }

        /* Game Stats */
        .stats-grid {
            display: grid;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            background: var(--game-bg-primary);
            border-radius: 0.375rem;
            border: 1px solid var(--game-border);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--game-text-secondary);
        }

        .stat-value {
            font-weight: 600;
            color: var(--game-text-primary);
        }

        /* Controls Help */
        .help-grid {
            display: grid;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .help-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--game-text-secondary);
        }

        .help-icon {
            font-size: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .minesweeper-game-board {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .minesweeper-cell {
                width: 25px;
                height: 25px;
                font-size: 0.75rem;
            }
            
            .difficulty-buttons {
                grid-template-columns: 1fr;
            }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    </style>
</div>
