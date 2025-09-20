<div x-data="{
    timer: 0,
    gameStarted: false,
    gameOver: false,
    currentPlayer: 0,
    lastAsk: null,
    lastResponse: null,
    
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
        this.timer = 0;
        $wire.startGame();
    },
    
    newGame() {
        this.gameStarted = false;
        this.gameOver = false;
        this.timer = 0;
        $wire.newGame();
    },
    
    askForCard(targetPlayer, rank) {
        if (this.gameOver || this.currentPlayer !== 0) return;
        
        $wire.askForCard(targetPlayer, rank);
    }
}">
    <x-game-styles />
    
    <x-game-layout title="Go Fish" 
                   description="Classic card game! Ask other players for cards to make sets of four. Go fish when they don't have what you need!"
                   difficulty="Easy" 
                   estimatedDuration="10-30 minutes">
        
        <!-- Game Status -->
        <div class="game-status">
            @if($state['gameOver'])
                @if($state['winner'] === 0)
                    <div class="winner-indicator">
                        🎉 You Won! Sets: {{ count($state['players'][0]['sets']) }}
                        <div class="text-sm mt-2">
                            Score: {{ $state['players'][0]['score'] }} |
                            Time: <span x-text="formatTime(timer)"></span>
                        </div>
                    </div>
                @else
                    <div class="winner-indicator" style="color: var(--game-danger);">
                        💔 {{ $state['players'][$state['winner']]['name'] }} Won!
                        <div class="text-sm mt-2">
                            Your Sets: {{ count($state['players'][0]['sets']) }} |
                            Time: <span x-text="formatTime(timer)"></span>
                        </div>
                    </div>
                @endif
            @elseif($state['gameStarted'])
                <div class="player-indicator">
                    @if($state['currentPlayer'] === 0)
                        Your Turn! Ask for a card you have.
                    @else
                        {{ $state['players'][$state['currentPlayer']]['name'] }}'s Turn
                    @endif
                    <div class="text-sm mt-2">
                        Turn: {{ $state['turnCount'] + 1 }} |
                        Time: <span x-text="formatTime(timer)"></span> |
                        Deck: {{ count($state['deck']) }} cards
                    </div>
                </div>
            @else
                <div class="player-indicator">
                    Ready to Play Go Fish!
                    <div class="text-sm mt-2">
                        Collect sets of four cards of the same rank
                    </div>
                </div>
            @endif
        </div>

        <!-- Game Board Container -->
        <div class="game-board-container">
            <div class="game-board go-fish-game-board">
                <!-- Players and Sets -->
                <div class="players-container">
                    @foreach($state['players'] as $index => $player)
                        <div class="player {{ $index === $state['currentPlayer'] ? 'current-player' : '' }} 
                            {{ $player['isHuman'] ? 'human-player' : 'ai-player' }}">
                            
                            <div class="player-info">
                                <div class="player-name">{{ $player['name'] }}</div>
                                <div class="player-stats">
                                    <span class="stat">Cards: {{ count($player['hand']) }}</span>
                                    <span class="stat">Sets: {{ count($player['sets']) }}</span>
                                    <span class="stat">Score: {{ $player['score'] }}</span>
                                </div>
                            </div>

                            <!-- Player Cards -->
                            <div class="player-cards">
                                @if($player['isHuman'] || $state['gameOver'])
                                    @foreach($player['hand'] as $card)
                                        <div class="card {{ $card['suit'] }}">
                                            <div class="card-rank">{{ $card['rank'] }}</div>
                                            <div class="card-suit">{{ $this->getSuitSymbol($card['suit']) }}</div>
                                        </div>
                                    @endforeach
                                @else
                                    @for($i = 0; $i < count($player['hand']); $i++)
                                        <div class="card card-back"></div>
                                    @endfor
                                @endif
                            </div>

                            <!-- Player Sets -->
                            @if(!empty($player['sets']))
                                <div class="player-sets">
                                    <h5>Sets:</h5>
                                    <div class="sets-grid">
                                        @foreach($player['sets'] as $set)
                                            <div class="set">
                                                <div class="set-rank">{{ $set['rank'] }}</div>
                                                <div class="set-cards">
                                                    @foreach($set['cards'] as $card)
                                                        <div class="mini-card {{ $card['suit'] }}">
                                                            <div class="mini-rank">{{ $card['rank'] }}</div>
                                                            <div class="mini-suit">{{ $this->getSuitSymbol($card['suit']) }}</div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
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
                            <!-- Player Actions -->
                            @if($state['currentPlayer'] === 0)
                                <div class="player-actions">
                                    <h4>Ask For Cards</h4>
                                    <div class="ask-options">
                                        @foreach($this->getPossibleAsks() as $rank)
                                            <div class="rank-group">
                                                <h5>{{ $rank }}</h5>
                                                <div class="target-players">
                                                    @foreach($state['players'] as $index => $player)
                                                        @if($index !== 0)
                                                            <button class="action-button" 
                                                                    @click="askForCard({{ $index }}, '{{ $rank }}')">
                                                                Ask {{ $player['name'] }}
                                                            </button>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="waiting-message">
                                    <h4>Waiting for {{ $state['players'][$state['currentPlayer']]['name'] }}...</h4>
                                    <div class="loading-spinner">
                                        <div class="spinner"></div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>

                    <!-- Game Stats -->
                    <div class="game-stats">
                        <h4>Game Stats</h4>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-label">Your Cards:</span>
                                <span class="stat-value">{{ count($state['players'][0]['hand']) }}</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Your Sets:</span>
                                <span class="stat-value">{{ count($state['players'][0]['sets']) }}</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Your Score:</span>
                                <span class="stat-value">{{ $state['players'][0]['score'] }}</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Deck Cards:</span>
                                <span class="stat-value">{{ count($state['deck']) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Last Action -->
                    @if($state['lastAsk'])
                        <div class="last-action">
                            <h4>Last Action</h4>
                            <div class="action-details">
                                @if($state['lastResponse'] === 'has_card')
                                    <div class="action-success">
                                        ✅ {{ $state['players'][$state['lastAsk']['from']]['name'] }} asked {{ $state['players'][$state['lastAsk']['to']]['name'] }} for {{ $state['lastAsk']['rank'] }} and got it!
                                    </div>
                                @elseif($state['lastResponse'] === 'go_fish')
                                    <div class="action-info">
                                        🎣 {{ $state['players'][$state['lastAsk']['from']]['name'] }} asked {{ $state['players'][$state['lastAsk']['to']]['name'] }} for {{ $state['lastAsk']['rank'] }} - Go Fish!
                                    </div>
                                @elseif($state['lastResponse'] === 'go_fish_success')
                                    <div class="action-success">
                                        🎣 {{ $state['players'][$state['lastAsk']['from']]['name'] }} asked for {{ $state['lastAsk']['rank'] }} and got it from the deck!
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
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
                            <li>Each player starts with 7 cards (5 for 3+ players)</li>
                            <li>On your turn, ask another player for a specific rank</li>
                            <li>If they have that rank, they give you all cards of that rank</li>
                            <li>If they don't have it, they say "Go Fish!" and you draw from the deck</li>
                            <li>If you get a card you asked for, you get another turn</li>
                            <li>When you have four cards of the same rank, place them as a set</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Strategy</h4>
                        <ul>
                            <li>Pay attention to what cards other players ask for</li>
                            <li>Remember which cards have been played</li>
                            <li>Ask for cards you already have to increase your chances</li>
                            <li>Try to complete sets before opponents</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </x-game-layout>

    <style>
        /* Go Fish Game Styles */
        .go-fish-game-board {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 2rem;
            align-items: start;
        }

        .players-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .player {
            background: var(--game-bg-secondary);
            border: 2px solid var(--game-border);
            border-radius: 0.75rem;
            padding: 1.5rem;
            transition: var(--game-transition);
        }

        .player.current-player {
            border-color: var(--game-accent);
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }

        .player.human-player {
            border-color: var(--game-success);
        }

        .player-info {
            margin-bottom: 1rem;
            text-align: center;
        }

        .player-name {
            font-weight: 600;
            color: var(--game-text-primary);
            margin-bottom: 0.5rem;
            font-size: 1.125rem;
        }

        .player-stats {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .stat {
            font-size: 0.875rem;
            color: var(--game-text-secondary);
            background: var(--game-bg-primary);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            border: 1px solid var(--game-border);
        }

        .player-cards {
            display: flex;
            justify-content: center;
            gap: 0.25rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .player-sets {
            margin-top: 1rem;
        }

        .player-sets h5 {
            font-weight: 600;
            color: var(--game-text-primary);
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .sets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.5rem;
        }

        .set {
            background: var(--game-bg-primary);
            border: 1px solid var(--game-border);
            border-radius: 0.5rem;
            padding: 0.5rem;
            text-align: center;
        }

        .set-rank {
            font-weight: 600;
            color: var(--game-accent);
            margin-bottom: 0.25rem;
        }

        .set-cards {
            display: flex;
            justify-content: center;
            gap: 0.125rem;
        }

        .mini-card {
            width: 20px;
            height: 28px;
            background: white;
            border: 1px solid #ccc;
            border-radius: 0.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 0.125rem;
            font-size: 0.5rem;
            font-weight: 600;
        }

        .mini-card.hearts, .mini-card.diamonds {
            color: #dc2626;
        }

        .mini-card.clubs, .mini-card.spades {
            color: #1f2937;
        }

        .mini-rank {
            font-size: 0.5rem;
        }

        .mini-suit {
            font-size: 0.625rem;
            text-align: center;
        }

        /* Card Styles */
        .card {
            width: 60px;
            height: 84px;
            background: white;
            border: 1px solid #ccc;
            border-radius: 0.375rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: var(--game-transition);
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .card.hearts, .card.diamonds {
            color: #dc2626;
        }

        .card.clubs, .card.spades {
            color: #1f2937;
        }

        .card-back {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .card-rank {
            font-size: 0.875rem;
        }

        .card-suit {
            font-size: 1rem;
            text-align: center;
        }

        /* Player Actions */
        .ask-options {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }

        .rank-group {
            background: var(--game-bg-primary);
            border: 1px solid var(--game-border);
            border-radius: 0.5rem;
            padding: 1rem;
        }

        .rank-group h5 {
            font-weight: 600;
            color: var(--game-text-primary);
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .target-players {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .waiting-message {
            text-align: center;
            padding: 2rem;
        }

        .waiting-message h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
        }

        /* Last Action */
        .last-action {
            margin-top: 1.5rem;
        }

        .action-details {
            margin-top: 0.5rem;
            padding: 1rem;
            background: var(--game-bg-primary);
            border-radius: 0.5rem;
            border: 1px solid var(--game-border);
        }

        .action-success {
            color: var(--game-success);
            font-weight: 500;
        }

        .action-info {
            color: var(--game-text-secondary);
            font-weight: 500;
        }

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

        /* Responsive Design */
        @media (max-width: 768px) {
            .go-fish-game-board {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .players-container {
                grid-template-columns: 1fr;
            }
            
            .player-cards {
                justify-content: center;
            }
            
            .card {
                width: 50px;
                height: 70px;
                font-size: 0.625rem;
            }
            
            .target-players {
                flex-direction: column;
            }
        }
    </style>
</div>
