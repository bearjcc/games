<div x-data="{
    timer: 0,
    gameStarted: false,
    gameOver: false,
    gameWon: false,
    selectedDiceIndices: [],
    turnScore: 0,
    currentPlayer: 0,
    
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
    
    rollDice() {
        if (this.gameOver) return;
        
        $wire.rollDice();
        this.turnScore = $wire.turnScore;
    },
    
    toggleDiceSelection(index) {
        if (this.selectedDiceIndices.includes(index)) {
            this.selectedDiceIndices = this.selectedDiceIndices.filter(i => i !== index);
        } else {
            this.selectedDiceIndices.push(index);
        }
    },
    
    confirmSelection() {
        if (this.selectedDiceIndices.length > 0) {
            $wire.selectDice(this.selectedDiceIndices);
            this.selectedDiceIndices = [];
        }
    },
    
    bankPoints() {
        if (this.gameOver) return;
        
        $wire.bankPoints();
        this.turnScore = 0;
        this.currentPlayer = $wire.currentPlayer;
    },
    
    clearSelection() {
        this.selectedDiceIndices = [];
    }
}">
    <x-game-styles />
    
    <x-game-layout title="Farkle" 
                   description="Classic dice game of risk and reward! Roll dice to score points, but beware - roll without scoring and you Farkle!"
                   difficulty="Easy" 
                   estimatedDuration="15-45 minutes">
        
        <!-- Game Status -->
        <div class="game-status">
            @if($state['gameOver'])
                @if($state['winner'] === 0)
                    <div class="winner-indicator">
                        🎉 You Won! Final Score: {{ $this->getScore() }}
                        <div class="text-sm mt-2">
                            Time: <span x-text="formatTime(timer)"></span> |
                            Farkles: {{ $state['farkleCount'] }}
                        </div>
                    </div>
                @else
                    <div class="winner-indicator" style="color: var(--game-error);">
                        💔 {{ $state['players'][$state['winner']]['name'] }} Won!
                        <div class="text-sm mt-2">
                            Your Score: {{ $this->getScore() }} |
                            Time: <span x-text="formatTime(timer)"></span>
                        </div>
                    </div>
                @endif
            @elseif($state['gameStarted'])
                <div class="player-indicator">
                    @if($state['currentPlayer'] === 0)
                        @if($state['turnPhase'] === 'rolling')
                            Your Turn! Roll the dice to start scoring.
                        @elseif($state['turnPhase'] === 'selecting')
                            Select dice to score points, then bank or roll again.
                        @elseif($state['turnPhase'] === 'banking')
                            Great roll! Bank your points or risk rolling again.
                        @endif
                    @else
                        {{ $state['players'][$state['currentPlayer']]['name'] }}'s Turn
                    @endif
                    <div class="text-sm mt-2">
                        Turn Score: <span class="score-highlight">{{ $this->getTurnScore() }}</span> |
                        Your Score: {{ $this->getScore() }} |
                        Time: <span x-text="formatTime(timer)"></span> |
                        Consecutive Rolls: {{ $state['consecutiveRolls'] }}
                    </div>
                </div>
            @else
                <div class="player-indicator">
                    Ready to Play Farkle!
                    <div class="text-sm mt-2">
                        First player to reach 10,000 points wins
                    </div>
                </div>
            @endif
        </div>

        <!-- Game Board Container -->
        <div class="game-board-container">
            <div class="game-board farkle-game-board">
                <!-- Player Scores -->
                <div class="scores-area">
                    <h4>Player Scores</h4>
                    <div class="player-scores">
                        @foreach($state['players'] as $index => $player)
                            <div class="player-score {{ $index === $state['currentPlayer'] ? 'current-player' : '' }}">
                                <div class="player-name">{{ $player['name'] }}</div>
                                <div class="player-points">{{ $player['score'] }}</div>
                                @if($index === $state['currentPlayer'])
                                    <div class="turn-indicator">Current Turn</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Dice Area -->
                <div class="dice-area" data-game-id="farkle">
                    <h4>Dice</h4>
                    <div class="dice-container">
                        @if(!empty($this->getDice()))
                            @foreach($this->getDice() as $index => $value)
                                <div class="die-wrapper">
                                    <div class="die {{ in_array($index, $selectedDiceIndices) ? 'selected' : '' }}"
                                         @click="toggleDiceSelection({{ $index }})"
                                         data-dice-index="{{ $index }}"
                                         data-dice-value="{{ $value }}">
                                        <img src="{{ $this->getDiceImage($value) }}" alt="Die {{ $value }}">
                                    </div>
                                    @if($state['turnPhase'] === 'selecting')
                                        <div class="dice-value">{{ $value }}</div>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <div class="no-dice">
                                <div class="no-dice-text">No dice to roll</div>
                            </div>
                        @endif
                    </div>
                    
                    @if($state['hotDice'])
                        <div class="hot-dice-indicator">
                            🔥 Hot Dice! +1000 bonus points!
                        </div>
                    @endif
                </div>

                <!-- Scoring Combinations -->
                @if($state['turnPhase'] === 'selecting' && !empty($this->getScoringCombinations()))
                    <div class="scoring-area">
                        <h4>Available Combinations</h4>
                        <div class="scoring-combinations">
                            @foreach($this->getScoringCombinations() as $combo)
                                <div class="scoring-combo">
                                    <div class="combo-type">{{ ucwords(str_replace('_', ' ', $combo['type'])) }}</div>
                                    <div class="combo-score">{{ $this->calculateSelectionScore($combo['dice']) }} points</div>
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
                    <button class="action-button primary" @click="newGame()">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"/>
                        </svg>
                        New Game
                    </button>
                @else
                    <!-- Game Actions -->
                    <div class="game-actions">
                        @if($state['currentPlayer'] === 0)
                            <div class="action-buttons">
                                @if($this->canRollDice())
                                    <button class="action-button roll-dice-button" @click="rollDice()">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"/>
                                        </svg>
                                        Roll Dice
                                    </button>
                                @endif
                                
                                @if($this->canSelectDice())
                                    <button class="action-button" @click="confirmSelection()" 
                                            :disabled="selectedDiceIndices.length === 0">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                        </svg>
                                        Select Dice
                                    </button>
                                @endif
                                
                                @if($this->canBankPoints())
                                    <button class="action-button" @click="bankPoints()">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 6a2 2 0 114 0 2 2 0 01-4 0zm6 0a2 2 0 114 0 2 2 0 01-4 0z"/>
                                        </svg>
                                        Bank Points
                                    </button>
                                @endif
                                
                                <button class="action-button" @click="$wire.getHint()">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"/>
                                    </svg>
                                    Hint
                                </button>
                            </div>
                        @else
                            <div class="waiting-message">
                                <h4>Waiting for {{ $state['players'][$state['currentPlayer']]['name'] }}...</h4>
                                <div class="loading-spinner">
                                    <div class="spinner"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Game Stats -->
            <div class="game-stats">
                <h4>Game Stats</h4>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-label">Your Score:</span>
                        <span class="stat-value">{{ $this->getScore() }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Turn Score:</span>
                        <span class="stat-value">{{ $this->getTurnScore() }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Farkles:</span>
                        <span class="stat-value">{{ $state['farkleCount'] }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Rolls:</span>
                        <span class="stat-value">{{ $state['consecutiveRolls'] }}</span>
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
                        @if(isset($hint['action']))
                            <button class="action-button" @click="$wire.applyHint(@js($hint))">
                                {{ ucfirst(str_replace('_', ' ', $hint['action'])) }}
                            </button>
                        @elseif(isset($hint['combination']))
                            <button class="action-button" @click="$wire.applyHint(@js($hint))">
                                Select {{ ucwords(str_replace('_', ' ', $hint['combination']['type'])) }}
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
                            <li>Roll dice to score points with various combinations</li>
                            <li>Select scoring dice to keep and add to your turn score</li>
                            <li>Decide when to bank your points or risk rolling again</li>
                            <li>Roll without scoring and you Farkle (lose turn points)</li>
                            <li>First player to reach 10,000 points wins</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Scoring Combinations</h4>
                        <ul>
                            <li>1s: 100 points each</li>
                            <li>5s: 50 points each</li>
                            <li>Three of a kind: 200-1000 points</li>
                            <li>Four of a kind: 1,000 points</li>
                            <li>Five of a kind: 2,000 points</li>
                            <li>Six of a kind: 3,000 points</li>
                            <li>Straight (1-6): 1,500 points</li>
                            <li>Three pairs: 1,500 points</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </x-game-layout>

    <style>
        /* Farkle Game Styles */
        .farkle-game-board {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 2rem;
            align-items: start;
        }

        .scores-area {
            text-align: center;
        }

        .scores-area h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
        }

        .player-scores {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .player-score {
            background: var(--game-bg-secondary);
            border: 2px solid var(--game-border);
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
            transition: var(--game-transition);
        }

        .player-score.current-player {
            border-color: var(--game-accent-primary);
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.3);
        }

        .player-name {
            font-weight: 600;
            color: var(--game-text-primary);
            margin-bottom: 0.5rem;
        }

        .player-points {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--game-accent-primary);
        }

        .turn-indicator {
            font-size: 0.75rem;
            color: var(--game-accent-primary);
            margin-top: 0.25rem;
        }

        .dice-area {
            text-align: center;
        }

        .dice-area h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
        }

        .dice-container {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .die-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }

        .die {
            width: 60px;
            height: 60px;
            background: white;
            border: 2px solid #ccc;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--game-transition);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .die:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .die.selected {
            border-color: var(--game-success);
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.4);
            transform: translateY(-4px);
        }

        .die img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .dice-value {
            font-size: 0.75rem;
            font-weight: bold;
            color: var(--game-text-primary);
        }

        .no-dice {
            padding: 2rem;
            background: var(--game-bg-primary);
            border: 2px dashed var(--game-border);
            border-radius: 0.5rem;
            opacity: 0.5;
        }

        .no-dice-text {
            color: var(--game-text-secondary);
        }

        .hot-dice-indicator {
            background: var(--game-success);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: bold;
            margin-top: 1rem;
            animation: pulse 2s infinite;
        }

        .scoring-area {
            text-align: center;
        }

        .scoring-area h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
        }

        .scoring-combinations {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .scoring-combo {
            background: var(--game-bg-secondary);
            border: 1px solid var(--game-border);
            border-radius: 0.375rem;
            padding: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .combo-type {
            font-weight: 600;
            color: var(--game-text-primary);
        }

        .combo-score {
            font-weight: bold;
            color: var(--game-accent-primary);
        }

        .score-highlight {
            color: var(--game-success);
            font-weight: bold;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .farkle-game-board {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .dice-container {
                gap: 0.25rem;
            }
            
            .die {
                width: 50px;
                height: 50px;
            }
            
            .player-scores {
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .player-score {
                flex: 1;
                min-width: 120px;
            }
        }

        @media (max-width: 480px) {
            .die {
                width: 45px;
                height: 45px;
            }
            
            .player-scores {
                flex-direction: column;
            }
        }
    </style>

    <!-- Enhanced Animation Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Listen for dice rolled events
            Livewire.on('dice-rolled', function(data) {
                if (window.DiceAnimations && data.values) {
                    const diceElements = document.querySelectorAll('[data-game-id="farkle"] .die');
                    const finalValues = data.values;
                    
                    // Trigger enhanced dice rolling animation
                    window.DiceAnimations.rollMultipleDice(
                        Array.from(diceElements), 
                        finalValues,
                        {
                            duration: 1.5,
                            intensity: 'medium',
                            onComplete: function() {
                                console.log('Farkle dice rolling animation completed');
                            }
                        }
                    );
                }
            });

            // Add dice selection animations
            document.querySelectorAll('[data-game-id="farkle"] .die').forEach(die => {
                die.addEventListener('click', function() {
                    if (window.DiceAnimations) {
                        const isSelected = this.classList.contains('selected');
                        window.DiceAnimations.selectDie(this, !isSelected);
                    }
                });
            });

            // Add hover effects
            document.querySelectorAll('[data-game-id="farkle"] .die').forEach(die => {
                die.addEventListener('mouseenter', function() {
                    if (!this.classList.contains('selected')) {
                        this.style.transform = 'translateY(-2px) scale(1.05)';
                    }
                });
                
                die.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('selected')) {
                        this.style.transform = '';
                    }
                });
            });
        });
    </script>
</div>
