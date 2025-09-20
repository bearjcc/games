<div x-data="{
    timer: 0,
    gameStarted: false,
    gameOver: false,
    paused: false,
    score: 0,
    level: 1,
    linesCleared: 0,
    
    init() {
        this.startTimer();
        this.setupKeyboardControls();
    },
    
    startTimer() {
        if (!this.gameStarted || this.gameOver || this.paused) return;
        
        setInterval(() => {
            if (this.gameStarted && !this.gameOver && !this.paused) {
                this.timer++;
            }
        }, 1000);
    },
    
    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    },
    
    setupKeyboardControls() {
        document.addEventListener('keydown', (e) => {
            if (!this.gameStarted || this.gameOver || this.paused) return;
            
            switch(e.key) {
                case 'ArrowLeft':
                    $wire.movePiece('left');
                    e.preventDefault();
                    break;
                case 'ArrowRight':
                    $wire.movePiece('right');
                    e.preventDefault();
                    break;
                case 'ArrowDown':
                    $wire.movePiece('down');
                    e.preventDefault();
                    break;
                case 'ArrowUp':
                    $wire.rotatePiece();
                    e.preventDefault();
                    break;
                case ' ':
                    $wire.hardDrop();
                    e.preventDefault();
                    break;
                case 'p':
                case 'P':
                    $wire.togglePause();
                    e.preventDefault();
                    break;
            }
        });
    },
    
    startGame() {
        this.gameStarted = true;
        this.gameOver = false;
        this.paused = false;
        this.timer = 0;
        $wire.startGame();
    },
    
    togglePause() {
        this.paused = !this.paused;
        $wire.togglePause();
    },
    
    newGame() {
        this.gameStarted = false;
        this.gameOver = false;
        this.paused = false;
        this.timer = 0;
        $wire.newGame();
    }
}" 
@keydown.window="
    if (['ArrowLeft', 'ArrowRight', 'ArrowDown', 'ArrowUp', ' '].includes($event.key)) {
        $event.preventDefault();
    }
