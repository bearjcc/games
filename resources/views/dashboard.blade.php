<?php

use App\Services\UserBestScoreService;
use App\Games\GameRegistry;
use Livewire\Volt\Component;

new class extends Component {
    public array $scores = [];
    public array $games = [];
    
    public function mount(): void
    {
        if (auth()->check()) {
            $this->loadUserScores();
            $this->loadGames();
        }
    }
    
    private function loadUserScores(): void
    {
        $scoreService = app(UserBestScoreService::class);
        $this->games = app(GameRegistry::class)->listMetadata();
        
        foreach ($this->games as $game) {
            $score = $scoreService->get(auth()->user(), $game['slug']);
            if ($score > 0) {
                $this->scores[$game['slug']] = [
                    'name' => $game['name'],
                    'score' => $score,
                    'slug' => $game['slug']
                ];
            }
        }
        
        // Sort by score (highest first)
        uasort($this->scores, fn($a, $b) => $b['score'] <=> $a['score']);
    }
    
    private function loadGames(): void
    {
        $this->games = app(GameRegistry::class)->listMetadata();
    }
}; ?>

<x-layouts.app :title="__('Dashboard')">
    <div class="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-950">
        <!-- Navigation -->
        <x-site-navigation current-page="dashboard" />
        
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center mb-12">
                <h1 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-slate-100 mb-4">
                    Welcome back, {{ auth()->user()->name }}!
                </h1>
                <p class="text-lg text-slate-600 dark:text-slate-400">
                    Track your progress and see your best scores across all games.
                </p>
            </div>

            <!-- High Scores Section -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 mb-8">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">
                        🏆 Your Best Scores
                    </h2>
                </div>
                
                @if(count($scores) > 0)
                    <div class="p-6">
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            @foreach($scores as $scoreData)
                                <div class="bg-slate-50 dark:bg-slate-700 rounded-lg p-4 border border-slate-200 dark:border-slate-600">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="font-medium text-slate-900 dark:text-slate-100">
                                            {{ $scoreData['name'] }}
                                        </h3>
                                        <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                            {{ number_format($scoreData['score']) }}
                                        </span>
                                    </div>
                                    <a href="{{ url('/' . $scoreData['slug']) }}" 
                                       class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                        Play Again →
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="p-6 text-center">
                        <div class="text-4xl mb-4">🎮</div>
                        <p class="text-slate-600 dark:text-slate-400 mb-4">
                            No scores yet! Start playing games to track your progress.
                        </p>
                        <a href="{{ url('/games') }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200">
                            Browse Games
                        </a>
                    </div>
                @endif
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 p-6">
                    <div class="flex items-center">
                        <div class="text-3xl mr-4">🎯</div>
                        <div>
                            <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                                {{ count($scores) }}
                            </div>
                            <div class="text-sm text-slate-600 dark:text-slate-400">
                                Games with Scores
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 p-6">
                    <div class="flex items-center">
                        <div class="text-3xl mr-4">🏅</div>
                        <div>
                            <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                                {{ count($scores) > 0 ? max(array_column($scores, 'score')) : 0 }}
                            </div>
                            <div class="text-sm text-slate-600 dark:text-slate-400">
                                Highest Score
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 p-6">
                    <div class="flex items-center">
                        <div class="text-3xl mr-4">🎮</div>
                        <div>
                            <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                                {{ count($games) }}
                            </div>
                            <div class="text-sm text-slate-600 dark:text-slate-400">
                                Available Games
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Games -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">
                        🎲 Popular Games
                    </h2>
                </div>
                
                <div class="p-6">
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        @foreach(array_slice($games, 0, 8) as $game)
                            <a href="{{ url('/' . $game['slug']) }}" 
                               class="bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 rounded-lg p-4 border border-slate-200 dark:border-slate-600 transition-colors duration-200 group">
                                <div class="text-2xl mb-2 group-hover:scale-110 transition-transform duration-200">
                                    @switch($game['slug'])
                                        @case('checkers') ⚫ @break
                                        @case('connect4') 🔴 @break
                                        @case('solitaire') 🃏 @break
                                        @case('nine-mens-morris') ⚫ @break
                                        @case('peg-solitaire') 🔵 @break
                                        @case('tic-tac-toe') ❌ @break
                                        @case('2048') 🔢 @break
                                        @case('war') ⚔️ @break
                                        @default 🎮
                                    @endswitch
                                </div>
                                <h3 class="font-medium text-slate-900 dark:text-slate-100 text-sm">
                                    {{ $game['name'] }}
                                </h3>
                                @if(isset($scores[$game['slug']]))
                                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                        Best: {{ number_format($scores[$game['slug']]['score']) }}
                                    </p>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>