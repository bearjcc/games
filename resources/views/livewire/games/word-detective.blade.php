<?php

use App\Games\WordDetective\WordDetectiveGame;
use App\Games\WordDetective\WordDetectiveEngine;
use App\Services\UserBestScoreService;
use Livewire\Volt\Component;

new class extends Component
{
    public array $state;
    public string $selectedDifficulty = 'detective';
    public bool $showInstructions = false;

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $this->state = WordDetectiveEngine::newGame($this->selectedDifficulty);
    }

    public function newGame($difficulty = null)
    {
        if ($difficulty) {
            $this->selectedDifficulty = $difficulty;
        }
        $this->resetGame();
    }

    public function guessLetter($letter)
    {
        $move = ['action' => 'guess_letter', 'letter' => $letter];
        $this->applyMove($move);
    }

    public function useHint()
    {
        $move = ['action' => 'use_hint'];
        $this->applyMove($move);
    }

    public function applyMove($move)
    {
        $game = new WordDetectiveGame();
        
        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
            
            // Update best score if game is complete and user is authenticated
            if ($this->state['gameComplete'] && $this->state['gameWon'] && auth()->check()) {
                $score = $game->getScore($this->state);
                app(UserBestScoreService::class)->updateIfBetter(
                    auth()->user(),
                    'word-detective',
                    $score
                );
            }
        }
    }

    public function canUseHint()
    {
        $game = new WordDetectiveGame();
        return $game->canUseHint($this->state);
    }

    public function getProgressPercentage()
    {
        $totalLetters = strlen($this->state['word']);
        $revealedLetters = $totalLetters - substr_count($this->state['displayWord'], '_');
        return round(($revealedLetters / $totalLetters) * 100);
    }

    public function handleKeyPress($key)
    {
        $key = strtoupper($key);
        if (ctype_alpha($key) && !in_array($key, $this->state['guessedLetters'])) {
            $this->guessLetter($key);
        }
    }

    public function getLetterClass($letter)
    {
        $classes = ['letter-button'];
        
        if (in_array($letter, $this->state['guessedLetters'])) {
            if (strpos($this->state['word'], $letter) !== false) {
                $classes[] = 'correct-guess';
            } else {
                $classes[] = 'wrong-guess';
            }
            $classes[] = 'guessed';
        } else {
            $classes[] = 'available';
        }
        
        if ($this->state['gameComplete']) {
            $classes[] = 'disabled';
        }
        
        return implode(' ', $classes);
    }

    public function getDisplayWordWithSpacing()
    {
        return implode(' ', str_split($this->state['displayWord']));
    }
}; ?>

