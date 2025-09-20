@props(['swipeTarget' => null, 'keyboardTarget' => null])

<div class="game-controls" 
     x-data="gameControls({{ $swipeTarget ? "'{$swipeTarget}'" : 'null' }})"
     @if($keyboardTarget) x-on:keydown.window="handleKeyboard($event, '{{ $keyboardTarget }}')" @endif
     x-on:touchstart="onTouchStart($event)"
     x-on:touchend="onTouchEnd($event)">
    {{ $slot }}
</div>

<script>
function gameControls(swipeTarget) {
    return {
        startX: 0,
        startY: 0,
        threshold: 50,
        
        onTouchStart(e) {
            if (!swipeTarget) return;
            this.startX = e.touches[0].clientX;
            this.startY = e.touches[0].clientY;
        },
        
        onTouchEnd(e) {
            if (!swipeTarget || !this.startX || !this.startY) return;
            
            let endX = e.changedTouches[0].clientX;
            let endY = e.changedTouches[0].clientY;
            let diffX = this.startX - endX;
            let diffY = this.startY - endY;
            
            if (Math.abs(diffX) > Math.abs(diffY)) {
                if (Math.abs(diffX) > this.threshold) {
                    let direction = diffX > 0 ? 'left' : 'right';
                    this.$wire.call(swipeTarget, direction);
                }
            } else {
                if (Math.abs(diffY) > this.threshold) {
                    let direction = diffY > 0 ? 'up' : 'down';
                    this.$wire.call(swipeTarget, direction);
                }
            }
            
            this.startX = 0;
            this.startY = 0;
        },
        
        handleKeyboard(e, target) {
            // Override in specific games
        }
    }
}
</script>
