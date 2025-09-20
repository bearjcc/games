@props(['width' => '320px', 'height' => '320px', 'background' => '#2c3e50'])

<div class="mx-auto mb-8" style="width: {{ $width }}; height: {{ $height }};">
    <div class="game-board relative liminal-surface" 
         style="width: {{ $width }}; height: {{ $height }}; background: {{ $background }}; border-radius: 8px; padding: 20px;">
        {{ $slot }}
    </div>
</div>

<style>
    .liminal-surface {
        background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
        box-shadow: 
            0 8px 32px rgba(0,0,0,0.1),
            inset 0 1px 0 rgba(255,255,255,0.1);
    }
</style>