<div x-data="{
    handleKeyPress(event) {
        if (event.key.match(/[a-zA-Z]/)) {
            $wire.handleKeyPress(event.key);
        }
    }
}" 
x-on:keydown.window="handleKeyPress">
    <x-game-styles />
    
    <x-game-layout title="Word Detective" 
                   description="Solve mysteries by guessing letters! Uncover clues and detective tools as you work to reveal the hidden word."
                   difficulty="Medium" 
                   estimatedDuration="2-10 minutes">
        <!-- Game Status -->
        <div class="game-status">
            @if($state['gameComplete'])
                @if($state['gameWon'])
                    <div class="winner-indicator">
                        🎉 Case Solved! Score: {{ app(WordDetectiveGame::class)->getScore($state) }}
                        <div class="text-sm mt-2">
                            Mystery: {{ $state['mysteryTitle'] }}
                        </div>
                    </div>
                @else
                    <div class="game-over-indicator">
                        ❌ Case Gone Cold
                        <div class="text-sm mt-2">
                            The word was: <strong>{{ $state['word'] }}</strong>
                        </div>
                    </div>
                @endif
            @else
                <div class="player-indicator">
                    <div class="mystery-title">{{ $state['mysteryTitle'] }}</div>
                    <div class="text-sm mt-2">
                        Category: {{ ucfirst($state['category']) }} | 
                        Difficulty: {{ WordDetectiveEngine::DIFFICULTIES[$selectedDifficulty]['label'] }} |
                        Progress: {{ $this->getProgressPercentage() }}%
                    </div>
                </div>
            @endif
        </div>

        <!-- Game Board Container -->
        <div class="game-board-container">
            <div class="game-board word-detective-board">
                <!-- Detective Tools Display -->
                <div class="detective-tools-section">
                    <h4>Detective Tools</h4>
                    <div class="tools-grid">
                        @for($i = 0; $i < $state['maxWrongGuesses']; $i++)
                            <div class="tool-slot">
                                @if($i < count($state['revealedTools']))
                                    <span class="detective-tool">
                                        {{ WordDetectiveEngine::getToolEmoji($state['revealedTools'][$i]) }}
                                    </span>
                                @elseif($i < $state['wrongGuesses'])
                                    <span class="red-herring">
                                        {{ WordDetectiveEngine::getRedHerringEmoji($state['redHerrings'][$i - count($state['revealedTools'])]) }}
                                    </span>
                                @else
                                    <span class="empty-slot">❓</span>
                                @endif
                            </div>
                        @endfor
                    </div>
                </div>

                <!-- Word Display -->
                <div class="word-display-section">
                    <h4>The Mystery Word</h4>
                    <div class="word-display">
                        {{ $this->getDisplayWordWithSpacing() }}
                    </div>
                </div>

                <!-- Letter Grid -->
                <div class="letter-grid-section">
                    <h4>Guess a Letter</h4>
                    <div class="letter-grid">
                        @foreach(WordDetectiveEngine::getAvailableLetters() as $letter)
                            <button class="{{ $this->getLetterClass($letter) }}"
                                    wire:click="guessLetter('{{ $letter }}')"
                                    {{ $state['gameComplete'] ? 'disabled' : '' }}>
                                {{ $letter }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons-section">
                    <button class="action-button hint-button" 
                            wire:click="useHint"
                            {{ !$this->canUseHint() ? 'disabled' : '' }}>
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"/>
                        </svg>
                        Hint
                    </button>
                </div>

                <!-- Difficulty Selection -->
                <div class="difficulty-section">
                    <h4>New Case</h4>
                    <div class="difficulty-buttons">
                        @foreach(WordDetectiveEngine::DIFFICULTIES as $key => $info)
                            <button class="difficulty-button {{ $selectedDifficulty === $key ? 'active' : '' }}"
                                    wire:click="newGame('{{ $key }}')">
                                {{ $info['label'] }}
                            </button>
                        @endforeach
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
                            <li>Click letters or type on your keyboard to guess</li>
                            <li>Correct guesses reveal letters and unlock detective tools</li>
                            <li>Wrong guesses add red herrings (false clues)</li>
                            <li>Use hints when you're stuck</li>
                            <li>Solve the mystery before running out of chances!</li>
                        </ul>
                    </div>
                    <div class="instruction-section">
                        <h4>Difficulty Levels</h4>
                        <ul>
                            <li><strong>Rookie Detective:</strong> Short words, many chances</li>
                            <li><strong>Detective:</strong> Medium words, good for beginners</li>
                            <li><strong>Inspector:</strong> Longer words, fewer chances</li>
                            <li><strong>Detective Chief:</strong> Challenging words</li>
                            <li><strong>Superintendent:</strong> Expert level mysteries</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </x-game-layout>

    <style>
        /* Word Detective Specific Styles */
        .word-detective-board {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            max-width: 60rem;
            margin: 0 auto;
            padding: 1rem;
        }

        .detective-tools-section,
        .word-display-section,
        .letter-grid-section,
        .action-buttons-section,
        .difficulty-section {
            background: white;
            border: 2px solid rgb(71 85 105);
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .dark .detective-tools-section,
        .dark .word-display-section,
        .dark .letter-grid-section,
        .dark .action-buttons-section,
        .dark .difficulty-section {
            background: rgb(51 65 85);
            border-color: rgb(203 213 225);
        }

        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(3rem, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .tool-slot {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgb(249 250 251);
            border: 2px solid rgb(203 213 225);
            border-radius: 0.5rem;
            font-size: 1.5rem;
        }

        .dark .tool-slot {
            background: rgb(55 65 81);
            border-color: rgb(71 85 105);
        }

        .detective-tool {
            animation: revealTool 0.5s ease-in-out;
        }

        .red-herring {
            opacity: 0.6;
            animation: revealRedHerring 0.5s ease-in-out;
        }

        .empty-slot {
            opacity: 0.3;
        }

        @keyframes revealTool {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        @keyframes revealRedHerring {
            from { transform: rotate(-10deg) scale(0); opacity: 0; }
            to { transform: rotate(0deg) scale(1); opacity: 0.6; }
        }

        .word-display {
            font-family: 'Courier New', monospace;
            font-size: 2rem;
            font-weight: bold;
            letter-spacing: 0.5rem;
            text-align: center;
            color: rgb(71 85 105);
            min-height: 4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgb(249 250 251);
            border: 2px solid rgb(203 213 225);
            border-radius: 0.5rem;
            margin-top: 1rem;
        }

        .dark .word-display {
            color: rgb(203 213 225);
            background: rgb(55 65 81);
            border-color: rgb(71 85 105);
        }

        .letter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(3rem, 1fr));
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .letter-button {
            aspect-ratio: 1;
            border: 2px solid rgb(203 213 225);
            background: white;
            border-radius: 0.375rem;
            font-weight: bold;
            font-size: 1.125rem;
            color: rgb(71 85 105);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dark .letter-button {
            background: rgb(51 65 85);
            border-color: rgb(71 85 105);
            color: rgb(203 213 225);
        }

        .letter-button:hover:not(:disabled) {
            background: rgb(59 130 246);
            color: white;
            border-color: rgb(59 130 246);
        }

        .letter-button.available:hover:not(:disabled) {
            transform: scale(1.05);
        }

        .letter-button.correct-guess {
            background: rgb(34 197 94);
            color: white;
            border-color: rgb(34 197 94);
        }

        .letter-button.wrong-guess {
            background: rgb(239 68 68);
            color: white;
            border-color: rgb(239 68 68);
        }

        .letter-button.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .letter-button.guessed {
            animation: letterGuessed 0.3s ease-in-out;
        }

        @keyframes letterGuessed {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .mystery-title {
            font-size: 1.25rem;
            font-weight: bold;
            color: rgb(71 85 105);
            text-align: center;
        }

        .dark .mystery-title {
            color: rgb(203 213 225);
        }

        .action-buttons-section {
            display: flex;
            justify-content: center;
        }

        .action-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: white;
            border: 2px solid rgb(203 213 225);
            border-radius: 0.375rem;
            color: rgb(71 85 105);
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }

        .dark .action-button {
            background: rgb(51 65 85);
            border-color: rgb(71 85 105);
            color: rgb(203 213 225);
        }

        .action-button:hover:not(:disabled) {
            background: rgb(243 244 246);
            border-color: rgb(156 163 175);
        }

        .dark .action-button:hover:not(:disabled) {
            background: rgb(71 85 105);
        }

        .action-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .hint-button:not(:disabled) {
            background: rgb(168 85 247);
            color: white;
            border-color: rgb(168 85 247);
        }

        .hint-button:not(:disabled):hover {
            background: rgb(147 51 234);
            border-color: rgb(147 51 234);
        }

        .difficulty-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .difficulty-button {
            padding: 0.75rem;
            background: white;
            border: 2px solid rgb(203 213 225);
            border-radius: 0.375rem;
            color: rgb(71 85 105);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .dark .difficulty-button {
            background: rgb(51 65 85);
            border-color: rgb(71 85 105);
            color: rgb(203 213 225);
        }

        .difficulty-button:hover {
            background: rgb(243 244 246);
        }

        .dark .difficulty-button:hover {
            background: rgb(71 85 105);
        }

        .difficulty-button.active {
            background: rgb(59 130 246);
            color: white;
            border-color: rgb(59 130 246);
        }

        .game-over-indicator {
            background: rgb(254 226 226);
            border: 2px solid rgb(239 68 68);
            color: rgb(127 29 29);
            padding: 1rem;
            border-radius: 0.5rem;
            text-align: center;
            font-weight: bold;
        }

        .dark .game-over-indicator {
            background: rgb(127 29 29);
            border-color: rgb(239 68 68);
            color: rgb(254 226 226);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .word-detective-board {
                gap: 1rem;
                padding: 0.5rem;
            }

            .letter-grid {
                grid-template-columns: repeat(6, 1fr);
            }

            .tools-grid {
                grid-template-columns: repeat(4, 1fr);
            }

            .word-display {
                font-size: 1.5rem;
                letter-spacing: 0.25rem;
            }

            .difficulty-buttons {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            .letter-grid {
                grid-template-columns: repeat(5, 1fr);
            }

            .tools-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .word-display {
                font-size: 1.25rem;
                letter-spacing: 0.125rem;
            }
        }
    </style>
</div>
