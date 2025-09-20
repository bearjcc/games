@props(['modes', 'currentMode' => null, 'wireTarget' => 'newGame'])

<div class="mb-8">
    <div class="text-center mb-4">
        <div class="text-sm opacity-80 mb-2">Choose Your Challenge</div>
        <div class="flex justify-center gap-2 flex-wrap">
            @foreach($modes as $mode => $config)
                <flux:button 
                    wire:click="{{ $wireTarget }}('{{ $mode }}')" 
                    variant="{{ $currentMode === $mode ? 'primary' : 'outline' }}" 
                    size="sm">
                    @if(isset($config['icon']))
                        {{ $config['icon'] }}
                    @endif
                    {{ $config['label'] }}
                </flux:button>
            @endforeach
        </div>
    </div>
</div>
