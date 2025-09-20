<div x-data="{
    timer: 0,
    gameStarted: false,
    gameOver: false,
    gameWon: false,
    gameLost: false,
    currentGuess: [],
    guesses: [],
    feedback: [],
    remainingGuesses: 10,
    difficulty: 'medium',
    availableColors: [],
    secretCode: [],
    
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
        this.gameLost = false;
        this.timer = 0;
        $wire.startGame();
    },
    
    newGame(difficulty = 'medium') {
        this.gameStarted = false;
        this.gameOver = false;
        this.gameWon = false;
        this.gameLost = false;
        this.timer = 0;
        this.currentGuess = [];
        this.guesses = [];
        this.feedback = [];
        this.difficulty = difficulty;
        $wire.newGame(difficulty);
    },
    
    selectColor(position, color) {
        if (this.gameOver) return;
        
        $wire.selectColor(position, color);
        this.currentGuess = $wire.currentGuess;
    },
    
    submitGuess() {
        if (this.gameOver) return;
        
        $wire.submitGuess();
        this.guesses = $wire.guesses;
        this.feedback = $wire.feedback;
        this.currentGuess = [];
        this.remainingGuesses = $wire.remainingGuesses;
        
        if ($wire.gameWon) {
            this.gameWon = true;
            this.gameOver = true;
        } else if ($wire.gameLost) {
            this.gameLost = true;
            this.gameOver = true;
        }
    },
    
    clearGuess() {
        if (this.gameOver) return;
        
        $wire.clearGuess();
        this.currentGuess = [];
    },
    
    getColorClass(color) {
        const colorMap = {
            'red': 'bg-red-500',
            'blue': 'bg-blue-500',
            'green': 'bg-green-500',
            'yellow': 'bg-yellow-400',
            'orange': 'bg-orange-500',
            'purple': 'bg-purple-500',
            'pink': 'bg-pink-500',
            'cyan': 'bg-cyan-500',
            'brown': 'bg-amber-800',
            'gray': 'bg-gray-500',
            'lime': 'bg-lime-500',
            'navy': 'bg-blue-800'
        };
        return colorMap[color] || 'bg-gray-300';
    },
    
    getFeedbackClass(type) {
        const classMap = {
            'black': 'bg-black',
            'white': 'bg-white border-2 border-gray-400',
            'none': 'bg-gray-200'
        };
        return classMap[type] || 'bg-gray-200';
    }
}">
    <x-game-styles />
    
    <x-game-layout title="Mastermind" 
                   description="Classic code-breaking puzzle! Crack the secret code by deducing the correct sequence of colors using logic and deduction."
                   difficulty="Medium" 
                   estimatedDuration="5-15 minutes">
        
        <!-- Game Status -->
        <div class="game-status">
            @if($state['gameOver'])
                @if($state['gameWon'])
                    <div class="winner-indicator">
                        🎉 You Cracked the Code!
                        <div class="text-sm mt-2">
                            Attempts: {{ $state['currentAttempt'] }}/{{ $state['maxGuesses'] }} |
                            Score: {{ $this->getScore() }} |
                            Time: <span x-text="formatTime(timer)"></span>
                        </div>
                    </div>
                @else
                    <div class="winner-indicator" style="color: var(--game-error);">
                        💔 Game Over - Code Not Cracked
                        <div class="text-sm mt-2">
                            The secret code was: 
                            @foreach($this->getSecretCode() as $color)
                                <span class="color-peg {{ $this->getColorClass($color) }}"></span>
                            @endforeach
                        </div>
                    </div>
                @endif
            @elseif($state['gameStarted'])
                <div class="player-indicator">
                    @if($state['guessPhase'] === 'selecting')
                        Select 4 colors for your guess
                    @elseif($state['guessPhase'] === 'submitting')
                        Submit your guess to get feedback
                    @endif
                    <div class="text-sm mt-2">
                        Attempt: {{ $state['currentAttempt'] + 1 }}/{{ $state['maxGuesses'] }} |
                        Remaining: {{ $this->getRemainingGuesses() }} |
                        Difficulty: {{ ucfirst($this->getDifficulty()) }} |
                        Time: <span x-text="formatTime(timer)"></span>
                    </div>
                </div>
            @else
                <div class="player-indicator">
                    Ready to Crack the Code!
                    <div class="text-sm mt-2">
                        Select difficulty and start the game
                    </div>
                </div>
            @endif
        </div>

        <!-- Game Board Container -->
        <div class="game-board-container">
            <div class="game-board mastermind-game-board">
                <!-- Difficulty Selection -->
                @if(!$state['gameStarted'])
                    <div class="difficulty-selection">
                        <h4>Select Difficulty</h4>
                        <div class="difficulty-options">
                            <button class="difficulty-btn {{ $selectedDifficulty === 'easy' ? 'active' : '' }}" 
                                    @click="newGame('easy')">
                                <div class="difficulty-name">Easy</div>
                                <div class="difficulty-details">6 colors, 10 guesses</div>
                            </button>
                            <button class="difficulty-btn {{ $selectedDifficulty === 'medium' ? 'active' : '' }}" 
                                    @click="newGame('medium')">
                                <div class="difficulty-name">Medium</div>
                                <div class="difficulty-details">8 colors, 8 guesses</div>
                            </button>
                            <button class="difficulty-btn {{ $selectedDifficulty === 'hard' ? 'active' : '' }}" 
                                    @click="newGame('hard')">
                                <div class="difficulty-name">Hard</div>
                                <div class="difficulty-details">10 colors, 6 guesses</div>
                            </button>
                            <button class="difficulty-btn {{ $selectedDifficulty === 'expert' ? 'active' : '' }}" 
                                    @click="newGame('expert')">
                                <div class="difficulty-name">Expert</div>
                                <div class="difficulty-details">12 colors, 5 guesses</div>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Current Guess Area -->
                @if($state['gameStarted'] && !$state['gameOver'])
                    <div class="current-guess-area">
                        <h4>Your Guess</h4>
                        <div class="guess-row">
                            @for($i = 0; $i < 4; $i++)
                                <div class="guess-peg {{ isset($this->getCurrentGuess()[$i]) ? 'filled' : 'empty' }}"
                                     @click="selectColor({{ $i }}, '')">
                                    @if(isset($this->getCurrentGuess()[$i]))
                                        <div class="color-peg {{ $this->getColorClass($this->getCurrentGuess()[$i]) }}"></div>
                                    @else
                                        <div class="empty-peg">?</div>
                                    @endif
                                </div>
                            @endfor
                        </div>
                        
                        @if($this->canSubmitGuess())
                            <div class="guess-actions">
                                <button class="action-button" @click="submitGuess()">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                                    </svg>
                                    Submit Guess
                                </button>
                                <button class="action-button secondary" @click="clearGuess()">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                    </svg>
                                    Clear
                                </button>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Color Palette -->
                @if($state['gameStarted'] && !$state['gameOver'])
                    <div class="color-palette">
                        <h4>Available Colors</h4>
                        <div class="color-grid">
                            @foreach($this->getAvailableColors() as $color)
                                <button class="color-button {{ $this->getColorClass($color) }}"
                                        @click="selectColor(0, '{{ $color }}')"
                                        title="{{ ucfirst($color) }}">
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Guess History -->
                @if(!empty($this->getGuesses()))
                    <div class="guess-history">
                        <h4>Guess History</h4>
                        <div class="history-container">
                            @foreach($this->getGuesses() as $index => $guess)
                                <div class="history-row">
                                    <div class="guess-number">{{ $index + 1 }}</div>
                                    <div class="guess-pegs">
                                        @foreach($guess as $color)
                                            <div class="color-peg {{ $this->getColorClass($color) }}"></div>
                                        @endforeach
                                    </div>
                                    <div class="feedback-pegs">
                                        @if(isset($this->getFeedback()[$index]))
                                            @php $feedback = $this->getFeedback()[$index]; @endphp
                                            @for($i = 0; $i < $feedback['black']; $i++)
                                                <div class="feedback-peg {{ $this->getFeedbackClass('black') }}"></div>
                                            @endfor
                                            @for($i = 0; $i < $feedback['white']; $i++)
                                                <div class="feedback-peg {{ $this->getFeedbackClass('white') }}"></div>
                                            @endfor
                                            @for($i = 0; $i < $feedback['none']; $i++)
                                                <div class="feedback-peg {{ $this->getFeedbackClass('none') }}"></div>
                                            @endfor
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
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
                    <button class="action-button primary" @click="newGame(difficulty)">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"/>
                        </svg>
                        New Game
                    </button>
                @else
                    <div class="game-actions">
                        <button class="action-button" @click="$wire.getHint()">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"/>
                            </svg>
                            Get Hint
                        </button>
                    </div>
                @endif
            </div>

            <!-- Game Stats -->
            <div class="game-stats">
                <h4>Game Stats</h4>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-label">Difficulty:</span>
                        <span class="stat-value">{{ ucfirst($this->getDifficulty()) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Attempts:</span>
                        <span class="stat-value">{{ $state['currentAttempt'] }}/{{ $state['maxGuesses'] }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Remaining:</span>
                        <span class="stat-value">{{ $this->getRemainingGuesses() }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Score:</span>
                        <span class="stat-value">{{ $this->getScore() }}</span>
                    </div>
                </div>
            </div>

            <!-- Hint Display -->
            @if($showHint)
                <div class="hint-panel">
                    <h4>Hint</h4>
                    <div class="hint-content">
                        @php $hint = $this->getHintData(); @endphp
                        <p>{{ $hint['message'] }}</p>
                        @if(isset($hint['suggestion']))
                            <button class="action-button" @click="$wire.applyHint(@js($hint))">
                                Use Suggestion
                            </button>
                        @endif
                    </div>
                </div>
            @endif
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
                            <li>Crack the secret 4-color code in limited guesses</li>
                            <li>Select colors for your guess and submit</li>
                            <li>Get feedback: Black = correct color & position, White = correct color wrong position</li>
                            <li>Use logic and deduction to narrow down possibilities</li>
                            <li>Win by getting 4 black pegs (all colors correct)</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Feedback System</h4>
                        <ul>
                            <li><strong>Black Peg:</strong> Right color, right position</li>
                            <li><strong>White Peg:</strong> Right color, wrong position</li>
                            <li><strong>No Peg:</strong> Color not in the secret code</li>
                            <li>Feedback order: Black pegs first, then white pegs</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Strategy Tips</h4>
                        <ul>
                            <li>Start with diverse colors to gather information</li>
                            <li>Use process of elimination based on feedback</li>
                            <li>Keep track of which colors are confirmed</li>
                            <li>Pay attention to position clues from black pegs</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </x-game-layout>

    <style>
        /* Mastermind Game Styles */
        .mastermind-game-board {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 2rem;
            align-items: start;
        }

        .difficulty-selection {
            text-align: center;
            grid-column: 1 / -1;
        }

        .difficulty-selection h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
        }

        .difficulty-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .difficulty-btn {
            background: var(--game-bg-secondary);
            border: 2px solid var(--game-border);
            border-radius: 0.5rem;
            padding: 1rem;
            cursor: pointer;
            transition: var(--game-transition);
            text-align: center;
        }

        .difficulty-btn:hover {
            border-color: var(--game-accent-primary);
            transform: translateY(-2px);
        }

        .difficulty-btn.active {
            border-color: var(--game-accent-primary);
            background: var(--game-accent-primary);
            color: white;
        }

        .difficulty-name {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .difficulty-details {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .current-guess-area {
            text-align: center;
        }

        .current-guess-area h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
        }

        .guess-row {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .guess-peg {
            width: 50px;
            height: 50px;
            border: 2px solid var(--game-border);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--game-transition);
            background: var(--game-bg-secondary);
        }

        .guess-peg.empty {
            border-style: dashed;
        }

        .guess-peg.filled {
            border-color: var(--game-accent-primary);
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.3);
        }

        .guess-peg:hover {
            transform: scale(1.1);
        }

        .color-peg {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid rgba(0, 0, 0, 0.2);
        }

        .empty-peg {
            color: var(--game-text-secondary);
            font-weight: bold;
        }

        .guess-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .color-palette {
            text-align: center;
        }

        .color-palette h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
        }

        .color-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(40px, 1fr));
            gap: 0.5rem;
            max-width: 300px;
            margin: 0 auto;
        }

        .color-button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid rgba(0, 0, 0, 0.2);
            cursor: pointer;
            transition: var(--game-transition);
        }

        .color-button:hover {
            transform: scale(1.2);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
        }

        .guess-history {
            text-align: center;
        }

        .guess-history h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
        }

        .history-container {
            max-height: 400px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .history-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem;
            background: var(--game-bg-secondary);
            border-radius: 0.5rem;
            border: 1px solid var(--game-border);
        }

        .guess-number {
            font-weight: bold;
            color: var(--game-accent-primary);
            min-width: 30px;
        }

        .guess-pegs {
            display: flex;
            gap: 0.25rem;
        }

        .feedback-pegs {
            display: flex;
            gap: 0.25rem;
            margin-left: auto;
        }

        .feedback-peg {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, 0.2);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .mastermind-game-board {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .difficulty-options {
                grid-template-columns: 1fr;
            }
            
            .guess-row {
                gap: 0.25rem;
            }
            
            .guess-peg {
                width: 40px;
                height: 40px;
            }
            
            .color-peg {
                width: 30px;
                height: 30px;
            }
            
            .color-button {
                width: 35px;
                height: 35px;
            }
            
            .history-row {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .feedback-pegs {
                margin-left: 0;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .guess-peg {
                width: 35px;
                height: 35px;
            }
            
            .color-peg {
                width: 25px;
                height: 25px;
            }
            
            .color-button {
                width: 30px;
                height: 30px;
            }
        }
    </style>
</div>
