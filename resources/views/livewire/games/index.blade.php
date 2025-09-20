<?php

use App\Games\GameRegistry;
use Livewire\Volt\Component;

new class extends Component {
    public array $games = [];
    public function mount(): void
    {
        $this->games = app(GameRegistry::class)->listMetadata();
    }
}; ?>

<section class="max-w-4xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-4">Games</h1>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach ($games as $game)
            <a href="{{ url('/'.$game['slug']) }}" class="block rounded border p-4 hover:bg-gray-50 dark:hover:bg-gray-900">
                <div class="text-lg font-medium">{{ $game['name'] }}</div>
                <div class="text-sm opacity-80">{{ $game['description'] }}</div>
            </a>
        @endforeach
    </div>
    <div class="mt-6 opacity-70 text-sm">A growing collection of classic games.</div>
    <div class="mt-2"><a href="{{ url('/') }}" class="underline">Back to Home</a></div>
    
</section>


