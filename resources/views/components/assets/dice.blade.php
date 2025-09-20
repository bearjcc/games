@props([
    'color' => 'red',
    'style' => 'border',
    'value' => 1,
    'alt' => null,
    'class' => '',
    'width' => null,
    'height' => null,
    'loading' => 'lazy'
])

@php
    use App\Services\AssetManager;
    
    $assetUrl = AssetManager::getDiceAsset($color, $style, $value);
    $altText = $alt ?? "{$color} die showing {$value}";
    
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
