@props([
    'color' => 'Blue',
    'style' => 'standard',
    'alt' => null,
    'class' => '',
    'width' => null,
    'height' => null,
    'loading' => 'lazy'
])

@php
    use App\Services\AssetManager;
    
    $assetUrl = AssetManager::getChipAsset($color, $style);
    $altText = $alt ?? "{$color} poker chip";
    
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
