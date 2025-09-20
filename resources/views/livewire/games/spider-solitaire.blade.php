<div x-data="{
    timer: 0,
    gameStarted: false,
    gameOver: false,
    gameWon: false,
    selectedCards: [],
    dragFromColumn: -1,
    dragCardCount: 0,
    
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
    },
    
    newGame() {
        this.gameStarted = false;
        this.gameOver = false;
        this.gameWon = false;
        this.timer = 0;
        $wire.newGame();
    },
    
    selectCard(column, cardIndex) {
        if (this.gameOver) return;
        
        $wire.selectCard(column, cardIndex);
        this.selectedCards = $wire.selectedCards;
        this.dragFromColumn = $wire.dragFromColumn;
        this.dragCardCount = $wire.dragCardCount;
    },
    
    clearSelection() {
        $wire.clearSelection();
        this.selectedCards = [];
        this.dragFromColumn = -1;
        this.dragCardCount = 0;
    },
    
    canPlaceOnColumn(column) {
        return $wire.canPlaceOnColumn(column);
    },
    
    placeCards(column) {
        if (this.selectedCards.length === 0) return;
        
        $wire.moveCards(this.selectedCards.column, column, this.selectedCards.count);
        this.clearSelection();
    }
}">
    <x-game-styles />
    
    <x-game-layout title="Spider Solitaire" 
                   description="Classic single-player card game! Build sequences in descending order within the same suit. Complete a full suit to remove it from the board."
                   difficulty="Medium" 
                   estimatedDuration="10-30 minutes">
        
        <!-- Game Status -->
        <div class="game-status">
            @if($state['gameWon'])
                <div class="winner-indicator">
                    🎉 Congratulations! You Won!
                    <div class="text-sm mt-2">
                        Score: {{ $this->getScore() }} |
                        Moves: {{ $state['moves'] }} |
                        Time: <span x-text="formatTime(timer)"></span>
                    </div>
                </div>
            @elseif($state['gameOver'])
                <div class="winner-indicator" style="color: var(--game-error);">
                    💔 Game Over
                    <div class="text-sm mt-2">
                        Score: {{ $this->getScore() }} |
                        Moves: {{ $state['moves'] }} |
                        Time: <span x-text="formatTime(timer)"></span>
                    </div>
                </div>
            @elseif($state['gameStarted'] ?? true)
                <div class="player-indicator">
                    Build sequences in descending order within the same suit!
                    <div class="text-sm mt-2">
                        Moves: {{ $state['moves'] }} |
                        Score: {{ $this->getScore() }} |
                        Time: <span x-text="formatTime(timer)"></span> |
                        Stock: {{ count($this->getStock()) }} cards
                    </div>
                </div>
            @else
                <div class="player-indicator">
                    Ready to Play Spider Solitaire!
                    <div class="text-sm mt-2">
                        Complete all 8 suits to win
                    </div>
                </div>
            @endif
        </div>

        <!-- Game Board Container -->
        <div class="game-board-container">
            <div class="game-board spider-solitaire-game-board">
                <!-- Completed Suits Area -->
                <div class="completed-suits-area">
                    <h4>Completed Suits</h4>
                    <div class="completed-suits">
                        @foreach($this->getCompletedSuits() as $suit)
                            <div class="completed-suit {{ $this->getSuitColor($suit) }}">
                                <div class="suit-symbol">{{ $this->getSuitSymbol($suit) }}</div>
                                <div class="suit-name">{{ ucfirst($suit) }}</div>
                            </div>
                        @endforeach
                        @for($i = count($this->getCompletedSuits()); $i < 8; $i++)
                            <div class="completed-suit empty">
                                <div class="suit-symbol">?</div>
                                <div class="suit-name">Empty</div>
                            </div>
                        @endfor
                    </div>
                </div>

                <!-- Tableau Area -->
                <div class="tableau-area">
                    <h4>Tableau</h4>
                    <div class="tableau-grid">
                        @foreach($this->getTableau() as $columnIndex => $column)
                            <div class="tableau-column" 
                                 data-column="{{ $columnIndex }}"
                                 @click="if(selectedCards.length > 0 && canPlaceOnColumn({{ $columnIndex }})) placeCards({{ $columnIndex }})">
                                
                                <div class="column-header">
                                    {{ chr(65 + $columnIndex) }}
                                </div>
                                
                                <div class="column-cards">
                                    @foreach($column as $cardIndex => $card)
                                        <div class="card {{ $card['faceUp'] ? 'face-up' : 'face-down' }} 
                                            {{ $this->getSuitColor($card['suit']) }}
                                            {{ !empty($selectedCards) && $selectedCards['column'] == $columnIndex && $cardIndex >= $selectedCards['startIndex'] ? 'selected' : '' }}"
                                            @click="selectCard({{ $columnIndex }}, {{ $cardIndex }})"
                                            style="z-index: {{ $cardIndex + 1 }};">
                                            
                                            @if($card['faceUp'])
                                                <div class="card-rank">{{ $card['rank'] }}</div>
                                                <div class="card-suit">{{ $this->getSuitSymbol($card['suit']) }}</div>
                                            @else
                                                <div class="card-back"></div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Stock Pile -->
                <div class="stock-area">
                    <h4>Stock Pile</h4>
                    <div class="stock-pile">
                        @if(count($this->getStock()) > 0)
                            <div class="stock-card" @click="$wire.dealCards()">
                                <div class="stock-count">{{ count($this->getStock()) }}</div>
                                <div class="stock-text">Cards</div>
                            </div>
                        @else
                            <div class="stock-empty">
                                <div class="stock-text">Empty</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Game Controls Panel -->
        <div class="controls-panel fade-in">
            <!-- Game Controls -->
            <div class="game-controls">
                <h4>Game Controls</h4>
                @if(!($state['gameStarted'] ?? true))
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
                        <div class="action-buttons">
                            @if($this->canDealCards())
                                <button class="action-button" @click="$wire.dealCards()">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                                    </svg>
                                    Deal Cards
                                </button>
                            @endif
                            
                            @if($this->canUndo())
                                <button class="action-button" @click="$wire.undo()">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"/>
                                    </svg>
                                    Undo
                                </button>
                            @endif
                            
                            <button class="action-button" @click="$wire.getHint()">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"/>
                                </svg>
                                Hint
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Game Stats -->
            <div class="game-stats">
                <h4>Game Stats</h4>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-label">Moves:</span>
                        <span class="stat-value">{{ $state['moves'] }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Score:</span>
                        <span class="stat-value">{{ $this->getScore() }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Completed:</span>
                        <span class="stat-value">{{ count($this->getCompletedSuits()) }}/8</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Stock:</span>
                        <span class="stat-value">{{ count($this->getStock()) }}</span>
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
                        @if(isset($hint['move']))
                            <button class="action-button" @click="$wire.applyHint(@js($hint))">
                                Apply Hint
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
                            <li>Build sequences in descending order (King to Ace)</li>
                            <li>Sequences must be within the same suit</li>
                            <li>Complete a full suit (King to Ace) to remove it</li>
                            <li>Only Kings can be placed on empty columns</li>
                            <li>Deal 10 cards when no moves are possible</li>
                            <li>Goal: Remove all 8 suits from the board</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Scoring</h4>
                        <ul>
                            <li>Complete suit removal: 100 points</li>
                            <li>Move penalty: -1 point per move</li>
                            <li>Time bonus: Based on completion speed</li>
                            <li>Perfect game: All 8 suits removed</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </x-game-layout>

    <style>
        /* Spider Solitaire Game Styles */
        .spider-solitaire-game-board {
            display: grid;
            grid-template-columns: 1fr 3fr 1fr;
            gap: 2rem;
            align-items: start;
        }

        .completed-suits-area {
            text-align: center;
        }

        .completed-suits-area h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
        }

        .completed-suits {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }

        .completed-suit {
            background: var(--game-bg-secondary);
            border: 2px solid var(--game-border);
            border-radius: 0.5rem;
            padding: 0.75rem;
            text-align: center;
            transition: var(--game-transition);
        }

        .completed-suit.empty {
            opacity: 0.3;
            border-style: dashed;
        }

        .suit-symbol {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .suit-name {
            font-size: 0.75rem;
            color: var(--game-text-secondary);
        }

        .tableau-area {
            min-height: 500px;
        }

        .tableau-area h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
            text-align: center;
        }

        .tableau-grid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 0.5rem;
            min-height: 400px;
        }

        .tableau-column {
            background: var(--game-bg-secondary);
            border: 2px solid var(--game-border);
            border-radius: 0.5rem;
            padding: 0.5rem;
            min-height: 400px;
            cursor: pointer;
            transition: var(--game-transition);
        }

        .tableau-column:hover {
            border-color: var(--game-accent-primary);
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.2);
        }

        .column-header {
            text-align: center;
            font-weight: bold;
            color: var(--game-text-primary);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .column-cards {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            min-height: 350px;
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
            cursor: pointer;
            position: relative;
        }

        .card.face-down {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }

        .card.face-up.hearts, .card.face-up.diamonds {
            color: #dc2626;
        }

        .card.face-up.clubs, .card.face-up.spades {
            color: #1f2937;
        }

        .card.selected {
            border-color: var(--game-success);
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.4);
            transform: translateY(-4px);
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .card-rank {
            font-size: 0.875rem;
        }

        .card-suit {
            font-size: 1rem;
            text-align: center;
        }

        .card-back {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stock-area {
            text-align: center;
        }

        .stock-area h4 {
            margin-bottom: 1rem;
            color: var(--game-text-primary);
        }

        .stock-pile {
            display: flex;
            justify-content: center;
        }

        .stock-card {
            background: var(--game-bg-secondary);
            border: 2px solid var(--game-border);
            border-radius: 0.5rem;
            padding: 1rem;
            cursor: pointer;
            transition: var(--game-transition);
            min-width: 80px;
        }

        .stock-card:hover {
            border-color: var(--game-accent-primary);
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.2);
        }

        .stock-empty {
            background: var(--game-bg-primary);
            border: 2px dashed var(--game-border);
            border-radius: 0.5rem;
            padding: 1rem;
            opacity: 0.5;
            min-width: 80px;
        }

        .stock-count {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--game-accent-primary);
        }

        .stock-text {
            font-size: 0.875rem;
            color: var(--game-text-secondary);
            margin-top: 0.25rem;
        }

        /* Hint Panel */
        .hint-panel {
            margin-top: 1.5rem;
            padding: 1rem;
            background: var(--game-bg-primary);
            border: 1px solid var(--game-border);
            border-radius: 0.5rem;
        }

        .hint-panel h4 {
            margin-bottom: 0.5rem;
            color: var(--game-text-primary);
        }

        .hint-content p {
            color: var(--game-text-secondary);
            margin-bottom: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .spider-solitaire-game-board {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .tableau-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        @media (max-width: 768px) {
            .tableau-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .card {
                width: 50px;
                height: 70px;
                font-size: 0.625rem;
            }
            
            .completed-suits {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .tableau-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .card {
                width: 45px;
                height: 63px;
                font-size: 0.5rem;
            }
        }
    </style>
</div>
