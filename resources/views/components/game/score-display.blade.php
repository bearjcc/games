@props(['label', 'value', 'color' => 'blue', 'animated' => false])

<div class="text-center {{ $animated ? 'score-animated' : '' }}">
    <div class="text-sm opacity-80 mb-2">{{ $label }}</div>
    <div class="text-2xl font-bold 
                @if($color === 'blue') text-blue-600 dark:text-blue-400
                @elseif($color === 'red') text-red-500 dark:text-red-400  
                @elseif($color === 'green') text-green-600 dark:text-green-400
                @elseif($color === 'yellow') text-yellow-600 dark:text-yellow-400
                @else text-gray-600 dark:text-gray-400
                @endif">
        {{ $value }}
    </div>
</div>

@if($animated)
<style>
    .score-animated {
        transition: transform 0.3s ease-in-out;
    }
    .score-animated:hover {
        transform: scale(1.05);
    }
</style>
@endif
