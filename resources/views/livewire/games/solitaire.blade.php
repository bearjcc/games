<?php

use App\Games\Solitaire\SolitaireGame;
use App\Games\Solitaire\SolitaireEngine;
use App\Models\User;
use App\Services\UserBestScoreService;
use Livewire\Volt\Component;

new class extends Component
{
    public $state;
    public $draggedCard = null;
    public $dragSource = null;
    public $gameTime = 0;
    public $gameTimer = null;

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $this->state = SolitaireEngine::newGame();
        $this->gameTime = 0;
        $this->draggedCard = null;
        $this->dragSource = null;
    }

    public function drawFromStock()
    {
        $this->state = SolitaireEngine::drawFromStock($this->state);
    }

    public function moveWasteToTableau($tableauCol)
    {
        $this->state = SolitaireEngine::moveWasteToTableau($this->state, $tableauCol);
        $this->checkGameWon();
    }

    public function moveWasteToFoundation($suit)
    {
        $this->state = SolitaireEngine::moveWasteToFoundation($this->state, $suit);
        $this->checkGameWon();
    }

    public function moveTableauToTableau($fromCol, $cardIndex, $toCol)
    {
        $this->state = SolitaireEngine::moveTableauToTableau($this->state, $fromCol, $cardIndex, $toCol);
        $this->checkGameWon();
    }

    public function moveTableauToFoundation($fromCol, $suit)
    {
        $this->state = SolitaireEngine::moveTableauToFoundation($this->state, $fromCol, $suit);
        $this->checkGameWon();
    }

    public function autoMove()
    {
        $autoMoves = SolitaireEngine::getAutoMoves($this->state);
        if (!empty($autoMoves)) {
            $move = $autoMoves[0]; // Take first available auto-move
            
            if ($move['type'] === 'waste_to_foundation') {
                $this->moveWasteToFoundation($move['suit']);
            } elseif ($move['type'] === 'tableau_to_foundation') {
                $this->moveTableauToFoundation($move['col'], $move['suit']);
            }
        }
    }

    private function checkGameWon()
    {
        if (SolitaireEngine::isGameWon($this->state)) {
            $this->state['gameWon'] = true;
            $this->state['gameTime'] = $this->gameTime;
            
            // Save best score if user is authenticated
            if (auth()->check()) {
                $game = new SolitaireGame();
                $score = $game->getScore($this->state);
                $userBestScoreService = app(UserBestScoreService::class);
                $userBestScoreService->updateBestScore(auth()->user(), $game, $score);
            }
        }
    }

    public function getCardImageUrl($card)
    {
        if (!$card || !$card['faceUp']) {
            return '/images/playingCards_back.svg';
        }
        
        $filename = SolitaireEngine::getCardSprite($card);
        return "/images/Cards/{$filename}";
    }

    public function getStats()
    {
        return SolitaireEngine::getStats($this->state);
    }

    public function getWasteCard()
    {
        return SolitaireEngine::getWasteCard($this->state);
    }
}; ?>

