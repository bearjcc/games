<!-- 2048 Auto Demo Display -->
<div class="game-2048-demo">
    <div class="board-grid">
        @for($row = 0; $row < 4; $row++)
            @for($col = 0; $col < 4; $col++)
                @php
                    $value = $state['board'][$row][$col] ?? 0;
                    $cellClass = $value > 0 ? 'tile-' . $value : 'empty-tile';
                @endphp
                
                <div class="board-cell">
                    @if($value > 0)
                        <div class="tile {{ $cellClass }}">
                            {{ $value }}
                        </div>
                    @endif
                </div>
            @endfor
        @endfor
    </div>
</div>

<style>
    .game-2048-demo .board-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-template-rows: repeat(4, 1fr);
        gap: 6px;
        max-width: 200px;
        margin: 0 auto;
        padding: 12px;
        background: #bbada0;
        border-radius: 8px;
    }

    .game-2048-demo .board-cell {
        width: 40px;
        height: 40px;
        background: rgba(238, 228, 218, 0.35);
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .game-2048-demo .tile {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 12px;
        border-radius: 4px;
        transition: all 0.3s ease;
        animation: tileAppear 0.3s ease;
    }

    /* Tile Colors */
    .game-2048-demo .tile-2 { background: #eee4da; color: #776e65; }
    .game-2048-demo .tile-4 { background: #ede0c8; color: #776e65; }
    .game-2048-demo .tile-8 { background: #f2b179; color: #f9f6f2; }
    .game-2048-demo .tile-16 { background: #f59563; color: #f9f6f2; }
    .game-2048-demo .tile-32 { background: #f67c5f; color: #f9f6f2; }
    .game-2048-demo .tile-64 { background: #f65e3b; color: #f9f6f2; }
    .game-2048-demo .tile-128 { background: #edcf72; color: #f9f6f2; font-size: 10px; }
    .game-2048-demo .tile-256 { background: #edcc61; color: #f9f6f2; font-size: 10px; }
    .game-2048-demo .tile-512 { background: #edc850; color: #f9f6f2; font-size: 10px; }
    .game-2048-demo .tile-1024 { background: #edc53f; color: #f9f6f2; font-size: 8px; }
    .game-2048-demo .tile-2048 { background: #edc22e; color: #f9f6f2; font-size: 8px; box-shadow: 0 0 10px rgba(237, 194, 46, 0.6); }

    @keyframes tileAppear {
        0% { transform: scale(0); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
</style>
