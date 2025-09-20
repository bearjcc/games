<div x-data="{
    timer: 0,
    gameStarted: false,
    gameOver: false,
    currentPlayer: 0,
    pot: 0,
    currentBet: 0,
    
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
    
    startHand() {
        this.gameStarted = true;
        this.gameOver = false;
        this.timer = 0;
        $wire.startHand();
    },
    
    newGame() {
        this.gameStarted = false;
        this.gameOver = false;
        this.timer = 0;
        $wire.newGame();
    },
    
    makeMove(action, amount = 0) {
        if (this.gameOver) return;
        
        if (amount > 0) {
            $wire.makeMove(action, amount);
        } else {
            $wire.makeMove(action);
        }
    }
}">
    <x-game-styles />
    
    <x-game-layout title="Texas Hold'em Poker" 
                   description="Classic poker game! Play against AI opponents, manage your chips, and master the art of bluffing and strategy."
                   difficulty="Hard" 
                   estimatedDuration="15-60 minutes">
        
        <!-- Game Status -->
        <div class="game-status">
            @if($state['gameOver'])
                @if($state['winner'] === 'player')
                    <div class="winner-indicator">
                        🎉 You Won! Hand: {{ $state['winningHand']['rank'] ?? 'Unknown' }}
                        <div class="text-sm mt-2">
                            Pot: ${{ $state['pot'] }} |
                            Time: <span x-text="formatTime(timer)"></span>
                        </div>
                    </div>
                @else
                    <div class="winner-indicator" style="color: var(--game-danger);">
                        💸 You Lost! {{ $state['players'][array_search($state['winner'], array_column($state['players'], 'id'))]['name'] }} Won
                        <div class="text-sm mt-2">
                            Pot: ${{ $state['pot'] }} |
                            Time: <span x-text="formatTime(timer)"></span>
                        </div>
                    </div>
                @endif
            @elseif($state['gameStarted'])
                <div class="player-indicator">
                    Phase: {{ ucfirst(str_replace('_', ' ', $state['gamePhase'])) }} |
                    Pot: ${{ $state['pot'] }} |
                    Current Bet: ${{ $state['currentBet'] }}
                    <div class="text-sm mt-2">
                        Time: <span x-text="formatTime(timer)"></span> |
                        Hand: {{ $state['handNumber'] }}
                    </div>
                </div>
            @else
                <div class="player-indicator">
                    Ready to Play Texas Hold'em!
                    <div class="text-sm mt-2">
                        Play against AI opponents with realistic betting
                    </div>
                </div>
            @endif
        </div>

        <!-- Game Board Container -->
        <div class="game-board-container">
            <div class="game-board poker-game-board">
                <!-- Poker Table -->
                @if($state['gameStarted'])
                    <div class="poker-table">
                        <!-- Community Cards -->
                        <div class="community-cards">
                            <h4>Community Cards</h4>
                            <div class="cards-container">
                                @foreach($state['communityCards'] as $card)
                                    <div class="card {{ $card['suit'] }}">
                                        <div class="card-rank">{{ $card['rank'] }}</div>
                                        <div class="card-suit">{{ $this->getSuitSymbol($card['suit']) }}</div>
                                    </div>
                                @endforeach
                                @for($i = count($state['communityCards']); $i < 5; $i++)
                                    <div class="card card-back"></div>
                                @endfor
                            </div>
                        </div>

                        <!-- Players -->
                        <div class="players-container">
                            @foreach($state['players'] as $index => $player)
                                <div class="player {{ $player['isHuman'] ? 'human-player' : 'ai-player' }} 
                                    {{ $index === $state['currentPlayer'] ? 'current-player' : '' }}
                                    {{ $player['folded'] ? 'folded' : '' }}">
                                    
                                    <div class="player-info">
                                        <div class="player-name">{{ $player['name'] }}</div>
                                        <div class="player-chips">${{ $player['chips'] }}</div>
                                        @if($player['currentBet'] > 0)
                                            <div class="player-bet">Bet: ${{ $player['currentBet'] }}</div>
                                        @endif
                                        @if($player['allIn'])
                                            <div class="player-status">ALL IN</div>
                                        @elseif($player['folded'])
                                            <div class="player-status">FOLDED</div>
                                        @endif
                                    </div>

                                    <!-- Player Cards -->
                                    <div class="player-cards">
                                        @if($player['isHuman'] || $state['gameOver'])
                                            @foreach($player['holeCards'] as $card)
                                                <div class="card {{ $card['suit'] }}">
                                                    <div class="card-rank">{{ $card['rank'] }}</div>
                                                    <div class="card-suit">{{ $this->getSuitSymbol($card['suit']) }}</div>
                                                </div>
                                            @endforeach
                                        @else
                                            @foreach($player['holeCards'] as $card)
                                                <div class="card card-back"></div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Game Controls Panel -->
                <div class="controls-panel fade-in">
                    <!-- Game Controls -->
                    <div class="game-controls">
                        <h4>Game Controls</h4>
                        @if(!$state['gameStarted'])
                            <button class="action-button primary" @click="startHand()">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                                </svg>
                                Start Hand
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
                            @if($state['currentPlayer'] === 0 && !$state['players'][0]['folded'] && !$state['players'][0]['allIn'])
                                <div class="player-actions">
                                    <h4>Your Actions</h4>
                                    <div class="action-buttons">
                                        @if($state['currentBet'] === $state['players'][0]['currentBet'])
                                            <button class="action-button" @click="makeMove('check')">
                                                Check
                                            </button>
                                        @else
                                            <button class="action-button" @click="makeMove('call')">
                                                Call ${{ $state['currentBet'] - $state['players'][0]['currentBet'] }}
                                            </button>
                                        @endif
                                        
                                        @if($state['currentBet'] === 0)
                                            <button class="action-button primary" @click="makeMove('bet', 20)">
                                                Bet $20
                                            </button>
                                        @else
                                            <button class="action-button primary" @click="makeMove('raise', $state['currentBet'] * 2)">
                                                Raise to ${{ $state['currentBet'] * 2 }}
                                            </button>
                                        @endif
                                        
                                        <button class="action-button danger" @click="makeMove('fold')">
                                            Fold
                                        </button>
                                        
                                        <button class="action-button success" @click="makeMove('all_in')">
                                            All In (${{ $state['players'][0]['chips'] }})
                                        </button>
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

                    <!-- Hand Information -->
                    @if($state['gameStarted'] && !empty($state['communityCards']))
                        <div class="hand-info">
                            <h4>Your Hand</h4>
                            @if($this->getHandRank())
                                <div class="hand-rank">
                                    <strong>{{ ucfirst(str_replace('_', ' ', $this->getHandRank()['rank'])) }}</strong>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Game Stats -->
                    <div class="game-stats">
                        <h4>Game Stats</h4>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-label">Your Chips:</span>
                                <span class="stat-value">${{ $state['players'][0]['chips'] }}</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Pot Size:</span>
                                <span class="stat-value">${{ $state['pot'] }}</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Hand Number:</span>
                                <span class="stat-value">{{ $state['handNumber'] }}</span>
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
                            <li>Each player gets 2 private cards (hole cards)</li>
                            <li>5 community cards are dealt face up in stages</li>
                            <li>Betting rounds: Pre-flop, Flop, Turn, River</li>
                            <li>Players can Check, Bet, Call, Raise, or Fold</li>
                            <li>Best hand wins the pot</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Hand Rankings</h4>
                        <ul>
                            <li>Royal Flush: A, K, Q, J, 10 of the same suit</li>
                            <li>Straight Flush: Five consecutive cards of the same suit</li>
                            <li>Four of a Kind: Four cards of the same rank</li>
                            <li>Full House: Three of a kind + pair</li>
                            <li>Flush: Five cards of the same suit</li>
                            <li>Straight: Five consecutive cards</li>
                            <li>Three of a Kind: Three cards of the same rank</li>
                            <li>Two Pair: Two different pairs</li>
                            <li>One Pair: Two cards of the same rank</li>
                            <li>High Card: Highest card when no other hand is made</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </x-game-layout>

    <style>
        /* Poker Table Styles */
        .poker-game-board {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 2rem;
            align-items: start;
        }

        .poker-table {
            background: var(--game-bg-primary);
            border-radius: 1rem;
            border: 2px solid var(--game-border);
            padding: 2rem;
            backdrop-filter: var(--game-backdrop);
        }

        .community-cards {
            text-align: center;
            margin-bottom: 2rem;
        }

        .community-cards h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
        }

        .cards-container {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .players-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .player {
            background: var(--game-bg-secondary);
            border: 2px solid var(--game-border);
            border-radius: 0.75rem;
            padding: 1rem;
            text-align: center;
            transition: var(--game-transition);
        }

        .player.current-player {
            border-color: var(--game-accent);
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }

        .player.folded {
            opacity: 0.5;
        }

        .player.human-player {
            border-color: var(--game-success);
        }

        .player-info {
            margin-bottom: 1rem;
        }

        .player-name {
            font-weight: 600;
            color: var(--game-text-primary);
            margin-bottom: 0.25rem;
        }

        .player-chips {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--game-accent);
        }

        .player-bet {
            font-size: 0.875rem;
            color: var(--game-warning);
            margin-top: 0.25rem;
        }

        .player-status {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--game-danger);
            margin-top: 0.25rem;
        }

        .player-cards {
            display: flex;
            justify-content: center;
            gap: 0.25rem;
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
        .player-actions {
            margin-bottom: 1.5rem;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .waiting-message {
            text-align: center;
            padding: 2rem;
        }

        .waiting-message h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
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

        .hand-rank {
            text-align: center;
            padding: 1rem;
            background: var(--game-bg-primary);
            border-radius: 0.5rem;
            border: 1px solid var(--game-border);
            margin-top: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .poker-game-board {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .players-container {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
            
            .card {
                width: 50px;
                height: 70px;
                font-size: 0.625rem;
            }
        }
    </style>
</div>