<div>
    <x-game.accessibility>
        @if($state['gameWon'])
            <div>Congratulations! You won Klondike Solitaire in {{ $state['moves'] }} moves!</div>
        @endif
    </x-game.accessibility>

    <!-- Solitaire Table Layout -->
    <div class="solitaire-table">
        <!-- Header Area: Stock, Waste, and Foundations -->
        <div class="game-header">
            <!-- Stock and Waste -->
            <div class="stock-waste-area">
                <!-- Stock Pile -->
                <div class="card-pile stock-pile" wire:click="drawFromStock">
                    @if(!empty($state['stock']))
                        <div class="playing-card card-back" title="Draw cards ({{ count($state['stock']) }} remaining)">
                            <div class="card-count-badge">{{ count($state['stock']) }}</div>
                        </div>
                    @else
                        <div class="empty-pile stock-empty" title="Click to recycle waste pile">
                            <div class="pile-icon">↻</div>
                        </div>
                    @endif
                </div>

                <!-- Waste Pile -->
                <div class="card-pile waste-pile">
                    @php $wasteCard = $this->getWasteCard(); @endphp
                    @if($wasteCard)
                        <div class="playing-card" 
                             style="background-image: url('{{ $this->getCardImageUrl($wasteCard) }}');"
                             title="{{ $wasteCard['rank'] }} of {{ $wasteCard['suit'] }}">
                        </div>
                    @else
                        <div class="empty-pile waste-empty">
                            <div class="pile-label">Waste</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Foundations -->
            <div class="foundations-area">
                @foreach(['hearts', 'diamonds', 'clubs', 'spades'] as $suit)
                    <div class="foundation-pile foundation-{{ $suit }}" 
                         wire:click="moveWasteToFoundation('{{ $suit }}')"
                         title="{{ ucfirst($suit) }} Foundation">
                        @if(!empty($state['foundations'][$suit]))
                            @php $topCard = end($state['foundations'][$suit]); @endphp
                            <div class="playing-card" 
                                 style="background-image: url('{{ $this->getCardImageUrl($topCard) }}');"
                                 title="{{ $topCard['rank'] }} of {{ $topCard['suit'] }}">
                            </div>
                        @else
                            <div class="empty-pile foundation-empty foundation-{{ $suit }}">
                                <div class="suit-icon">{{ ['hearts' => '♥', 'diamonds' => '♦', 'clubs' => '♣', 'spades' => '♠'][$suit] }}</div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Tableau Area -->
        <div class="tableau-area">
            @for($col = 0; $col < 7; $col++)
                <div class="tableau-column" data-col="{{ $col }}">
                    @if(empty($state['tableau'][$col]))
                        <!-- Empty column - only accepts Kings -->
                        <div class="empty-pile tableau-empty" 
                             wire:click="moveWasteToTableau({{ $col }})"
                             title="Empty - Kings only">
                            <div class="pile-label">K</div>
                        </div>
                    @else
                        @foreach($state['tableau'][$col] as $index => $card)
                            <div class="playing-card tableau-card {{ $card['faceUp'] ? 'face-up' : 'face-down' }}"
                                 style="top: {{ $index * 20 }}px; background-image: url('{{ $this->getCardImageUrl($card) }}');"
                                 data-col="{{ $col }}" 
                                 data-index="{{ $index }}"
                                 wire:click="moveTableauToFoundation({{ $col }}, '{{ $card['suit'] }}')"
                                 title="{{ $card['faceUp'] ? $card['rank'] . ' of ' . $card['suit'] : 'Face down card' }}">
                                @if($card['faceUp'] && $index === count($state['tableau'][$col]) - 1)
                                    <!-- Add move indicators for top cards -->
                                    <div class="card-moves">
                                        <button wire:click.stop="autoMove" class="auto-move-btn" title="Auto-move if possible">↑</button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            @endfor
        </div>

        <!-- Game Controls and Stats -->
        <div class="game-controls">
            <div class="control-section">
                <button wire:click="resetGame" class="game-btn new-game-btn">
                    New Game
                </button>
                <button wire:click="autoMove" class="game-btn auto-move-btn">
                    Auto Move
                </button>
            </div>

            <div class="stats-section">
                @php $stats = $this->getStats(); @endphp
                <div class="stat-item">
                    <span class="stat-label">Score:</span>
                    <span class="stat-value">{{ number_format($stats['score']) }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Moves:</span>
                    <span class="stat-value">{{ $stats['moves'] }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Foundation:</span>
                    <span class="stat-value">{{ $stats['foundationCards'] }}/52</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Complete:</span>
                    <span class="stat-value">{{ $stats['completion'] }}%</span>
                </div>
            </div>
        </div>

        <!-- Win Message -->
        @if($state['gameWon'])
            <div class="win-overlay">
                <div class="win-message">
                    <h2 class="win-title">🎉 Congratulations! 🎉</h2>
                    <p class="win-details">You completed Klondike Solitaire!</p>
                    <div class="win-stats">
                        <div>Final Score: <strong>{{ number_format($this->getStats()['score']) }}</strong></div>
                        <div>Moves: <strong>{{ $state['moves'] }}</strong></div>
                        <div>Time: <strong>{{ gmdate('i:s', $state['gameTime']) }}</strong></div>
                    </div>
                    <button wire:click="resetGame" class="game-btn new-game-btn">
                        Play Again
                    </button>
                </div>
            </div>
        @endif
    </div>

    <style>
        /* Solitaire Table Layout */
        .solitaire-table {
            min-height: 100vh;
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1d4ed8 100%);
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            user-select: none;
            position: relative;
        }

        /* Header Area (Stock, Waste, Foundations) */
        .game-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .stock-waste-area {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .foundations-area {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /* Card Styling */
        .playing-card {
            width: 80px;
            height: 112px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 
                0 4px 12px rgba(0,0,0,0.3),
                0 2px 6px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
        }

        .playing-card:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 8px 20px rgba(0,0,0,0.4),
                0 4px 10px rgba(0,0,0,0.3);
        }

        .card-back {
            background-image: url('/images/playingCards_back.svg');
            background-size: cover;
            background-position: center;
        }

        /* Card Piles */
        .card-pile {
            width: 80px;
            height: 112px;
            position: relative;
            cursor: pointer;
        }

        .empty-pile {
            width: 80px;
            height: 112px;
            border: 2px dashed rgba(255,255,255,0.3);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.05);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .empty-pile:hover {
            border-color: rgba(255,255,255,0.5);
            background: rgba(255,255,255,0.1);
        }

        .pile-icon, .pile-label {
            color: rgba(255,255,255,0.6);
            font-size: 24px;
            font-weight: bold;
        }

        .suit-icon {
            color: rgba(255,255,255,0.6);
            font-size: 32px;
        }

        .foundation-hearts .suit-icon, .foundation-diamonds .suit-icon {
            color: rgba(239, 68, 68, 0.6);
        }

        .foundation-clubs .suit-icon, .foundation-spades .suit-icon {
            color: rgba(0, 0, 0, 0.6);
        }

        /* Card Count Badge */
        .card-count-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            background: rgba(0,0,0,0.8);
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 4px;
            border-radius: 8px;
            min-width: 16px;
            text-align: center;
        }

        /* Tableau Area */
        .tableau-area {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 40px;
            min-height: 400px;
        }

        .tableau-column {
            width: 80px;
            position: relative;
            min-height: 300px;
        }

        .tableau-card {
            position: absolute;
            left: 0;
            z-index: 1;
        }

        .tableau-card.face-up {
            z-index: 2;
        }

        .tableau-card:last-child {
            z-index: 10;
        }

        /* Card Move Controls */
        .card-moves {
            position: absolute;
            top: -8px;
            right: -8px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .tableau-card:hover .card-moves {
            opacity: 1;
        }

        .auto-move-btn {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #22c55e;
            color: white;
            border: none;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Game Controls */
        .game-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
        }

        .control-section {
            display: flex;
            gap: 12px;
        }

        .stats-section {
            display: flex;
            gap: 24px;
        }

        .game-btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .new-game-btn {
            background: #ef4444;
            color: white;
        }

        .new-game-btn:hover {
            background: #dc2626;
        }

        .auto-move-btn {
            background: #22c55e;
            color: white;
        }

        .auto-move-btn:hover {
            background: #16a34a;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .stat-label {
            color: rgba(255,255,255,0.7);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            color: white;
            font-size: 18px;
            font-weight: bold;
        }

        /* Win Overlay */
        .win-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .win-message {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            max-width: 400px;
            color: #1f2937;
        }

        .win-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 16px;
        }

        .win-details {
            font-size: 16px;
            margin-bottom: 20px;
        }

        .win-stats {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .solitaire-table {
                padding: 10px;
            }
            
            .game-header {
                flex-direction: column;
                gap: 20px;
            }
            
            .tableau-area {
                gap: 8px;
                overflow-x: auto;
                padding-bottom: 20px;
            }
            
            .playing-card {
                width: 60px;
                height: 84px;
            }
            
            .tableau-column {
                width: 60px;
            }
            
            .game-controls {
                flex-direction: column;
                gap: 16px;
            }
            
            .stats-section {
                gap: 16px;
            }
        }
    </style>
</div>
