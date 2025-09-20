<?php

use App\Games\GameRegistry;
use Livewire\Volt\Component;

new class extends Component {
    public string $slug = '';
    public ?array $game = null;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
        $g = app(GameRegistry::class)->getBySlug($slug);
        $this->game = $g ? [
            'slug' => $g->slug(),
            'name' => $g->name(),
            'description' => $g->description(),
        ] : null;
        abort_if($this->game === null, 404);
    }
}; ?>

<section class="max-w-2xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-2">{{ $game['name'] }}</h1>
    <p class="opacity-80 mb-4">{{ $game['description'] }}</p>

    @if ($slug === 'tic-tac-toe')
        <a href="{{ url('/tic-tac-toe') }}" class="text-primary-600 underline">Play now</a>
    @elseif ($slug === '2048')
        <a href="{{ url('/2048') }}" class="text-primary-600 underline">Play now</a>
    @endif

    <div class="mt-6"><a class="underline" href="{{ url('/games') }}">Back to games</a></div>
</section>