">
    <x-game-styles />
    
    <x-game-layout title="Tetris" 
                   description="Classic falling block puzzle! Arrange falling tetrominoes to create complete horizontal lines and clear them for points."
                   difficulty="Medium" 
                   estimatedDuration="10-60 minutes">
        
        <!-- Game Status -->
        <div class="game-status">
            @if($state['gameOver'])
                <div class="winner-indicator">
                    🎮 Game Over! Final Score: {{ $state['score'] }}
                    <div class="text-sm mt-2">
                        Level: {{ $state['level'] }} | 
                        Lines: {{ $state['linesCleared'] }} |
                        Time: <span x-text="formatTime(timer)"></span>
                    </div>
                </div>
            @elseif($state['gameStarted'])
                <div class="player-indicator">
                    Level: {{ $state['level'] }} | 
                    Score: {{ $state['score'] }} |
                    Lines: {{ $state['linesCleared'] }}
                    <div class="text-sm mt-2">
                        Time: <span x-text="formatTime(timer)"></span>
                        @if($state['paused'])
                            | <span class="text-yellow-500">PAUSED</span>
                        @endif
                    </div>
                </div>
            @else
                <div class="player-indicator">
                    Ready to Play Tetris!
                    <div class="text-sm mt-2">
                        Use arrow keys to move and rotate pieces
                    </div>
                </div>
            @endif
        </div>

        <!-- Game Board Container -->
        <div class="game-board-container">
            <div class="game-board tetris-game-board">
                <!-- Tetris Grid -->
                <div class="tetris-grid-container">
                    <div class="tetris-grid">
                        @for($y = 0; $y < 20; $y++)
                            @for($x = 0; $x < 10; $x++)
                                <div class="tetris-cell 
                                    @if($state['board'][$y][$x] !== ' ')
                                        tetris-cell-filled tetris-{{ $state['board'][$y][$x] }}
                                    @endif
                                    @if(isset($state['currentPiece']) && $this->isCurrentPieceAt($x, $y))
                                        tetris-current-piece tetris-{{ $state['currentPiece']['color'] }}
                                    @endif
                                "></div>
                            @endfor
                        @endfor
                    </div>
                </div>

                <!-- Game Controls Panel -->
                <div class="controls-panel fade-in">
                    <!-- Game Controls -->
                    <div class="game-controls">
                        <h4>Game Controls</h4>
                        @if(!$state['gameStarted'])
                            <button class="action-button primary" @click="startGame()">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                                </svg>
                                Start Game
                            </button>
                        @elseif($state['gameOver'])
                            <button class="action-button primary" @click="newGame()">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"/>
                                </svg>
                                New Game
                            </button>
                        @else
                            <div class="control-buttons">
                                <button class="action-button" @click="togglePause()">
                                    @if($state['paused'])
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                                        </svg>
                                        Resume
                                    @else
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z"/>
                                        </svg>
                                        Pause
                                    @endif
                                </button>
                                
                                <button class="action-button danger" @click="newGame()">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"/>
                                    </svg>
                                    New Game
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Next Piece Preview -->
                    @if($state['gameStarted'] && !$state['gameOver'])
                        <div class="next-piece">
                            <h4>Next Piece</h4>
                            <div class="next-piece-preview">
                                @if(isset($state['nextPiece']))
                                    <div class="piece-preview tetris-{{ $state['nextPiece']['color'] }}">
                                        @foreach($state['nextPiece']['shape'] as $row)
                                            <div class="piece-row">
                                                @foreach($row as $cell)
                                                    <div class="piece-cell @if($cell === '#') piece-filled @endif"></div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Keyboard Controls -->
                    <div class="keyboard-controls">
                        <h4>Controls</h4>
                        <div class="control-grid">
                            <div class="control-item">
                                <kbd>←</kbd> <span>Move Left</span>
                            </div>
                            <div class="control-item">
                                <kbd>→</kbd> <span>Move Right</span>
                            </div>
                            <div class="control-item">
                                <kbd>↓</kbd> <span>Soft Drop</span>
                            </div>
                            <div class="control-item">
                                <kbd>↑</kbd> <span>Rotate</span>
                            </div>
                            <div class="control-item">
                                <kbd>Space</kbd> <span>Hard Drop</span>
                            </div>
                            <div class="control-item">
                                <kbd>P</kbd> <span>Pause</span>
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
                            <li>Arrange falling blocks (tetrominoes) to create complete horizontal lines</li>
                            <li>Complete lines disappear and award points</li>
                            <li>Use rotation and horizontal movement to position blocks optimally</li>
                            <li>Game ends when blocks reach the top of the playing field</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Scoring</h4>
                        <ul>
                            <li>Single line: 100 points</li>
                            <li>Double lines: 300 points</li>
                            <li>Triple lines: 500 points</li>
                            <li>Tetris (4 lines): 800 points</li>
                            <li>Soft drop: 1 point per cell</li>
                            <li>Level increases every 10 lines cleared</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </x-game-layout>

    <style>
        /* Tetris Grid Styles */
        .tetris-game-board {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 2rem;
            align-items: start;
        }

        .tetris-grid-container {
            display: flex;
            justify-content: center;
        }

        .tetris-grid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            grid-template-rows: repeat(20, 1fr);
            gap: 1px;
            background: var(--game-border);
            border: 2px solid var(--game-border);
            border-radius: 0.5rem;
            padding: 0.5rem;
            width: 300px;
            height: 600px;
        }

        .tetris-cell {
            background: var(--game-bg-primary);
            border-radius: 2px;
            transition: all 0.2s ease;
        }

        .tetris-cell-filled {
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .tetris-current-piece {
            border: 2px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }

        /* Tetromino Colors */
        .tetris-cyan { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .tetris-yellow { background: linear-gradient(135deg, #eab308, #ca8a04); }
        .tetris-purple { background: linear-gradient(135deg, #a855f7, #9333ea); }
        .tetris-green { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .tetris-red { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .tetris-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .tetris-orange { background: linear-gradient(135deg, #f97316, #ea580c); }

        /* Next Piece Preview */
        .next-piece-preview {
            display: flex;
            justify-content: center;
            margin-top: 1rem;
        }

        .piece-preview {
            display: grid;
            gap: 2px;
            padding: 1rem;
            background: var(--game-bg-primary);
            border-radius: 0.5rem;
            border: 1px solid var(--game-border);
        }

        .piece-row {
            display: flex;
            gap: 2px;
        }

        .piece-cell {
            width: 20px;
            height: 20px;
            background: transparent;
            border-radius: 2px;
        }

        .piece-cell.piece-filled {
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Keyboard Controls */
        .control-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .control-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        kbd {
            background: var(--game-bg-tertiary);
            border: 1px solid var(--game-border);
            border-radius: 0.25rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--game-text-primary);
        }

        /* Control Buttons */
        .control-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .tetris-game-board {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .tetris-grid {
                width: 250px;
                height: 500px;
            }
            
            .control-buttons {
                flex-direction: column;
            }
        }
    </style>
</div>
