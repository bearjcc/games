<div x-data="{
    timer: 0,
    gameStarted: false,
    gameOver: false,
    currentPlayer: 0,
    lastCardPlayed: null,
    currentSuit: '',
    mustDraw: false,
    
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
    
    playCard(cardIndex, newSuit = null) {
        if (this.gameOver || this.currentPlayer !== 0) return;
        
        $wire.playCard(cardIndex, newSuit);
    },
    
    drawCard() {
        if (this.gameOver || this.currentPlayer !== 0) return;
        
        $wire.drawCard();
    },
    
    changeSuit(newSuit) {
        if (this.gameOver) return;
        
        $wire.changeSuit(newSuit);
    }
}">
    <x-game-styles />
    
    <x-game-layout title="Crazy 8s" 
                   description="Classic shedding card game! Play cards matching the suit or rank of the discard pile. 8s are wild and can change the suit!"
                   difficulty="Easy" 
                   estimatedDuration="5-20 minutes">
        
        <!-- Game Status -->
        <div class="game-status">
            @if($state['gameOver'])
                @if($state['winner'] === 0)
                    <div class="winner-indicator">
                        🎉 You Won! Score: {{ $state['players'][0]['score'] }}
                        <div class="text-sm mt-2">
                            Time: <span x-text="formatTime(timer)"></span> |
                            Turns: {{ $state['turnCount'] }}
                        </div>
                    </div>
                @else
                    <div class="winner-indicator" style="color: var(--game-danger);">
                        💔 {{ $state['players'][$state['winner']]['name'] }} Won!
                        <div class="text-sm mt-2">
                            Your Score: {{ $state['players'][0]['score'] }} |
                            Time: <span x-text="formatTime(timer)"></span>
                        </div>
                    </div>
                @endif
            @elseif($state['gameStarted'])
                <div class="player-indicator">
                    @if($state['currentPlayer'] === 0)
                        @if($state['mustDraw'])
                            You must draw a card or play if possible.
                        @else
                            Your Turn! Play a card or draw from the deck.
                        @endif
                    @else
                        {{ $state['players'][$state['currentPlayer']]['name'] }}'s Turn
                    @endif
                    <div class="text-sm mt-2">
                        Current Suit: <span class="suit-indicator">{{ ucfirst($state['currentSuit']) }}</span> |
                        Turn: {{ $state['turnCount'] + 1 }} |
                        Time: <span x-text="formatTime(timer)"></span> |
                        Deck: {{ count($state['deck']) }} cards
                    </div>
                </div>
            @else
                <div class="player-indicator">
                    Ready to Play Crazy 8s!
                    <div class="text-sm mt-2">
                        Be the first to get rid of all your cards
                    </div>
                </div>
            @endif
        </div>

        <!-- Game Board Container -->
        <div class="game-board-container">
            <div class="game-board crazy-eights-game-board">
                <!-- Discard Pile -->
                <div class="discard-area">
                    <h4>Discard Pile</h4>
                    <div class="discard-pile">
                        @if(!empty($state['discardPile']))
                            @php
                                $topCard = end($state['discardPile']);
                            @endphp
                            <div class="card {{ $topCard['suit'] }} discard-card">
                                <div class="card-rank">{{ $topCard['rank'] }}</div>
                                <div class="card-suit">{{ $this->getSuitSymbol($topCard['suit']) }}</div>
                            </div>
                        @endif
                    </div>
                    <div class="current-suit">
                        Current Suit: <span class="suit-indicator">{{ ucfirst($state['currentSuit']) }}</span>
                    </div>
                </div>

                <!-- Players -->
                <div class="players-container">
                    @foreach($state['players'] as $index => $player)
                        <div class="player {{ $index === $state['currentPlayer'] ? 'current-player' : '' }} 
                            {{ $player['isHuman'] ? 'human-player' : 'ai-player' }}">
                            
                            <div class="player-info">
                                <div class="player-name">{{ $player['name'] }}</div>
                                <div class="player-stats">
                                    <span class="stat">Cards: {{ count($player['hand']) }}</span>
                                    <span class="stat">Score: {{ $player['score'] }}</span>
                                </div>
                            </div>

                            <!-- Player Cards -->
                            <div class="player-cards">
                                @if($player['isHuman'] || $state['gameOver'])
                                    @foreach($player['hand'] as $cardIndex => $card)
                                        <div class="card {{ $card['suit'] }} 
                                            {{ $this->canPlayCard($card) ? 'playable' : '' }}
                                            {{ $index === 0 ? 'clickable' : '' }}"
                                            @if($index === 0 && $this->canPlayCard($card))
                                                @click="playCard({{ $cardIndex }})"
                                            @endif>
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
                                    <h4>Your Actions</h4>
                                    <div class="action-buttons">
                                        <button class="action-button" @click="drawCard()">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                                            </svg>
                                            Draw Card
                                        </button>
                                    </div>
                                    
                                    @if($state['lastCardPlayed'] && $state['lastCardPlayed']['rank'] === '8')
                                        <div class="suit-selection">
                                            <h5>Choose New Suit:</h5>
                                            <div class="suit-buttons">
                                                @foreach(['hearts', 'diamonds', 'clubs', 'spades'] as $suit)
                                                    <button class="action-button suit-button {{ $suit }}" 
                                                            @click="changeSuit('{{ $suit }}')">
                                                        {{ $this->getSuitSymbol($suit) }} {{ ucfirst($suit) }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
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

                    <!-- Playable Cards -->
                    @if($state['currentPlayer'] === 0 && $state['gameStarted'] && !$state['gameOver'])
                        <div class="playable-cards">
                            <h4>Playable Cards</h4>
                            <div class="playable-list">
                                @foreach($this->getPlayableCards() as $playableCard)
                                    <div class="playable-item">
                                        <div class="card {{ $playableCard['card']['suit'] }} mini-card">
                                            <div class="card-rank">{{ $playableCard['card']['rank'] }}</div>
                                            <div class="card-suit">{{ $this->getSuitSymbol($playableCard['card']['suit']) }}</div>
                                        </div>
                                        <span class="playable-text">Click to play</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Game Stats -->
                    <div class="game-stats">
                        <h4>Game Stats</h4>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-label">Your Cards:</span>
                                <span class="stat-value">{{ count($state['players'][0]['hand']) }}</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Your Score:</span>
                                <span class="stat-value">{{ $state['players'][0]['score'] }}</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Deck Cards:</span>
                                <span class="stat-value">{{ count($state['deck']) }}</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Draw Count:</span>
                                <span class="stat-value">{{ $state['drawCount'] }}</span>
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
                            <li>Each player starts with 7 cards</li>
                            <li>Play cards matching the suit or rank of the discard pile</li>
                            <li>8s can be played on any card and let you choose a new suit</li>
                            <li>If you can't play, draw from the deck until you can</li>
                            <li>First player to empty their hand wins</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Special Cards</h4>
                        <ul>
                            <li>8 (Crazy Eight): Wild card, can be played on anything</li>
                            <li>Ace: Can be played on any card of the same suit</li>
                            <li>King: Can be played on any card of the same suit</li>
                            <li>Queen: Can be played on any card of the same suit</li>
                            <li>Jack: Can be played on any card of the same suit</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </x-game-layout>

    <style>
        /* Crazy 8s Game Styles */
        .crazy-eights-game-board {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 2rem;
            align-items: start;
        }

        .discard-area {
            text-align: center;
            margin-bottom: 2rem;
        }

        .discard-area h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
        }

        .discard-pile {
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .discard-card {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .current-suit {
            font-weight: 600;
            color: var(--game-text-primary);
        }

        .suit-indicator {
            color: var(--game-accent);
            font-weight: bold;
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

        .card.playable {
            border-color: var(--game-success);
            box-shadow: 0 0 10px rgba(34, 197, 94, 0.3);
        }

        .card.clickable {
            cursor: pointer;
        }

        .card.clickable:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .mini-card {
            width: 40px;
            height: 56px;
            font-size: 0.625rem;
        }

        /* Player Actions */
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .suit-selection {
            margin-top: 1rem;
            padding: 1rem;
            background: var(--game-bg-primary);
            border-radius: 0.5rem;
            border: 1px solid var(--game-border);
        }

        .suit-selection h5 {
            margin-bottom: 0.5rem;
            color: var(--game-text-primary);
            text-align: center;
        }

        .suit-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }

        .suit-button.hearts {
            color: #dc2626;
        }

        .suit-button.diamonds {
            color: #dc2626;
        }

        .suit-button.clubs {
            color: #1f2937;
        }

        .suit-button.spades {
            color: #1f2937;
        }

        .waiting-message {
            text-align: center;
            padding: 2rem;
        }

        .waiting-message h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
        }

        /* Playable Cards */
        .playable-cards {
            margin-top: 1.5rem;
        }

        .playable-list {
            display: grid;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .playable-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: var(--game-bg-primary);
            border-radius: 0.375rem;
            border: 1px solid var(--game-border);
        }

        .playable-text {
            font-size: 0.875rem;
            color: var(--game-text-secondary);
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
            .crazy-eights-game-board {
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
            
            .suit-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</div>
