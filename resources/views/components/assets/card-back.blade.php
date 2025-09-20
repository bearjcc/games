@props([
    'color' => 'blue',
    'style' => 1,
    'alt' => null,
    'class' => '',
    'width' => null,
    'height' => null,
    'loading' => 'lazy'
])

@php
    use App\Services\AssetManager;
    
    $assetUrl = AssetManager::getCardBackAsset($color, $style);
    $altText = $alt ?? "{$color} card back";
    
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
