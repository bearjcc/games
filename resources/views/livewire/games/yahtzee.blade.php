<?php

use App\Games\Yahtzee\YahtzeeGame;
use App\Games\Yahtzee\YahtzeeEngine;
use App\Services\UserBestScoreService;
use Livewire\Volt\Component;

new class extends Component
{
    public array $state;
    public bool $showScores = false;
    public string $selectedCategory = '';
    public array $possibleScores = [];

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $game = new YahtzeeGame();
        $this->state = $game->initialState();
        $this->showScores = false;
        $this->selectedCategory = '';
        $this->updatePossibleScores();
    }

    public function rollDice()
    {
        $game = new YahtzeeGame();
        $move = ['action' => 'roll'];
        
        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
            $this->updatePossibleScores();
            
            // Trigger enhanced dice rolling animation
            $this->dispatch('dice-rolled', [
                'values' => $this->state['dice'],
                'rollCount' => $this->state['rollCount']
            ]);
            
            // Show scoring options if no more rolls
            if (!$game->canRoll($this->state)) {
                $this->showScores = true;
            }
        }
    }

    public function toggleDiceHold($diceIndex)
    {
        $game = new YahtzeeGame();
        $move = ['action' => 'hold_dice', 'diceIndex' => $diceIndex];
        
        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
        }
    }

    public function scoreCategory($category)
    {
        $game = new YahtzeeGame();
        $move = ['action' => 'score', 'category' => $category];
        
        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
            $this->showScores = false;
            $this->selectedCategory = '';
            $this->updatePossibleScores();
            
            // Update best score if game is over and user is authenticated
            if ($this->state['gameOver'] && auth()->check()) {
                $score = $game->getScore($this->state);
                app(UserBestScoreService::class)->updateIfBetter(
                    auth()->user(),
                    'yahtzee',
                    $score
                );
            }
        }
    }

    public function updatePossibleScores()
    {
        $game = new YahtzeeGame();
        $this->possibleScores = $game->getPossibleScores($this->state);
    }

    public function getDiceImage($value)
    {
        return "dieWhite{$value}.png";
    }

    public function getCategoryLabel($category)
    {
        $labels = [
            'aces' => 'Aces (1s)',
            'twos' => 'Twos (2s)',
            'threes' => 'Threes (3s)',
            'fours' => 'Fours (4s)',
            'fives' => 'Fives (5s)',
            'sixes' => 'Sixes (6s)',
            'three_of_a_kind' => '3 of a Kind',
            'four_of_a_kind' => '4 of a Kind',
            'full_house' => 'Full House',
            'small_straight' => 'Small Straight',
            'large_straight' => 'Large Straight',
            'yahtzee' => 'YAHTZEE!',
            'chance' => 'Chance'
        ];
        
        return $labels[$category] ?? $category;
    }

    public function getScorecard()
    {
        $game = new YahtzeeGame();
        return $game->getScorecard($this->state);
    }

    public function isUpperSection($category)
    {
        return in_array($category, YahtzeeEngine::UPPER_SECTION);
    }

    public function previewScore($category)
    {
        $this->selectedCategory = $category;
    }

    public function clearPreview()
    {
        $this->selectedCategory = '';
    }
}; ?>

