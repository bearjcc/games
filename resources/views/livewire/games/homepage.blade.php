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

<div class="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800">
    <!-- Navigation -->
    <x-site-navigation current-page="home" />
    
    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-8">
        <!-- Simple Header -->
        <div class="text-center mb-8">
            <h1 class="text-xl md:text-2xl font-light text-slate-700 dark:text-slate-300 mb-1 tracking-wide">
                Classic Games
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ $stats['total_games'] }} thoughtfully crafted games
            </p>
        </div>
        
        <!-- Games Grid - Front and Center -->
        <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-7 gap-3 md:gap-4">
            @foreach($featuredGames as $game)
                @if($game)
                    <a href="{{ url('/' . $game['slug']) }}" 
                       class="group bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm rounded-lg p-3 border border-slate-200/50 dark:border-slate-700/50 hover:border-slate-300 dark:hover:border-slate-600 hover:bg-white dark:hover:bg-slate-800 transition-all duration-300 hover:shadow-md hover:scale-105">
                        <!-- Game Icon -->
                        <div class="w-12 h-12 mx-auto mb-2 bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-600 rounded-lg flex items-center justify-center text-lg shadow-sm">
                            {{ $this->getGameIcon($game['slug']) }}
                        </div>
                        
                        <!-- Game Name -->
                        <h3 class="text-xs font-medium text-slate-800 dark:text-slate-200 mb-1 text-center group-hover:text-slate-900 dark:group-hover:text-slate-100 transition-colors leading-tight">
                            {{ $game['name'] }}
                        </h3>
                        
                        <!-- Game Description -->
                        <p class="text-xs text-slate-500 dark:text-slate-400 text-center leading-tight line-clamp-2">
                            {{ $game['description'] }}
                        </p>
                    </a>
                @endif
            @endforeach
        </div>
        
        <!-- View All Games Link -->
        <div class="text-center mt-8">
            <a href="{{ url('/games') }}" 
               class="inline-flex items-center text-xs text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition-colors duration-200">
                View all {{ $stats['total_games'] }} games
                <svg class="ml-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>

</div>