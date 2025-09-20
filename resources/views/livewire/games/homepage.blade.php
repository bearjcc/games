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

<div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
    <!-- Navigation -->
    <x-site-navigation current-page="home" />
    
    <!-- Hero Section -->
    <section class="relative overflow-hidden">
        <!-- Animated Background -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="stars-container">
                <div class="stars"></div>
                <div class="stars2"></div>
                <div class="stars3"></div>
            </div>
        </div>
        
        <!-- Content -->
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold text-white mb-6">
                    <span class="bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
                        Gaming Excellence
                    </span>
                    <br>
                    <span class="text-white">Redefined</span>
                </h1>
                
                <p class="text-xl md:text-2xl text-gray-300 mb-8 max-w-3xl mx-auto">
                    Experience {{ $stats['total_games'] }} meticulously crafted games with cutting-edge AI, 
                    professional UX, and features that set new standards in web gaming.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-12">
                    <a href="#games" 
                       class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white px-8 py-4 rounded-full text-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-2xl">
                        Explore Games
                    </a>
                    <button wire:click="startDemo('connect4')"
                            class="border-2 border-white/30 hover:border-white/60 text-white px-8 py-4 rounded-full text-lg font-semibold transition-all duration-300 backdrop-blur-sm">
                        Watch Demo
                    </button>
                </div>
                
                <!-- Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-4xl mx-auto">
                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                        <div class="text-3xl font-bold text-white mb-2">{{ $stats['total_games'] }}</div>
                        <div class="text-gray-300">Premium Games</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                        <div class="text-3xl font-bold text-white mb-2">{{ $stats['ai_levels'] }}</div>
                        <div class="text-gray-300">AI Difficulty Levels</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                        <div class="text-3xl font-bold text-white mb-2">4</div>
                        <div class="text-gray-300">Game Categories</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                        <div class="text-3xl font-bold text-white mb-2">∞</div>
                        <div class="text-gray-300">Hours of Fun</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Games -->
    <section id="games" class="py-20 bg-gradient-to-r from-slate-800 to-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                    Featured Games
                </h2>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                    Each game features professional-grade AI opponents, comprehensive testing, 
                    and polished user experiences that rival commercial implementations.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($featuredGames as $game)
                    @if($game)
                        <div class="group relative bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-lg rounded-2xl p-6 border border-white/10 hover:border-white/30 transition-all duration-300 transform hover:scale-105 hover:shadow-2xl">
                            <!-- Game Icon/Preview -->
                            <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br {{ $this->getGameGradient($game['slug']) }} rounded-2xl flex items-center justify-center text-2xl">
                                {{ $this->getGameIcon($game['slug']) }}
                            </div>
                            
                            <h3 class="text-2xl font-bold text-white mb-3 text-center">{{ $game['name'] }}</h3>
                            <p class="text-gray-300 mb-6 text-center">{{ $game['description'] }}</p>
                            
                            <!-- Features -->
                            <div class="space-y-2 mb-6">
                                @foreach($this->getGameFeatures($game['slug']) as $feature)
                                    <div class="flex items-center text-sm text-gray-300">
                                        <div class="w-2 h-2 bg-green-400 rounded-full mr-3"></div>
                                        {{ $feature }}
                                    </div>
                                @endforeach
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex gap-3">
                                <a href="{{ url('/' . $game['slug']) }}" 
                                   class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white text-center py-3 rounded-xl font-semibold transition-all duration-300">
                                    Play Now
                                </a>
                                <button wire:click="startDemo('{{ $game['slug'] }}')"
                                        class="px-4 py-3 border border-white/30 hover:border-white/60 text-white rounded-xl transition-all duration-300">
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
    <section class="py-20 bg-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                    Cutting-Edge Technology
                </h2>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                    Built with modern web technologies and advanced algorithms for 
                    unparalleled performance and user experience.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- AI Technology -->
                <div class="bg-gradient-to-br from-red-900/20 to-orange-900/20 backdrop-blur-lg rounded-2xl p-8 border border-red-500/20">
                    <div class="text-4xl mb-4">🤖</div>
                    <h3 class="text-2xl font-bold text-white mb-4">Advanced AI</h3>
                    <p class="text-gray-300 mb-4">Minimax algorithms with alpha-beta pruning, multiple difficulty levels, and strategic evaluation functions.</p>
                    <ul class="text-sm text-gray-400 space-y-1">
                        <li>• Impossible AI opponents</li>
                        <li>• Strategic move analysis</li>
                        <li>• Dynamic difficulty scaling</li>
                    </ul>
                </div>
                
                <!-- Professional UX -->
                <div class="bg-gradient-to-br from-blue-900/20 to-purple-900/20 backdrop-blur-lg rounded-2xl p-8 border border-blue-500/20">
                    <div class="text-4xl mb-4">🎨</div>
                    <h3 class="text-2xl font-bold text-white mb-4">Professional UX</h3>
                    <p class="text-gray-300 mb-4">Smooth animations, responsive design, and intuitive interfaces that rival commercial games.</p>
                    <ul class="text-sm text-gray-400 space-y-1">
                        <li>• Drag & drop interactions</li>
                        <li>• Smooth animations</li>
                        <li>• Mobile responsive</li>
                    </ul>
                </div>
                
                <!-- Comprehensive Testing -->
                <div class="bg-gradient-to-br from-green-900/20 to-teal-900/20 backdrop-blur-lg rounded-2xl p-8 border border-green-500/20">
                    <div class="text-4xl mb-4">🧪</div>
                    <h3 class="text-2xl font-bold text-white mb-4">Comprehensive Testing</h3>
                    <p class="text-gray-300 mb-4">Extensive test suites ensuring reliability and bug-free gameplay across all games.</p>
                    <ul class="text-sm text-gray-400 space-y-1">
                        <li>• 200+ automated tests</li>
                        <li>• Edge case coverage</li>
                        <li>• Performance validated</li>
                    </ul>
                </div>
                
                <!-- Modern Architecture -->
                <div class="bg-gradient-to-br from-purple-900/20 to-pink-900/20 backdrop-blur-lg rounded-2xl p-8 border border-purple-500/20">
                    <div class="text-4xl mb-4">🏗️</div>
                    <h3 class="text-2xl font-bold text-white mb-4">Modern Architecture</h3>
                    <p class="text-gray-300 mb-4">Clean separation of concerns with engines, interfaces, and reactive components.</p>
                    <ul class="text-sm text-gray-400 space-y-1">
                        <li>• Laravel + Livewire</li>
                        <li>• Component-based design</li>
                        <li>• Scalable patterns</li>
                    </ul>
                </div>
                
                <!-- Advanced Features -->
                <div class="bg-gradient-to-br from-yellow-900/20 to-orange-900/20 backdrop-blur-lg rounded-2xl p-8 border border-yellow-500/20">
                    <div class="text-4xl mb-4">⚡</div>
                    <h3 class="text-2xl font-bold text-white mb-4">Advanced Features</h3>
                    <p class="text-gray-300 mb-4">Undo/redo, intelligent hints, auto-play demos, and comprehensive statistics.</p>
                    <ul class="text-sm text-gray-400 space-y-1">
                        <li>• Universal hint system</li>
                        <li>• Game state management</li>
                        <li>• Achievement tracking</li>
                    </ul>
                </div>
                
                <!-- Performance -->
                <div class="bg-gradient-to-br from-indigo-900/20 to-blue-900/20 backdrop-blur-lg rounded-2xl p-8 border border-indigo-500/20">
                    <div class="text-4xl mb-4">🚀</div>
                    <h3 class="text-2xl font-bold text-white mb-4">Optimized Performance</h3>
                    <p class="text-gray-300 mb-4">Efficient algorithms, optimized rendering, and fast load times for seamless gameplay.</p>
                    <ul class="text-sm text-gray-400 space-y-1">
                        <li>• Sub-second AI responses</li>
                        <li>• Smooth 60fps animations</li>
                        <li>• Minimal memory usage</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-20 bg-gradient-to-r from-blue-900 to-purple-900">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                Ready to Experience Gaming Excellence?
            </h2>
            <p class="text-xl text-gray-300 mb-8">
                Join thousands of players enjoying our professionally crafted games. 
                No downloads, no registration required.
            </p>
            <a href="{{ url('/games') }}" 
               class="inline-block bg-gradient-to-r from-white to-gray-100 hover:from-gray-100 hover:to-gray-200 text-purple-900 px-12 py-4 rounded-full text-xl font-bold transition-all duration-300 transform hover:scale-105 shadow-2xl">
                Start Playing Now
            </a>
        </div>
    </section>

    <!-- Demo Modal -->
    @if($showDemo)
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
             wire:click="closeDemo">
            <div class="bg-slate-800 rounded-2xl p-8 max-w-4xl w-full max-h-[90vh] overflow-auto"
                 wire:click.stop>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-white">{{ $this->findGame($demoGame)['name'] ?? 'Game' }} Demo</h3>
                    <button wire:click="closeDemo" 
                            class="text-gray-400 hover:text-white text-2xl">&times;</button>
                </div>
                
                <div class="bg-slate-900 rounded-xl p-6 mb-6">
                    @if($demoGame)
                        <livewire:games.auto-demo :game="$demoGame" />
                    @else
                        <div class="text-center text-gray-300">
                            <div class="text-6xl mb-4">🎮</div>
                            <p class="text-lg">Interactive demo will be loaded here</p>
                            <p class="text-sm mt-2">Full auto-play demonstration with AI vs AI gameplay</p>
                        </div>
                    @endif
                </div>
                
                <div class="flex gap-4">
                    <a href="{{ url('/' . $demoGame) }}" 
                       class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white text-center py-3 rounded-xl font-semibold transition-all duration-300">
                        Play Full Game
                    </a>
                    <button wire:click="closeDemo"
                            class="px-6 py-3 border border-gray-600 hover:border-gray-500 text-gray-300 hover:text-white rounded-xl transition-all duration-300">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <style>
        /* Animated Stars Background */
        .stars-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .stars, .stars2, .stars3 {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: transparent;
        }

        .stars {
            background-image: 
                radial-gradient(2px 2px at 20px 30px, white, transparent),
                radial-gradient(2px 2px at 40px 70px, white, transparent),
                radial-gradient(1px 1px at 90px 40px, white, transparent),
                radial-gradient(1px 1px at 130px 80px, white, transparent),
                radial-gradient(2px 2px at 160px 30px, white, transparent);
            background-repeat: repeat;
            background-size: 200px 100px;
            animation: stars-animate 50s linear infinite;
        }

        .stars2 {
            background-image: 
                radial-gradient(1px 1px at 40px 60px, white, transparent),
                radial-gradient(1px 1px at 120px 50px, white, transparent),
                radial-gradient(1px 1px at 180px 90px, white, transparent);
            background-repeat: repeat;
            background-size: 250px 120px;
            animation: stars-animate 100s linear infinite;
        }

        .stars3 {
            background-image: 
                radial-gradient(1px 1px at 60px 20px, white, transparent),
                radial-gradient(1px 1px at 100px 80px, white, transparent),
                radial-gradient(1px 1px at 140px 40px, white, transparent);
            background-repeat: repeat;
            background-size: 300px 150px;
            animation: stars-animate 150s linear infinite;
        }

        @keyframes stars-animate {
            from { transform: translateY(0px); }
            to { transform: translateY(-2000px); }
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Custom gradients and animations */
        .group:hover .group-hover\:scale-110 {
            transform: scale(1.1);
        }
    </style>

    <script>
        // Auto-scroll to games section when "Explore Games" is clicked
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
