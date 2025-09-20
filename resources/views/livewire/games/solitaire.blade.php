<?php

use App\Games\Solitaire\SolitaireGame;
use App\Games\Solitaire\SolitaireEngine;
use App\Models\User;
use App\Services\UserBestScoreService;
use App\Services\HintEngine;
use Livewire\Volt\Component;

new class extends Component
{
    public $state;
    public $draggedCard = null;
    public $dragSource = null;
    public $draggedCards = [];
    public $gameTime = 0;
    public $gameTimer = null;
    public $draggedFromCol = null;
    public $draggedFromIndex = null;
    public $undoStack = [];
    public $redoStack = [];
    public $gameMode = 'standard';
    public $difficulty = 'normal';
    public $drawCount = 3;
    public $showHints = false;
    public $hintDifficulty = 'beginner';

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $options = [
            'scoringMode' => $this->gameMode,
            'difficulty' => $this->difficulty,
            'drawCount' => $this->drawCount,
            'timerPressure' => $this->gameMode === 'timed'
        ];
        
        $this->state = SolitaireEngine::newGame($options);
        $this->gameTime = 0;
        $this->draggedCard = null;
        $this->dragSource = null;
        $this->draggedCards = [];
        $this->draggedFromCol = null;
        $this->draggedFromIndex = null;
        $this->undoStack = [];
        $this->redoStack = [];
    }

    public function drawFromStock()
    {
        $this->saveStateForUndo();
        $this->state = SolitaireEngine::drawFromStock($this->state);
    }

    public function moveWasteToTableau($tableauCol)
    {
        $this->saveStateForUndo();
        $this->state = SolitaireEngine::moveWasteToTableau($this->state, $tableauCol);
        $this->checkGameWon();
    }

    public function moveWasteToFoundation($suit)
    {
        $this->saveStateForUndo();
        $this->state = SolitaireEngine::moveWasteToFoundation($this->state, $suit);
        $this->checkGameWon();
    }

    public function moveTableauToTableau($fromCol, $cardIndex, $toCol)
    {
        $this->saveStateForUndo();
        $this->state = SolitaireEngine::moveTableauToTableau($this->state, $fromCol, $cardIndex, $toCol);
        $this->checkGameWon();
    }

    public function moveTableauToFoundation($fromCol, $suit)
    {
        $this->saveStateForUndo();
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

    public function startDragWaste()
    {
        $wasteCard = SolitaireEngine::getWasteCard($this->state);
        if ($wasteCard) {
            $this->draggedCard = $wasteCard;
            $this->dragSource = 'waste';
            $this->draggedCards = [$wasteCard];
        }
    }

    public function startDragTableau($col, $index)
    {
        if (!isset($this->state['tableau'][$col][$index])) {
            return;
        }

        $card = $this->state['tableau'][$col][$index];
        if (!$card['faceUp']) {
            return; // Can't drag face-down cards
        }

        // Get all cards from this index to the end (sequence)
        $cards = array_slice($this->state['tableau'][$col], $index);
        
        // Validate that this is a valid sequence to drag
        for ($i = 1; $i < count($cards); $i++) {
            if (!SolitaireEngine::canPlaceOnTableau($cards[$i], $cards[$i-1])) {
                return; // Invalid sequence
            }
        }

        $this->draggedCard = $card;
        $this->dragSource = 'tableau';
        $this->draggedCards = $cards;
        $this->draggedFromCol = $col;
        $this->draggedFromIndex = $index;
    }

    public function dropOnFoundation($suit)
    {
        if (!$this->draggedCard) {
            return;
        }

        if ($this->dragSource === 'waste') {
            $this->moveWasteToFoundation($suit);
        } elseif ($this->dragSource === 'tableau' && count($this->draggedCards) === 1) {
            $this->moveTableauToFoundation($this->draggedFromCol, $suit);
        }

        $this->clearDrag();
    }

    public function dropOnTableau($toCol)
    {
        if (!$this->draggedCard) {
            return;
        }

        if ($this->dragSource === 'waste') {
            $this->moveWasteToTableau($toCol);
        } elseif ($this->dragSource === 'tableau') {
            $this->moveTableauToTableau($this->draggedFromCol, $this->draggedFromIndex, $toCol);
        }

        $this->clearDrag();
    }

    public function clearDrag()
    {
        $this->draggedCard = null;
        $this->dragSource = null;
        $this->draggedCards = [];
        $this->draggedFromCol = null;
        $this->draggedFromIndex = null;
    }

    public function saveStateForUndo()
    {
        // Save current state to undo stack
        $this->undoStack[] = json_encode($this->state);
        
        // Clear redo stack when new move is made
        $this->redoStack = [];
        
        // Limit undo stack size to prevent memory issues
        if (count($this->undoStack) > 50) {
            array_shift($this->undoStack);
        }
    }

    public function undo()
    {
        if (empty($this->undoStack)) {
            return;
        }

        // Save current state to redo stack
        $this->redoStack[] = json_encode($this->state);
        
        // Restore previous state
        $previousState = array_pop($this->undoStack);
        $this->state = json_decode($previousState, true);
        
        // Clear any drag state
        $this->clearDrag();
    }

    public function redo()
    {
        if (empty($this->redoStack)) {
            return;
        }

        // Save current state to undo stack
        $this->undoStack[] = json_encode($this->state);
        
        // Restore next state
        $nextState = array_pop($this->redoStack);
        $this->state = json_decode($nextState, true);
        
        // Clear any drag state
        $this->clearDrag();
    }

    public function canUndo()
    {
        return !empty($this->undoStack);
    }

    public function canRedo()
    {
        return !empty($this->redoStack);
    }

    public function setGameMode($mode)
    {
        $this->gameMode = $mode;
        $this->resetGame();
    }

    public function setDifficulty($difficulty)
    {
        $this->difficulty = $difficulty;
        $this->resetGame();
    }

    public function setDrawCount($count)
    {
        $this->drawCount = $count;
        $this->resetGame();
    }

    public function getHints()
    {
        $game = new SolitaireGame();
        return HintEngine::getHints($game, $this->state, [
            'difficulty' => $this->hintDifficulty,
            'maxHints' => 5,
            'minPriority' => 1
        ]);
    }

    public function toggleHints()
    {
        $this->showHints = !$this->showHints;
    }

    public function setHintDifficulty($difficulty)
    {
        $this->hintDifficulty = $difficulty;
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
                        <div class="playing-card draggable-card card-flip-animation" 
                             style="background-image: url('{{ $this->getCardImageUrl($wasteCard) }}');"
                             title="{{ $wasteCard['rank'] }} of {{ $wasteCard['suit'] }}"
                             draggable="true"
                             x-data
                             @mousedown="$wire.startDragWaste()"
                             @dragstart="$el.classList.add('dragging')"
                             @dragend="$el.classList.remove('dragging'); $wire.clearDrag()">
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
                    <div class="foundation-pile foundation-{{ $suit }} drop-zone" 
                         wire:click="moveWasteToFoundation('{{ $suit }}')"
                         title="{{ ucfirst($suit) }} Foundation"
                         x-data
                         @dragover.prevent
                         @drop="$wire.dropOnFoundation('{{ $suit }}')">
                        @if(!empty($state['foundations'][$suit]))
                            @php $topCard = end($state['foundations'][$suit]); @endphp
                            <div class="playing-card card-stack-animation" 
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
                <div class="tableau-column drop-zone" 
                     data-col="{{ $col }}"
                     x-data
                     @dragover.prevent
                     @drop="$wire.dropOnTableau({{ $col }})">
                    @if(empty($state['tableau'][$col]))
                        <!-- Empty column - only accepts Kings -->
                        <div class="empty-pile tableau-empty" 
                             wire:click="moveWasteToTableau({{ $col }})"
                             title="Empty - Kings only">
                            <div class="pile-label">K</div>
                        </div>
                    @else
                        @foreach($state['tableau'][$col] as $index => $card)
                            <div class="playing-card tableau-card {{ $card['faceUp'] ? 'face-up' : 'face-down' }} 
                                      {{ $card['faceUp'] ? 'draggable-card' : '' }}
                                      {{ $card['faceUp'] && $index === count($state['tableau'][$col]) - 1 ? 'card-flip-animation' : '' }}"
                                 style="top: {{ $index * 20 }}px; background-image: url('{{ $this->getCardImageUrl($card) }}');"
                                 data-col="{{ $col }}" 
                                 data-index="{{ $index }}"
                                 wire:click="moveTableauToFoundation({{ $col }}, '{{ $card['suit'] }}')"
                                 title="{{ $card['faceUp'] ? $card['rank'] . ' of ' . $card['suit'] : 'Face down card' }}"
                                 @if($card['faceUp']) 
                                     draggable="true"
                                     x-data
                                     @mousedown="$wire.startDragTableau({{ $col }}, {{ $index }})"
                                     @dragstart="$el.classList.add('dragging')"
                                     @dragend="$el.classList.remove('dragging'); $wire.clearDrag()"
                                 @endif>
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

        <!-- Game Settings -->
        <div class="game-settings">
            <div class="settings-row">
                <div class="setting-group">
                    <label class="setting-label">Scoring:</label>
                    <select wire:change="setGameMode($event.target.value)" class="setting-select">
                        <option value="standard" {{ $gameMode === 'standard' ? 'selected' : '' }}>Standard</option>
                        <option value="vegas" {{ $gameMode === 'vegas' ? 'selected' : '' }}>Vegas ($)</option>
                        <option value="timed" {{ $gameMode === 'timed' ? 'selected' : '' }}>Timed</option>
                    </select>
                </div>
                
                <div class="setting-group">
                    <label class="setting-label">Difficulty:</label>
                    <select wire:change="setDifficulty($event.target.value)" class="setting-select">
                        <option value="easy" {{ $difficulty === 'easy' ? 'selected' : '' }}>Easy</option>
                        <option value="normal" {{ $difficulty === 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="hard" {{ $difficulty === 'hard' ? 'selected' : '' }}>Hard</option>
                    </select>
                </div>
                
                <div class="setting-group">
                    <label class="setting-label">Draw:</label>
                    <select wire:change="setDrawCount($event.target.value)" class="setting-select">
                        <option value="1" {{ $drawCount == 1 ? 'selected' : '' }}>1 Card</option>
                        <option value="3" {{ $drawCount == 3 ? 'selected' : '' }}>3 Cards</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Game Controls and Stats -->
        <div class="game-controls">
            <div class="control-section">
                <button wire:click="resetGame" class="game-btn new-game-btn">
                    New Game
                </button>
                <button wire:click="undo" 
                        class="game-btn undo-btn {{ $this->canUndo() ? '' : 'disabled' }}" 
                        {{ $this->canUndo() ? '' : 'disabled' }}
                        title="Undo last move (Ctrl+Z)">
                    ↶ Undo
                </button>
                <button wire:click="redo" 
                        class="game-btn redo-btn {{ $this->canRedo() ? '' : 'disabled' }}" 
                        {{ $this->canRedo() ? '' : 'disabled' }}
                        title="Redo last undone move (Ctrl+Y)">
                    ↷ Redo
                </button>
                <button wire:click="autoMove" class="game-btn auto-move-btn">
                    Auto Move
                </button>
                <button wire:click="toggleHints" class="game-btn hint-btn {{ $showHints ? 'active' : '' }}">
                    💡 Hints
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

        <!-- Hint Panel -->
        <x-game.hint-panel 
            :hints="$this->getHints()" 
            :show="$showHints" 
            position="top-right" />

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

        /* Drag and Drop Styles */
        .draggable-card {
            cursor: grab;
        }

        .draggable-card:active {
            cursor: grabbing;
        }

        .dragging {
            opacity: 0.5;
            transform: rotate(5deg) scale(0.9);
            z-index: 1000;
            pointer-events: none;
        }

        .drop-zone {
            transition: all 0.2s ease;
        }

        .drop-zone:hover {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
        }

        .drop-zone.drag-over {
            background: rgba(34, 197, 94, 0.2);
            border: 2px dashed #22c55e;
            border-radius: 8px;
        }

        .drop-zone.invalid-drop {
            background: rgba(239, 68, 68, 0.2);
            border: 2px dashed #ef4444;
            border-radius: 8px;
        }

        /* Enhanced dragging feedback */
        .draggable-card:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 
                0 8px 25px rgba(0,0,0,0.4),
                0 4px 15px rgba(0,0,0,0.3);
        }

        /* Smooth transitions for all cards */
        .playing-card {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), 
                       box-shadow 0.2s ease, 
                       opacity 0.2s ease,
                       top 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                       left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Enhanced animation effects */
        .card-move-animation {
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-flip-animation {
            animation: cardFlip 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-stack-animation {
            animation: stackCard 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes cardFlip {
            0% { transform: perspective(800px) rotateY(-180deg); opacity: 0.5; }
            50% { transform: perspective(800px) rotateY(-90deg); opacity: 0.8; }
            100% { transform: perspective(800px) rotateY(0deg); opacity: 1; }
        }

        @keyframes stackCard {
            0% { transform: translateY(-20px) scale(1.1); opacity: 0.8; }
            100% { transform: translateY(0) scale(1); opacity: 1; }
        }

        @keyframes foundationSuccess {
            0% { transform: scale(1); }
            50% { transform: scale(1.15); box-shadow: 0 0 20px rgba(34, 197, 94, 0.6); }
            100% { transform: scale(1); }
        }

        .foundation-success {
            animation: foundationSuccess 0.4s ease-in-out;
        }

        /* Game Settings */
        .game-settings {
            max-width: 1200px;
            margin: 0 auto 20px;
            background: rgba(0,0,0,0.15);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 16px 20px;
        }

        .settings-row {
            display: flex;
            gap: 24px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }

        .setting-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .setting-label {
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
        }

        .setting-select {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 6px;
            color: white;
            padding: 6px 12px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .setting-select:hover {
            background: rgba(255,255,255,0.15);
            border-color: rgba(255,255,255,0.3);
        }

        .setting-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        .setting-select option {
            background: #1e40af;
            color: white;
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

        .undo-btn {
            background: #3b82f6;
            color: white;
        }

        .undo-btn:hover:not(.disabled) {
            background: #2563eb;
        }

        .redo-btn {
            background: #8b5cf6;
            color: white;
        }

        .redo-btn:hover:not(.disabled) {
            background: #7c3aed;
        }

        .game-btn.disabled {
            background: #6b7280;
            color: #9ca3af;
            cursor: not-allowed;
            opacity: 0.5;
        }

        .game-btn.disabled:hover {
            background: #6b7280;
        }

        .hint-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .hint-btn:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }

        .hint-btn.active {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            transform: scale(1.05);
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

    <!-- Keyboard Shortcuts -->
    <script>
        document.addEventListener('keydown', function(e) {
            // Undo: Ctrl+Z
            if (e.ctrlKey && e.key === 'z' && !e.shiftKey) {
                e.preventDefault();
                if (@this.canUndo()) {
                    @this.undo();
                }
            }
            
            // Redo: Ctrl+Y or Ctrl+Shift+Z
            if ((e.ctrlKey && e.key === 'y') || (e.ctrlKey && e.shiftKey && e.key === 'Z')) {
                e.preventDefault();
                if (@this.canRedo()) {
                    @this.redo();
                }
            }
            
            // Auto Move: Space
            if (e.key === ' ' && !e.target.matches('input, textarea, button')) {
                e.preventDefault();
                @this.autoMove();
            }
        });
    </script>
</div>
