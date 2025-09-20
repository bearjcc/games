@props([
    'suit' => 'hearts',
    'rank' => 'A',
    'alt' => null,
    'class' => '',
    'width' => null,
    'height' => null,
    'loading' => 'lazy'
])

@php
    use App\Services\AssetManager;
    
    $assetUrl = AssetManager::getCardAsset($suit, $rank);
    $altText = $alt ?? "{$rank} of {$suit}";
    
    $styleAttr = '';
    if ($width) $styleAttr .= "width: {$width}; ";
    if ($height) $styleAttr .= "height: {$height}; ";
@endphp

<img 
    src="{{ $assetUrl }}" 
    alt="{{ $altText }}"
    class="{{ $class }}"
    @if($styleAttr) style="{{ $styleAttr }}" @endif
    loading="{{ $loading }}"
    {{ $attributes }}
/>
