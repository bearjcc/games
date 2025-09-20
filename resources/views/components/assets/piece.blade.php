@props([
    'color' => 'Black',
    'style' => 'single',
    'index' => 0,
    'alt' => null,
    'class' => '',
    'width' => null,
    'height' => null,
    'loading' => 'lazy'
])

@php
    use App\Services\AssetManager;
    
    $assetUrl = AssetManager::getPieceAsset($color, $style, $index);
    $altText = $alt ?? "{$color} piece ({$style} style)";
    
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
