<?php

use App\Games\GameRegistry;
use Livewire\Volt\Component;

new class extends Component {
    public array $games = [];
    public array $featuredGames = [];
    public array $stats = [];
    
    public function mount(): void
    {
        $registry = app(GameRegistry::class);
        $this->games = $registry->listMetadata();
        
        // Feature the most impressive games first
        $this->featuredGames = [
            $this->findGame('checkers'),
            $this->findGame('connect4'),
            $this->findGame('solitaire'), 
            $this->findGame('nine-mens-morris'),
            $this->findGame('peg-solitaire'),
            $this->findGame('tic-tac-toe'),
            $this->findGame('2048'),
            $this->findGame('war')
        ];
        
        // Generate some impressive stats
        $this->stats = [
            'total_games' => count($this->games),
            'game_types' => ['Strategy', 'Puzzle', 'Card', 'Board'],
            'ai_levels' => 20, // Total AI difficulty levels across all games (4 for each AI game)
            'features' => ['Undo/Redo', 'Hint System', 'AI Opponents', 'Auto-Play Demos']
        ];
    }
    
    private function findGame($slug): ?array
    {
        return collect($this->games)->firstWhere('slug', $slug);
    }
    
    public function getGameIcon($slug): string
    {
        return match($slug) {
            'checkers' => '🟢',
            'connect4' => '🔴',
            'solitaire' => '♠️',
            'nine-mens-morris' => '⚫',
            'peg-solitaire' => '🔷',
            'tic-tac-toe' => '❌',
            '2048' => '🔢',
            'war' => '🃏',
            'chess' => '♔',
            'yahtzee' => '🎲',
            'sudoku' => '🔢',
            'blackjack' => '🃏',
            'snake' => '🐍',
            'memory' => '🧠',
            'tetris' => '📦',
            'minesweeper' => '💣',
            'poker' => '🃏',
            'go-fish' => '🐟',
            'crazy-eights' => '🎴',
            'spider-solitaire' => '🕷️',
            'farkle' => '🎲',
            'mastermind' => '🔍',
            'phase10' => '🔟',
            'word-detective' => '🔍',
            'slitherlink' => '🔗',
            'hexagon-slitherlink' => '🔷',
            default => '🎮'
        };
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-950">
    <!-- Navigation -->
    <x-site-navigation current-page="home" />
    
    <!-- Hero Section -->
    <section class="relative">
        <!-- Content -->
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-20">
            <div class="text-center">
                <h1 class="text-3xl md:text-5xl lg:text-6xl font-light text-slate-900 dark:text-slate-100 mb-8 tracking-tight">
                    <span class="font-normal">Classic Games</span>
                    <br>
                    <span class="text-slate-600 dark:text-slate-400 font-extralight">Thoughtfully Crafted</span>
                </h1>
                
                <p class="text-lg md:text-xl text-slate-600 dark:text-slate-400 mb-12 max-w-2xl mx-auto leading-relaxed">
                    {{ $stats['total_games'] }} classic games, thoughtfully implemented.
                </p>
                
                <div class="flex justify-center items-center mb-16">
                    <a href="#games" 
                       class="bg-slate-900 dark:bg-slate-100 hover:bg-slate-800 dark:hover:bg-slate-200 text-slate-100 dark:text-slate-900 px-6 py-3 rounded-lg text-base font-medium transition-colors duration-200">
                        Browse Games
                    </a>
                </div>
                
                <!-- Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto">
                    <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
                        <div class="text-2xl font-semibold text-slate-900 dark:text-slate-100 mb-1">{{ $stats['total_games'] }}</div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">Games</div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
                        <div class="text-2xl font-semibold text-slate-900 dark:text-slate-100 mb-1">{{ $stats['ai_levels'] }}</div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">AI Levels</div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
                        <div class="text-2xl font-semibold text-slate-900 dark:text-slate-100 mb-1">4</div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">Categories</div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
                        <div class="text-2xl font-semibold text-slate-900 dark:text-slate-100 mb-1">∞</div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">Hours</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Games -->
    <section id="games" class="py-20 bg-slate-100 dark:bg-slate-900">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-2xl md:text-3xl font-light text-slate-900 dark:text-slate-100 mb-4 tracking-tight">
                    Available Games
                </h2>
                <p class="text-base text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                    Classic games with clean interfaces.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($featuredGames as $game)
                    @if($game)
                        <div class="group bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 transition-colors duration-200">
                            <!-- Game Icon/Preview -->
                            <div class="w-12 h-12 mb-4 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center text-xl">
                                {{ $this->getGameIcon($game['slug']) }}
                            </div>
                            
                            <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-2">{{ $game['name'] }}</h3>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-6 leading-relaxed">{{ $game['description'] }}</p>
                            
                            <!-- Action Button -->
                            <div class="mt-4">
                                <a href="{{ url('/' . $game['slug']) }}" 
                                   class="w-full bg-slate-900 dark:bg-slate-100 hover:bg-slate-800 dark:hover:bg-slate-200 text-slate-100 dark:text-slate-900 text-center py-2 rounded-md text-sm font-medium transition-colors duration-200 block">
                                    Play
                                </a>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    <style>
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
    </style>

    <script>
        // Auto-scroll to games section when "Browse Games" is clicked
        document.addEventListener('DOMContentLoaded', function() {
            const exploreButton = document.querySelector('a[href="#games"]');
            if (exploreButton) {
                exploreButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.getElementById('games').scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            }
        });
    </script>
</div>