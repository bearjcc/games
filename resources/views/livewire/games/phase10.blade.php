<div x-data="{
    timer: 0,
    gameStarted: false,
    gameOver: false,
    gameWon: false,
    currentPlayer: 0,
    selectedCards: [],
    playerHands: [],
    discardPile: [],
    drawPile: [],
    currentPhase: 1,
    playerScores: [],
    playerPhases: [],
    
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
        this.selectedCards = [];
        this.playerHands = [];
        this.discardPile = [];
        this.drawPile = [];
        this.currentPhase = 1;
        this.playerScores = [];
        this.playerPhases = [];
        $wire.newGame();
    },
    
    drawFromDrawPile() {
        if (this.gameOver) return;
        
        $wire.drawFromDrawPile();
        this.playerHands = $wire.playerHands;
        this.drawPile = $wire.drawPile;
        this.currentPlayer = $wire.currentPlayer;
    },
    
    drawFromDiscardPile() {
        if (this.gameOver) return;
        
        $wire.drawFromDiscardPile();
        this.playerHands = $wire.playerHands;
        this.discardPile = $wire.discardPile;
        this.currentPlayer = $wire.currentPlayer;
    },
    
    discardCard(cardIndex) {
        if (this.gameOver) return;
        
        $wire.discardCard(cardIndex);
        this.playerHands = $wire.playerHands;
        this.discardPile = $wire.discardPile;
        this.currentPlayer = $wire.currentPlayer;
    },
    
    playPhase() {
        if (this.gameOver) return;
        
        $wire.playPhase();
        this.playerHands = $wire.playerHands;
        this.currentPhase = $wire.currentPhase;
        this.playerPhases = $wire.playerPhases;
        this.selectedCards = [];
    },
    
    goOut() {
        if (this.gameOver) return;
        
        $wire.goOut();
        this.gameOver = true;
        this.gameWon = $wire.gameWon;
        this.playerScores = $wire.playerScores;
    },
    
    toggleCardSelection(cardIndex) {
        if (this.selectedCards.includes(cardIndex)) {
            this.selectedCards = this.selectedCards.filter(i => i !== cardIndex);
        } else {
            this.selectedCards.push(cardIndex);
        }
    },
    
    clearSelection() {
        this.selectedCards = [];
    },
    
    getCardClass(card) {
        const classes = ['card'];
        
        if (card.type === 'wild') {
            classes.push('wild-card');
        } else if (card.type === 'skip') {
            classes.push('skip-card');
        } else {
            classes.push('number-card');
            classes.push(`color-${card.color}`);
        }
        
        return classes.join(' ');
    },
    
    getCardValue(card) {
        if (card.type === 'wild') {
            return 'W';
        } else if (card.type === 'skip') {
            return 'S';
        } else {
            return card.number;
        }
    }
}">
    <x-game-styles />
    
    <x-game-layout title="Phase 10" 
                   description="Classic rummy-style card game! Complete 10 phases by making sets and runs, then be the first to go out to win!"
                   difficulty="Medium" 
                   estimatedDuration="30-60 minutes">
        
        <!-- Game Status -->
        <div class="game-status">
            @if($state['gameOver'])
                @if($state['winner'] === 0)
                    <div class="winner-indicator">
                        🎉 You Won!
                        <div class="text-sm mt-2">
                            Final Score: {{ $this->getScore() }} |
                            Time: <span x-text="formatTime(timer)"></span>
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
                        @if($state['turnPhase'] === 'drawing')
                            Your Turn! Draw a card to begin.
                        @elseif($state['turnPhase'] === 'playing')
                            Select cards to play or discard to end your turn.
                        @endif
                    @else
                        {{ $state['players'][$state['currentPlayer']]['name'] }}'s Turn
                    @endif
                    <div class="text-sm mt-2">
                        Phase: {{ $state['players'][$state['currentPlayer']]['phase'] }}/10 |
                        Score: {{ $this->getScore() }} |
                        Time: <span x-text="formatTime(timer)"></span>
                    </div>
                </div>
            @else
                <div class="player-indicator">
                    Ready to Play Phase 10!
                    <div class="text-sm mt-2">
                        Complete 10 phases by making sets and runs
                    </div>
                </div>
            @endif
        </div>

        <!-- Game Board Container -->
        <div class="game-board-container">
            <div class="game-board phase10-game-board">
                <!-- Player Scores and Phases -->
                <div class="player-info">
                    <h4>Player Progress</h4>
                    <div class="player-progress">
                        @foreach($state['players'] as $index => $player)
                            <div class="player-progress-item {{ $index === $state['currentPlayer'] ? 'current-player' : '' }}">
                                <div class="player-name">{{ $player['name'] }}</div>
                                <div class="player-phase">Phase {{ $player['phase'] }}/10</div>
                                <div class="player-score">Score: {{ $player['score'] }}</div>
                                @if($index === $state['currentPlayer'])
                                    <div class="turn-indicator">Current Turn</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Current Phase Requirements -->
                @if($state['gameStarted'] && !$state['gameOver'])
                    <div class="phase-requirements">
                        <h4>Phase {{ $state['players'][$state['currentPlayer']]['phase'] }} Requirements</h4>
                        <div class="phase-description">
                            {{ $this->getPhaseRequirements()['description'] }}
                        </div>
                    </div>
                @endif

                <!-- Discard Pile -->
                @if($state['gameStarted'] && !empty($this->getDiscardPile()))
                    <div class="discard-pile">
                        <h4>Discard Pile</h4>
                        <div class="pile-container">
                            @php $topCard = end($this->getDiscardPile()); @endphp
                            <div class="card {{ $this->getCardClass($topCard) }}" 
                                 @click="drawFromDiscardPile()"
                                 title="Click to draw from discard pile">
                                <div class="card-value">{{ $this->getCardValue($topCard) }}</div>
                                <div class="card-suit">{{ $topCard['color'] }}</div>
                            </div>
                            <div class="pile-count">{{ count($this->getDiscardPile()) }} cards</div>
                        </div>
                    </div>
                @endif

                <!-- Draw Pile -->
                @if($state['gameStarted'])
                    <div class="draw-pile">
                        <h4>Draw Pile</h4>
                        <div class="pile-container">
                            <div class="card face-down" 
                                 @click="drawFromDrawPile()"
                                 title="Click to draw from draw pile">
                                <div class="card-back">?</div>
                            </div>
                            <div class="pile-count">{{ count($this->getDrawPile()) }} cards</div>
                        </div>
                    </div>
                @endif

                <!-- Player Hand -->
                @if($state['gameStarted'] && $state['currentPlayer'] === 0)
                    <div class="player-hand">
                        <h4>Your Hand</h4>
                        <div class="hand-container">
                            @foreach($this->getPlayerHands()[0] as $index => $card)
                                <div class="card {{ $this->getCardClass($card) }} {{ in_array($index, $selectedCards) ? 'selected' : '' }}"
                                     @click="toggleCardSelection({{ $index }})"
                                     title="Click to select/deselect">
                                    <div class="card-value">{{ $this->getCardValue($card) }}</div>
                                    <div class="card-suit">{{ $card['color'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        
                        @if($state['turnPhase'] === 'playing')
                            <div class="hand-actions">
                                @if($this->canPlayPhase())
                                    <button class="action-button" @click="playPhase()" 
                                            :disabled="selectedCards.length === 0">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                                        </svg>
                                        Play Phase
                                    </button>
                                @endif
                                
                                @if($this->canGoOut())
                                    <button class="action-button" @click="goOut()">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                                        </svg>
                                        Go Out
                                    </button>
                                @endif
                                
                                <button class="action-button secondary" @click="clearSelection()">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                    </svg>
                                    Clear Selection
                                </button>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Opponent Hands -->
                @if($state['gameStarted'] && $state['currentPlayer'] !== 0)
                    <div class="opponent-hands">
                        <h4>Opponent Hands</h4>
                        @foreach($state['players'] as $index => $player)
                            @if($index !== 0)
                                <div class="opponent-hand">
                                    <div class="opponent-name">{{ $player['name'] }}</div>
                                    <div class="opponent-cards">
                                        @for($i = 0; $i < count($player['hand']); $i++)
                                            <div class="card face-down">
                                                <div class="card-back">?</div>
                                            </div>
                                        @endfor
                                    </div>
                                    <div class="opponent-info">
                                        Phase {{ $player['phase'] }}/10 | Score: {{ $player['score'] }}
                                    </div>
                                </div>
                            @endif
                        @endforeach
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
                    <div class="game-actions">
                        @if($state['currentPlayer'] === 0)
                            @if($state['turnPhase'] === 'drawing')
                                <div class="draw-actions">
                                    <button class="action-button" @click="drawFromDrawPile()">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                                        </svg>
                                        Draw from Draw Pile
                                    </button>
                                    @if(!empty($this->getDiscardPile()))
                                        <button class="action-button" @click="drawFromDiscardPile()">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                                        </svg>
                                        Draw from Discard Pile
                                    </button>
                                    @endif
                                </div>
                            @elseif($state['turnPhase'] === 'playing')
                                <div class="play-actions">
                                    <button class="action-button" @click="$wire.getHint()">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"/>
                                        </svg>
                                        Get Hint
                                    </button>
                                </div>
                            @endif
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
                        <span class="stat-label">Current Phase:</span>
                        <span class="stat-value">{{ $state['players'][0]['phase'] }}/10</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Cards in Hand:</span>
                        <span class="stat-value">{{ count($this->getPlayerHands()[0]) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Draw Pile:</span>
                        <span class="stat-value">{{ count($this->getDrawPile()) }}</span>
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
                        @if(isset($hint['requirements']))
                            <div class="hint-requirements">
                                <strong>Phase {{ $hint['phase'] }}:</strong> {{ $hint['requirements']['description'] }}
                            </div>
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
                            <li>Complete 10 phases by making sets and runs</li>
                            <li>Draw a card from draw pile or discard pile</li>
                            <li>Try to complete your current phase</li>
                            <li>Discard one card to end your turn</li>
                            <li>Be the first to go out (empty hand) to win</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Phase Requirements</h4>
                        <ul>
                            <li>Phase 1: 2 sets of 3</li>
                            <li>Phase 2: 1 set of 3 + 1 run of 4</li>
                            <li>Phase 3: 1 set of 4 + 1 run of 4</li>
                            <li>Phase 4: 1 run of 7</li>
                            <li>Phase 5: 1 run of 8</li>
                            <li>Phase 6: 1 run of 9</li>
                            <li>Phase 7: 2 sets of 4</li>
                            <li>Phase 8: 7 cards of one color</li>
                            <li>Phase 9: 1 set of 5 + 1 set of 2</li>
                            <li>Phase 10: 1 set of 5 + 1 set of 3</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Card Values</h4>
                        <ul>
                            <li>Number cards: Face value (1-12)</li>
                            <li>Skip cards: 15 points</li>
                            <li>Wild cards: 25 points</li>
                            <li>Skip cards skip your next turn</li>
                            <li>Wild cards can substitute any card</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </x-game-layout>

    <style>
        /* Phase 10 Game Styles */
        .phase10-game-board {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 2rem;
            align-items: start;
        }

        .player-info {
            text-align: center;
        }

        .player-info h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
        }

        .player-progress {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .player-progress-item {
            background: var(--game-bg-secondary);
            border: 2px solid var(--game-border);
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
            transition: var(--game-transition);
        }

        .player-progress-item.current-player {
            border-color: var(--game-accent-primary);
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.3);
        }

        .player-name {
            font-weight: 600;
            color: var(--game-text-primary);
            margin-bottom: 0.5rem;
        }

        .player-phase {
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--game-accent-primary);
            margin-bottom: 0.25rem;
        }

        .player-score {
            font-size: 0.9rem;
            color: var(--game-text-secondary);
        }

        .turn-indicator {
            font-size: 0.75rem;
            color: var(--game-accent-primary);
            margin-top: 0.25rem;
        }

        .phase-requirements {
            text-align: center;
            grid-column: 1 / -1;
        }

        .phase-requirements h4 {
            margin-bottom: 0.5rem;
            color: var(--game-text-primary);
        }

        .phase-description {
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--game-accent-primary);
            background: var(--game-bg-secondary);
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--game-border);
        }

        .discard-pile, .draw-pile {
            text-align: center;
        }

        .discard-pile h4, .draw-pile h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
        }

        .pile-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .pile-count {
            font-size: 0.8rem;
            color: var(--game-text-secondary);
        }

        .player-hand {
            text-align: center;
            grid-column: 1 / -1;
        }

        .player-hand h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
        }

        .hand-container {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .hand-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .opponent-hands {
            text-align: center;
        }

        .opponent-hands h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
        }

        .opponent-hand {
            margin-bottom: 1rem;
            padding: 1rem;
            background: var(--game-bg-secondary);
            border-radius: 0.5rem;
            border: 1px solid var(--game-border);
        }

        .opponent-name {
            font-weight: bold;
            color: var(--game-text-primary);
            margin-bottom: 0.5rem;
        }

        .opponent-cards {
            display: flex;
            justify-content: center;
            gap: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .opponent-info {
            font-size: 0.8rem;
            color: var(--game-text-secondary);
        }

        .card {
            width: 60px;
            height: 90px;
            border: 2px solid var(--game-border);
            border-radius: 0.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--game-transition);
            background: white;
            position: relative;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .card.selected {
            border-color: var(--game-success);
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.4);
            transform: translateY(-10px);
        }

        .card.face-down {
            background: var(--game-bg-secondary);
            border-color: var(--game-border);
        }

        .card.wild-card {
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            color: white;
        }

        .card.skip-card {
            background: linear-gradient(45deg, #ffa726, #ff7043);
            color: white;
        }

        .card.color-red {
            border-color: #ef4444;
        }

        .card.color-blue {
            border-color: #3b82f6;
        }

        .card.color-green {
            border-color: #10b981;
        }

        .card.color-yellow {
            border-color: #f59e0b;
        }

        .card-value {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .card-suit {
            font-size: 0.8rem;
            text-transform: capitalize;
        }

        .card-back {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--game-text-secondary);
        }

        .draw-actions, .play-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .phase10-game-board {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .hand-container {
                gap: 0.25rem;
            }
            
            .card {
                width: 50px;
                height: 75px;
            }
            
            .card-value {
                font-size: 1rem;
            }
            
            .card-suit {
                font-size: 0.7rem;
            }
            
            .player-progress {
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .player-progress-item {
                flex: 1;
                min-width: 120px;
            }
            
            .hand-actions, .draw-actions, .play-actions {
                flex-direction: column;
                align-items: center;
            }
        }

        @media (max-width: 480px) {
            .card {
                width: 45px;
                height: 68px;
            }
            
            .card-value {
                font-size: 0.9rem;
            }
            
            .card-suit {
                font-size: 0.6rem;
            }
            
            .player-progress {
                flex-direction: column;
            }
        }
    </style>
</div>
