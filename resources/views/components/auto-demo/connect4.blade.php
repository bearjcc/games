<!-- Connect 4 Auto Demo Display -->
<div class="connect4-demo">
    <div class="board-grid">
        @for($row = 0; $row < 6; $row++)
            @for($col = 0; $col < 7; $col++)
                @php
                    $piece = $state['board'][$row][$col] ?? null;
                    $isLastMove = false;
                    
                    // Check if this is the last move
                    if (isset($state['lastMove']) && 
                        $state['lastMove']['row'] === $row && 
                        $state['lastMove']['column'] === $col) {
                        $isLastMove = true;
                    }
                @endphp
                
                <div class="board-cell {{ $isLastMove ? 'last-move' : '' }}">
                    @if($piece)
                        <div class="demo-piece {{ $piece }} {{ $isLastMove ? 'new-piece' : '' }}"></div>
                    @endif
                </div>
            @endfor
        @endfor
    </div>
</div>

<style>
    .connect4-demo .board-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        grid-template-rows: repeat(6, 1fr);
        gap: 4px;
        background: linear-gradient(135deg, #1e40af, #3730a3);
        padding: 12px;
        border-radius: 8px;
        max-width: 280px;
        margin: 0 auto;
    }

    .connect4-demo .board-cell {
        width: 32px;
        height: 32px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        transition: all 0.3s ease;
    }

    .connect4-demo .board-cell.last-move {
        background: rgba(34, 197, 94, 0.3);
        box-shadow: 0 0 10px rgba(34, 197, 94, 0.6);
    }

    .connect4-demo .demo-piece {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 1px solid rgba(255,255,255,0.3);
        transition: all 0.3s ease;
    }

    .connect4-demo .demo-piece.red {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    .connect4-demo .demo-piece.yellow {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
    }

    .connect4-demo .demo-piece.new-piece {
        animation: dropIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes dropIn {
        0% { transform: translateY(-200px) scale(0.8); }
        70% { transform: translateY(3px) scale(1.1); }
        100% { transform: translateY(0) scale(1); }
    }
</style>