<div>
    <x-game.styles />
    <x-game.animations />
    
    <x-game.layout title="Yahtzee">
        <!-- Game Header -->
        <div class="game-header">
            <div class="game-status">
                @if($state['gameOver'])
                    <div class="winner-indicator">
                        Game Complete! Final Score: {{ $this->getScorecard()['grandTotal'] }}
                    </div>
                @else
                    <div class="player-indicator">
                        Turn {{ $state['currentTurn'] }}/13 - 
                        @if($state['phase'] === 'rolling')
                            Rolls left: {{ $state['rollsRemaining'] }}
                        @else
                            Choose a category to score
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Game Board -->
        <div class="game-board-container">
            <div class="yahtzee-game-board">
                <!-- Dice Section -->
                <div class="dice-section" data-game-id="yahtzee">
                    <h3 class="section-title">Dice</h3>
                    <div class="dice-container">
                        @foreach($state['dice'] as $index => $value)
                            <div class="dice-wrapper">
                                <div class="dice die {{ $state['diceHeld'][$index] ? 'held selected' : '' }}"
                                     wire:click="toggleDiceHold({{ $index }})"
                                     data-dice-index="{{ $index }}"
                                     data-dice-value="{{ $value }}"
                                     style="background-image: url('{{ Vite::asset('resources/img/Dice/' . $this->getDiceImage($value)) }}')">
                                </div>
                                @if($state['diceHeld'][$index])
                                    <div class="held-label">HELD</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    
                    @if(!$state['gameOver'])
                        <div class="dice-controls">
                            @if($state['phase'] === 'rolling' && $state['rollsRemaining'] > 0)
                                <button wire:click="rollDice" class="game-button roll-button roll-dice-button">
                                    Roll Dice ({{ $state['rollsRemaining'] }} left)
                                </button>
                            @elseif($state['phase'] === 'scoring')
                                <div class="scoring-prompt">
                                    Choose a category to score your dice
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Scorecard Section -->
                <div class="scorecard-section">
                    <h3 class="section-title">Scorecard</h3>
                    <div class="scorecard">
                        @php $scorecard = $this->getScorecard(); @endphp
                        
                        <!-- Upper Section -->
                        <div class="score-section upper-section">
                            <h4 class="score-section-title">Upper Section</h4>
                            @foreach(YahtzeeEngine::UPPER_SECTION as $category)
                                <div class="score-row {{ $selectedCategory === $category ? 'preview' : '' }} 
                                           {{ $scorecard['scorecard'][$category] !== null ? 'scored' : 'available' }}"
                                     @if($state['phase'] === 'scoring' && $scorecard['scorecard'][$category] === null)
                                         wire:click="scoreCategory('{{ $category }}')"
                                         wire:mouseenter="previewScore('{{ $category }}')"
                                         wire:mouseleave="clearPreview"
                                     @endif>
                                    <span class="category-label">{{ $this->getCategoryLabel($category) }}</span>
                                    <span class="score-value">
                                        @if($scorecard['scorecard'][$category] !== null)
                                            {{ $scorecard['scorecard'][$category] }}
                                        @elseif($selectedCategory === $category && isset($possibleScores[$category]))
                                            ({{ $possibleScores[$category] }})
                                        @elseif(isset($possibleScores[$category]))
                                            {{ $possibleScores[$category] }}
                                        @else
                                            —
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                            
                            <div class="score-row subtotal">
                                <span class="category-label">Subtotal</span>
                                <span class="score-value">{{ $scorecard['upperTotal'] }}</span>
                            </div>
                            <div class="score-row bonus">
                                <span class="category-label">
                                    Bonus ({{ YahtzeeEngine::UPPER_BONUS_THRESHOLD }}+)
                                    @if($scorecard['needsForBonus'] > 0)
                                        <small>Need {{ $scorecard['needsForBonus'] }}</small>
                                    @endif
                                </span>
                                <span class="score-value">{{ $scorecard['upperBonus'] }}</span>
                            </div>
                        </div>

                        <!-- Lower Section -->
                        <div class="score-section lower-section">
                            <h4 class="score-section-title">Lower Section</h4>
                            @foreach(array_diff(YahtzeeEngine::CATEGORIES, YahtzeeEngine::UPPER_SECTION) as $category)
                                <div class="score-row {{ $selectedCategory === $category ? 'preview' : '' }} 
                                           {{ $scorecard['scorecard'][$category] !== null ? 'scored' : 'available' }}"
                                     @if($state['phase'] === 'scoring' && $scorecard['scorecard'][$category] === null)
                                         wire:click="scoreCategory('{{ $category }}')"
                                         wire:mouseenter="previewScore('{{ $category }}')"
                                         wire:mouseleave="clearPreview"
                                     @endif>
                                    <span class="category-label">{{ $this->getCategoryLabel($category) }}</span>
                                    <span class="score-value">
                                        @if($scorecard['scorecard'][$category] !== null)
                                            {{ $scorecard['scorecard'][$category] }}
                                        @elseif($selectedCategory === $category && isset($possibleScores[$category]))
                                            ({{ $possibleScores[$category] }})
                                        @elseif(isset($possibleScores[$category]))
                                            {{ $possibleScores[$category] }}
                                        @else
                                            —
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <!-- Total -->
                        <div class="score-section total-section">
                            <div class="score-row total">
                                <span class="category-label">TOTAL SCORE</span>
                                <span class="score-value total-value">{{ $scorecard['grandTotal'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Game Controls -->
        <div class="game-controls">
            <button wire:click="resetGame" class="game-button">
                New Game
            </button>
        </div>
    </x-game.layout>

    <style>
    .yahtzee-game-board {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 2rem;
        max-width: 60rem;
        margin: 0 auto;
        padding: 1rem;
    }

    @media (max-width: 768px) {
        .yahtzee-game-board {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: bold;
        margin-bottom: 1rem;
        text-align: center;
        color: rgb(71 85 105);
    }

    .dark .section-title {
        color: rgb(226 232 240);
    }

    /* Dice Styles */
    .dice-section {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .dice-container {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .dice-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .dice {
        width: 4rem;
        height: 4rem;
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
        border: 2px solid transparent;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .dice:hover {
        transform: scale(1.05);
        border-color: rgb(59 130 246);
    }

    .dice.held {
        border-color: rgb(34 197 94);
        background-color: rgba(34, 197, 94, 0.1);
    }

    .held-label {
        font-size: 0.75rem;
        font-weight: bold;
        color: rgb(34 197 94);
        margin-top: 0.25rem;
    }

    .dice-controls {
        text-align: center;
    }

    .roll-button {
        background: rgb(59 130 246);
        color: white;
        font-weight: bold;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        border: none;
        cursor: pointer;
        transition: background 0.2s;
    }

    .roll-button:hover {
        background: rgb(37 99 235);
    }

    .scoring-prompt {
        color: rgb(71 85 105);
        font-style: italic;
    }

    .dark .scoring-prompt {
        color: rgb(226 232 240);
    }

    /* Scorecard Styles */
    .scorecard {
        background: white;
        border-radius: 0.5rem;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .dark .scorecard {
        background: rgb(51 65 85);
    }

    .score-section {
        border-bottom: 1px solid rgb(226 232 240);
    }

    .dark .score-section {
        border-bottom-color: rgb(71 85 105);
    }

    .score-section:last-child {
        border-bottom: none;
    }

    .score-section-title {
        background: rgb(241 245 249);
        padding: 0.75rem 1rem;
        font-weight: bold;
        font-size: 0.875rem;
        color: rgb(71 85 105);
        margin: 0;
    }

    .dark .score-section-title {
        background: rgb(71 85 105);
        color: rgb(226 232 240);
    }

    .score-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 1rem;
        border-bottom: 1px solid rgb(241 245 249);
        transition: background 0.2s;
    }

    .dark .score-row {
        border-bottom-color: rgb(71 85 105);
    }

    .score-row:last-child {
        border-bottom: none;
    }

    .score-row.available {
        cursor: pointer;
    }

    .score-row.available:hover,
    .score-row.preview {
        background: rgb(239 246 255);
    }

    .dark .score-row.available:hover,
    .dark .score-row.preview {
        background: rgb(30 41 59);
    }

    .score-row.scored {
        background: rgb(249 250 251);
    }

    .dark .score-row.scored {
        background: rgb(71 85 105);
    }

    .category-label {
        font-weight: 500;
        color: rgb(55 65 81);
    }

    .dark .category-label {
        color: rgb(203 213 225);
    }

    .score-value {
        font-weight: bold;
        min-width: 3rem;
        text-align: right;
        color: rgb(17 24 39);
    }

    .dark .score-value {
        color: rgb(241 245 249);
    }

    .score-row.subtotal,
    .score-row.bonus,
    .score-row.total {
        background: rgb(243 244 246);
        font-weight: bold;
    }

    .dark .score-row.subtotal,
    .dark .score-row.bonus,
    .dark .score-row.total {
        background: rgb(55 65 81);
    }

    .total-value {
        font-size: 1.25rem;
        color: rgb(59 130 246);
    }

    .dark .total-value {
        color: rgb(96 165 250);
    }
    </style>

    <!-- Enhanced Animation Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Listen for dice rolled events
            Livewire.on('dice-rolled', function(data) {
                if (window.DiceAnimations && data.values) {
                    const diceElements = document.querySelectorAll('[data-game-id="yahtzee"] .die');
                    const finalValues = data.values;
                    
                    // Trigger enhanced dice rolling animation
                    window.DiceAnimations.rollMultipleDice(
                        Array.from(diceElements), 
                        finalValues,
                        {
                            duration: 1.5,
                            intensity: 'medium',
                            onComplete: function() {
                                console.log('Dice rolling animation completed');
                            }
                        }
                    );
                }
            });

            // Add dice selection animations
            document.querySelectorAll('[data-game-id="yahtzee"] .die').forEach(die => {
                die.addEventListener('click', function() {
                    if (window.DiceAnimations) {
                        const isSelected = this.classList.contains('selected');
                        window.DiceAnimations.selectDie(this, !isSelected);
                    }
                });
            });

            // Add hover effects
            document.querySelectorAll('[data-game-id="yahtzee"] .die').forEach(die => {
                die.addEventListener('mouseenter', function() {
                    if (!this.classList.contains('held')) {
                        this.style.transform = 'translateY(-4px) scale(1.05)';
                    }
                });
                
                die.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('held')) {
                        this.style.transform = '';
                    }
                });
            });
        });
    </script>
</div>
