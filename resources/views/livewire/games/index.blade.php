<?php

use App\Games\GameRegistry;
use Livewire\Volt\Component;

new class extends Component {
    public array $games = [];
    
    public function mount(): void
    {
        $this->games = app(GameRegistry::class)->listMetadata();
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
            default => 'from-gray-600 to-slate-700'
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
    <x-site-navigation current-page="games" />

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">
                Game Collection
            </h1>
            <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                {{ count($games) }} professionally crafted games featuring advanced AI, 
                beautiful UI, and comprehensive testing. All games are free to play.
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach ($games as $game)
                <a href="{{ url('/'.$game['slug']) }}" 
                   class="group bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-lg rounded-2xl p-6 border border-white/10 hover:border-white/30 transition-all duration-300 transform hover:scale-105 hover:shadow-2xl">
                    
                    <!-- Game Icon -->
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br {{ $this->getGameGradient($game['slug']) }} rounded-2xl flex items-center justify-center text-2xl">
                        {{ $this->getGameIcon($game['slug']) }}
                    </div>
                    
                    <h3 class="text-2xl font-bold text-white mb-3 text-center">{{ $game['name'] }}</h3>
                    <p class="text-gray-300 mb-6 text-center">{{ $game['description'] }}</p>
                    
                    <!-- Features -->
                    <div class="space-y-2">
                        @foreach($this->getGameFeatures($game['slug']) as $feature)
                            <div class="flex items-center text-sm text-gray-300">
                                <div class="w-2 h-2 bg-green-400 rounded-full mr-3"></div>
                                {{ $feature }}
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Play Button -->
                    <div class="mt-6">
                        <div class="bg-gradient-to-r from-blue-600 to-purple-600 group-hover:from-blue-700 group-hover:to-purple-700 text-white text-center py-3 rounded-xl font-semibold transition-all duration-300">
                            Play Now
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
        
        <div class="text-center mt-16">
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-8 border border-white/20 max-w-2xl mx-auto">
                <h3 class="text-2xl font-bold text-white mb-4">Why Our Games?</h3>
                <div class="text-gray-300 space-y-2">
                    <p>✨ Professional-grade AI opponents with multiple difficulty levels</p>
                    <p>🎨 Beautiful, responsive UI that works on all devices</p>
                    <p>🧪 Extensively tested with comprehensive test suites</p>
                    <p>⚡ Modern web technologies for smooth performance</p>
                    <p>🎮 Advanced features like undo/redo, hints, and auto-play demos</p>
                </div>
            </div>
        </div>
    </section>
</div>



