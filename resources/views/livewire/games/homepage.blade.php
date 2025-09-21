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
        
        // Show all games instead of just featured ones
        $this->featuredGames = $this->games;
        
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
            'checkers' => '<i class="fas fa-chess-board text-green-600"></i>',
            'connect4' => '<i class="fas fa-circle text-red-500"></i>',
            'solitaire' => '<i class="fas fa-spade text-black"></i>',
            'nine-mens-morris' => '<i class="fas fa-circle-dot text-gray-800"></i>',
            'peg-solitaire' => '<i class="fas fa-circle-notch text-blue-500"></i>',
            'tic-tac-toe' => '<i class="fas fa-times text-red-500"></i>',
            '2048' => '<i class="fas fa-calculator text-purple-600"></i>',
            'war' => '<i class="fas fa-cards-blank text-indigo-600"></i>',
            'chess' => '<i class="fas fa-chess-king text-gray-800"></i>',
            'yahtzee' => '<i class="fas fa-dice text-orange-500"></i>',
            'sudoku' => '<i class="fas fa-th text-blue-600"></i>',
            'blackjack' => '<i class="fas fa-cards-blank text-green-600"></i>',
            'snake' => '<i class="fas fa-snake text-green-500"></i>',
            'memory' => '<i class="fas fa-brain text-purple-500"></i>',
            'tetris' => '<i class="fas fa-cube text-yellow-500"></i>',
            'minesweeper' => '<i class="fas fa-bomb text-red-600"></i>',
            'poker' => '<i class="fas fa-cards-blank text-red-600"></i>',
            'go-fish' => '<i class="fas fa-fish text-blue-500"></i>',
            'crazy-eights' => '<i class="fas fa-cards-blank text-purple-500"></i>',
            'spider-solitaire' => '<i class="fas fa-spider text-gray-700"></i>',
            'farkle' => '<i class="fas fa-dice-d6 text-orange-600"></i>',
            'mastermind' => '<i class="fas fa-search text-indigo-500"></i>',
            'phase10' => '<i class="fas fa-ten text-red-500"></i>',
            'word-detective' => '<i class="fas fa-search text-blue-500"></i>',
            'slitherlink' => '<i class="fas fa-link text-green-600"></i>',
            'hexagon-slitherlink' => '<i class="fas fa-hexagon text-blue-500"></i>',
            default => '<i class="fas fa-gamepad text-gray-600"></i>'
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
                            {!! $this->getGameIcon($game['slug']) !!}
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
        
    </div>

</div>