<!-- Tic Tac Toe Auto Demo Display -->
<div class="tic-tac-toe-demo">
    <div class="board-grid">
        @for($row = 0; $row < 3; $row++)
            @for($col = 0; $col < 3; $col++)
                @php
                    $piece = $state['board'][$row][$col] ?? null;
                    $cellIndex = $row * 3 + $col;
                    $isLastMove = isset($state['lastMove']) && $state['lastMove'] === $cellIndex;
                    $isWinningCell = false;
                    
                    // Check if this cell is part of winning line
                    if (isset($state['winningLine'])) {
                        foreach ($state['winningLine'] as $winCell) {
                            if ($winCell === $cellIndex) {
                                $isWinningCell = true;
                                break;
                            }
                        }
                    }
                @endphp
                
                <div class="board-cell {{ $isLastMove ? 'last-move' : '' }} {{ $isWinningCell ? 'winning' : '' }}">
                    @if($piece === 'X')
                        <svg class="piece-x {{ $isLastMove ? 'new-piece' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    @elseif($piece === 'O')
                        <svg class="piece-o {{ $isLastMove ? 'new-piece' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <circle cx="12" cy="12" r="10"></circle>
                        </svg>
                    @endif
                </div>
            @endfor
        @endfor
    </div>
</div>

<style>
    .tic-tac-toe-demo .board-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        grid-template-rows: repeat(3, 1fr);
        gap: 8px;
        max-width: 180px;
        margin: 0 auto;
        padding: 12px;
        background: linear-gradient(135deg, #374151, #4b5563);
        border-radius: 8px;
    }

    .tic-tac-toe-demo .board-cell {
        width: 48px;
        height: 48px;
        background: rgba(255,255,255,0.1);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .tic-tac-toe-demo .board-cell.last-move {
        background: rgba(59, 130, 246, 0.3);
        border-color: rgba(59, 130, 246, 0.6);
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.4);
    }

    .tic-tac-toe-demo .board-cell.winning {
        background: rgba(34, 197, 94, 0.3);
        border-color: rgba(34, 197, 94, 0.6);
        box-shadow: 0 0 10px rgba(34, 197, 94, 0.6);
        animation: winPulse 1s ease-in-out infinite alternate;
    }

    .tic-tac-toe-demo .piece-x,
    .tic-tac-toe-demo .piece-o {
        width: 32px;
        height: 32px;
        color: white;
        transition: all 0.3s ease;
    }

    .tic-tac-toe-demo .piece-x {
        color: #ef4444;
    }

    .tic-tac-toe-demo .piece-o {
        color: #3b82f6;
    }

    .tic-tac-toe-demo .new-piece {
        animation: pieceAppear 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes pieceAppear {
        0% { transform: scale(0) rotate(180deg); opacity: 0; }
        50% { transform: scale(1.2) rotate(90deg); opacity: 0.8; }
        100% { transform: scale(1) rotate(0deg); opacity: 1; }
    }

    @keyframes winPulse {
        from { 
            transform: scale(1);
            box-shadow: 0 0 10px rgba(34, 197, 94, 0.6);
        }
        to { 
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(34, 197, 94, 0.8);
        }
    }
</style>
