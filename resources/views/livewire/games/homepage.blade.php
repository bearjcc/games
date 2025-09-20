<?php

use App\Games\GameRegistry;
use Livewire\Volt\Component;

new class extends Component {
    public array $games = [];
    public array $featuredGames = [];
    public array $stats = [];
    public $demoGame = null;
    public $showDemo = false;
    
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
    
    public function startDemo($gameSlug): void
    {
        $this->demoGame = $gameSlug;
        $this->showDemo = true;
        $this->dispatch('start-demo', $gameSlug);
    }
    
    public function closeDemo(): void
    {
        $this->showDemo = false;
        $this->demoGame = null;
    }
    
    public function getGameIcon($slug): string
    {
        return match($slug) {
            'checkers' => '🟢',
            'connect4' => '🔴',
            'solitaire' => '🃏',
            'nine-mens-morris' => '⚫',
            'peg-solitaire' => '🔵',
            'tic-tac-toe' => '❌',
            '2048' => '🔢',
            'war' => '⚔️',
            default => '🎮'
        };
    }
    
    public function getGameGradient($slug): string
    {
        return match($slug) {
            'checkers' => 'from-green-600 to-emerald-700',
            'connect4' => 'from-red-500 to-yellow-500',
            'solitaire' => 'from-green-600 to-blue-600',
            'nine-mens-morris' => 'from-purple-600 to-indigo-600',
            'peg-solitaire' => 'from-orange-500 to-red-500',
            'tic-tac-toe' => 'from-blue-500 to-purple-500',
            '2048' => 'from-yellow-500 to-orange-500',
            'war' => 'from-red-600 to-pink-600',
            default => 'from-gray-500 to-gray-600'
        };
    }
    
    public function getGameFeatures($slug): array
    {
        return match($slug) {
            'checkers' => ['4 AI Difficulty Levels', 'Drag & Drop Pieces', 'Kings & Captures', 'Traditional Rules'],
            'connect4' => ['4 AI Difficulty Levels', 'Pass & Play Mode', 'Animated Drops', 'Best Move Hints'],
            'solitaire' => ['Drag & Drop Cards', 'Undo/Redo System', 'Multiple Scoring Modes', 'Auto-Move Detection'],
            'nine-mens-morris' => ['3-Phase Gameplay', 'Strategic AI', 'Mill Detection', 'Professional Board'],
            'peg-solitaire' => ['Triangular Board', 'Traditional Scoring', 'Best Move Hints', 'Multiple Start Positions'],
            'tic-tac-toe' => ['Impossible AI', 'SVG Graphics', 'Win Line Animation', 'Pass & Play'],
            '2048' => ['Smooth Tile Merging', 'Score Tracking', 'Responsive Grid', 'Undo Feature'],
            'war' => ['Animated Cards', 'War Mechanics', 'Real Card Images', 'Game Statistics'],
            default => ['Professional Quality', 'Tested & Reliable', 'Mobile Responsive']
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
                    {{ $stats['total_games'] }} carefully designed games with sophisticated AI, 
                    clean interfaces, and timeless gameplay.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-3 justify-center items-center mb-16">
                    <a href="#games" 
                       class="bg-slate-900 dark:bg-slate-100 hover:bg-slate-800 dark:hover:bg-slate-200 text-slate-100 dark:text-slate-900 px-6 py-3 rounded-lg text-base font-medium transition-colors duration-200">
                        Browse Games
                    </a>
                    <button wire:click="startDemo('connect4')"
                            class="border border-slate-300 dark:border-slate-600 hover:border-slate-400 dark:hover:border-slate-500 text-slate-700 dark:text-slate-300 px-6 py-3 rounded-lg text-base font-medium transition-colors duration-200">
                        Watch Demo
                    </button>
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
                    Thoughtfully designed with intelligent AI, clean interfaces, and careful attention to detail.
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
                            
                            <!-- Features -->
                            <div class="space-y-1 mb-6">
                                @foreach(array_slice($this->getGameFeatures($game['slug']), 0, 3) as $feature)
                                    <div class="flex items-center text-xs text-slate-600 dark:text-slate-400">
                                        <div class="w-1.5 h-1.5 bg-slate-400 rounded-full mr-2"></div>
                                        {{ $feature }}
                                    </div>
                                @endforeach
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex gap-2">
                                <a href="{{ url('/' . $game['slug']) }}" 
                                   class="flex-1 bg-slate-900 dark:bg-slate-100 hover:bg-slate-800 dark:hover:bg-slate-200 text-slate-100 dark:text-slate-900 text-center py-2 rounded-md text-sm font-medium transition-colors duration-200">
                                    Play
                                </a>
                                <button wire:click="startDemo('{{ $game['slug'] }}')"
                                        class="px-3 py-2 border border-slate-300 dark:border-slate-600 hover:border-slate-400 dark:hover:border-slate-500 text-slate-700 dark:text-slate-300 rounded-md text-sm transition-colors duration-200">
                                    Demo
                                </button>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    <!-- Technologies & Features -->
    <section class="py-20 bg-white dark:bg-slate-800">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-2xl md:text-3xl font-light text-slate-900 dark:text-slate-100 mb-4 tracking-tight">
                    Built with Care
                </h2>
                <p class="text-base text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                    Modern technologies and thoughtful engineering for reliable, enjoyable gameplay.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- AI Technology -->
                <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
                    <div class="text-2xl mb-3 text-slate-600 dark:text-slate-400">🤖</div>
                    <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-3">Intelligent AI</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Minimax algorithms with multiple difficulty levels for engaging gameplay.</p>
                    <ul class="text-xs text-slate-500 dark:text-slate-500 space-y-1">
                        <li>• Strategic move analysis</li>
                        <li>• Adaptive difficulty</li>
                        <li>• Quick response times</li>
                    </ul>
                </div>
                
                <!-- Professional UX -->
                <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
                    <div class="text-2xl mb-3 text-slate-600 dark:text-slate-400">🎨</div>
                    <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-3">Clean Interface</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Intuitive controls and responsive design for all devices.</p>
                    <ul class="text-xs text-slate-500 dark:text-slate-500 space-y-1">
                        <li>• Drag & drop interactions</li>
                        <li>• Smooth animations</li>
                        <li>• Mobile optimized</li>
                    </ul>
                </div>
                
                <!-- Comprehensive Testing -->
                <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
                    <div class="text-2xl mb-3 text-slate-600 dark:text-slate-400">🧪</div>
                    <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-3">Well Tested</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Comprehensive testing ensures reliable gameplay.</p>
                    <ul class="text-xs text-slate-500 dark:text-slate-500 space-y-1">
                        <li>• Automated test suites</li>
                        <li>• Edge case coverage</li>
                        <li>• Performance validated</li>
                    </ul>
                </div>
                
                <!-- Modern Architecture -->
                <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
                    <div class="text-2xl mb-3 text-slate-600 dark:text-slate-400">🏗️</div>
                    <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-3">Modern Stack</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Clean architecture with separation of concerns.</p>
                    <ul class="text-xs text-slate-500 dark:text-slate-500 space-y-1">
                        <li>• Laravel framework</li>
                        <li>• Component-based design</li>
                        <li>• Maintainable code</li>
                    </ul>
                </div>
                
                <!-- Advanced Features -->
                <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
                    <div class="text-2xl mb-3 text-slate-600 dark:text-slate-400">⚡</div>
                    <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-3">Helpful Features</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Thoughtful features to enhance the playing experience.</p>
                    <ul class="text-xs text-slate-500 dark:text-slate-500 space-y-1">
                        <li>• Undo/redo system</li>
                        <li>• Intelligent hints</li>
                        <li>• Auto-play demos</li>
                    </ul>
                </div>
                
                <!-- Performance -->
                <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
                    <div class="text-2xl mb-3 text-slate-600 dark:text-slate-400">🚀</div>
                    <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-3">Fast & Efficient</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Optimized algorithms for smooth, responsive gameplay.</p>
                    <ul class="text-xs text-slate-500 dark:text-slate-500 space-y-1">
                        <li>• Quick AI responses</li>
                        <li>• Smooth animations</li>
                        <li>• Efficient rendering</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-20 bg-slate-100 dark:bg-slate-900">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl md:text-3xl font-light text-slate-900 dark:text-slate-100 mb-4 tracking-tight">
                Ready to Play?
            </h2>
            <p class="text-base text-slate-600 dark:text-slate-400 mb-8">
                No downloads or registration required. Simply choose a game and start playing.
            </p>
            <a href="{{ url('/games') }}" 
               class="inline-block bg-slate-900 dark:bg-slate-100 hover:bg-slate-800 dark:hover:bg-slate-200 text-slate-100 dark:text-slate-900 px-8 py-3 rounded-lg text-base font-medium transition-colors duration-200">
                Browse All Games
            </a>
        </div>
    </section>

    <!-- Demo Modal -->
    @if($showDemo)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
             wire:click="closeDemo">
            <div class="bg-white dark:bg-slate-800 rounded-lg p-6 max-w-4xl w-full max-h-[90vh] overflow-auto border border-slate-200 dark:border-slate-700"
                 wire:click.stop>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-medium text-slate-900 dark:text-slate-100">{{ $this->findGame($demoGame)['name'] ?? 'Game' }} Demo</h3>
                    <button wire:click="closeDemo" 
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl">&times;</button>
                </div>
                
                <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-6 mb-6 border border-slate-200 dark:border-slate-700">
                    @if($demoGame)
                        <livewire:games.auto-demo :game="$demoGame" />
                    @else
                        <div class="text-center text-slate-600 dark:text-slate-400">
                            <div class="text-4xl mb-4">🎮</div>
                            <p class="text-base">Interactive demo will be loaded here</p>
                            <p class="text-sm mt-2">Auto-play demonstration with AI gameplay</p>
                        </div>
                    @endif
                </div>
                
                <div class="flex gap-3">
                    <a href="{{ url('/' . $demoGame) }}" 
                       class="flex-1 bg-slate-900 dark:bg-slate-100 hover:bg-slate-800 dark:hover:bg-slate-200 text-slate-100 dark:text-slate-900 text-center py-3 rounded-lg font-medium transition-colors duration-200">
                        Play Full Game
                    </a>
                    <button wire:click="closeDemo"
                            class="px-6 py-3 border border-slate-300 dark:border-slate-600 hover:border-slate-400 dark:hover:border-slate-500 text-slate-700 dark:text-slate-300 rounded-lg transition-colors duration-200">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

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
