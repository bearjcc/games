<?php

namespace App\Services;

use App\Games\Contracts\GameInterface;

/**
 * Universal Hint Engine for providing intelligent game suggestions
 * across different game types and complexities
 */
class HintEngine
{
    /**
     * Get hints for a game state
     */
    public static function getHints(GameInterface $game, array $state, array $options = []): array
    {
        $hints = [];
        $gameId = $game->id();
        
        // Route to game-specific hint logic
        switch ($gameId) {
            case 'solitaire':
                $hints = self::getSolitaireHints($state, $options);
                break;
                
            case '2048':
                $hints = self::get2048Hints($state, $options);
                break;
                
            case 'tic-tac-toe':
                $hints = self::getTicTacToeHints($state, $options);
                break;
                
            case 'nine-mens-morris':
                $hints = self::getNineMensMorrisHints($state, $options);
                break;
                
            case 'peg-solitaire':
                $hints = self::getPegSolitaireHints($state, $options);
                break;
                
            case 'connect4':
                $hints = self::getConnect4Hints($state, $options);
                break;
                
            default:
                $hints = self::getGenericHints($game, $state, $options);
                break;
        }
        
        // Apply universal hint filtering and ranking
        return self::rankAndFilterHints($hints, $options);
    }

    /**
     * Get sophisticated hints for Solitaire
     */
    private static function getSolitaireHints(array $state, array $options): array
    {
        $hints = [];
        $difficulty = $options['difficulty'] ?? 'beginner';
        
        // Immediate moves (always show these)
        $immediateHints = self::getSolitaireImmediateHints($state);
        $hints = array_merge($hints, $immediateHints);
        
        // Strategic hints based on difficulty
        if ($difficulty !== 'minimal') {
            $strategicHints = self::getSolitaireStrategicHints($state, $difficulty);
            $hints = array_merge($hints, $strategicHints);
        }
        
        // Advanced multi-step hints for experienced players
        if ($difficulty === 'expert') {
            $advancedHints = self::getSolitaireAdvancedHints($state);
            $hints = array_merge($hints, $advancedHints);
        }
        
        return $hints;
    }

    /**
     * Get immediate move hints for Solitaire
     */
    private static function getSolitaireImmediateHints(array $state): array
    {
        $hints = [];
        
        // Check for foundation moves (highest priority)
        $wasteCard = self::getWasteCard($state);
        if ($wasteCard) {
            foreach (['hearts', 'diamonds', 'clubs', 'spades'] as $suit) {
                if (self::canPlaceOnFoundation($wasteCard, $state['foundations'][$suit])) {
                    $hints[] = [
                        'type' => 'immediate',
                        'priority' => 10,
                        'action' => 'waste_to_foundation',
                        'description' => "Move {$wasteCard['rank']} of {$wasteCard['suit']} to foundation",
                        'target' => $suit,
                        'reasoning' => 'Foundation moves are always beneficial'
                    ];
                }
            }
        }
        
        // Check tableau to foundation moves
        for ($col = 0; $col < 7; $col++) {
            if (!empty($state['tableau'][$col])) {
                $topCard = end($state['tableau'][$col]);
                if ($topCard['faceUp']) {
                    foreach (['hearts', 'diamonds', 'clubs', 'spades'] as $suit) {
                        if (self::canPlaceOnFoundation($topCard, $state['foundations'][$suit])) {
                            $hints[] = [
                                'type' => 'immediate',
                                'priority' => 9,
                                'action' => 'tableau_to_foundation',
                                'description' => "Move {$topCard['rank']} of {$topCard['suit']} to foundation",
                                'source' => $col,
                                'target' => $suit,
                                'reasoning' => 'Clears tableau card and builds foundation'
                            ];
                        }
                    }
                }
            }
        }
        
        // Check for face-down cards that can be revealed
        for ($col = 0; $col < 7; $col++) {
            if (!empty($state['tableau'][$col])) {
                $topCard = end($state['tableau'][$col]);
                if (!$topCard['faceUp']) {
                    // See if we can move the covering card
                    $coveringCard = count($state['tableau'][$col]) > 1 ? 
                        $state['tableau'][$col][count($state['tableau'][$col]) - 2] : null;
                    
                    if ($coveringCard && $coveringCard['faceUp']) {
                        $hints[] = [
                            'type' => 'immediate',
                            'priority' => 8,
                            'action' => 'reveal_card',
                            'description' => "Move {$coveringCard['rank']} of {$coveringCard['suit']} to reveal face-down card",
                            'source' => $col,
                            'reasoning' => 'Revealing face-down cards opens new possibilities'
                        ];
                    }
                }
            }
        }
        
        return $hints;
    }

    /**
     * Get strategic hints for Solitaire
     */
    private static function getSolitaireStrategicHints(array $state, string $difficulty): array
    {
        $hints = [];
        
        // Look for King placements in empty columns
        if ($difficulty === 'intermediate' || $difficulty === 'expert') {
            $emptyColumns = [];
            for ($col = 0; $col < 7; $col++) {
                if (empty($state['tableau'][$col])) {
                    $emptyColumns[] = $col;
                }
            }
            
            if (!empty($emptyColumns)) {
                // Check waste for Kings
                $wasteCard = self::getWasteCard($state);
                if ($wasteCard && $wasteCard['rank'] === 'K') {
                    $hints[] = [
                        'type' => 'strategic',
                        'priority' => 7,
                        'action' => 'waste_to_tableau',
                        'description' => "Move King of {$wasteCard['suit']} to empty column",
                        'target' => $emptyColumns[0],
                        'reasoning' => 'Kings in empty columns create more building opportunities'
                    ];
                }
                
                // Check tableau for accessible Kings
                for ($col = 0; $col < 7; $col++) {
                    if (!empty($state['tableau'][$col])) {
                        $topCard = end($state['tableau'][$col]);
                        if ($topCard['faceUp'] && $topCard['rank'] === 'K' && count($state['tableau'][$col]) > 1) {
                            $hints[] = [
                                'type' => 'strategic',
                                'priority' => 6,
                                'action' => 'tableau_to_tableau',
                                'description' => "Move King of {$topCard['suit']} to empty column",
                                'source' => $col,
                                'target' => $emptyColumns[0],
                                'reasoning' => 'This may reveal a face-down card and improve tableau organization'
                            ];
                        }
                    }
                }
            }
        }
        
        // Look for sequence building opportunities
        $buildingHints = self::getSolitaireSequenceBuildingHints($state);
        $hints = array_merge($hints, $buildingHints);
        
        return $hints;
    }

    /**
     * Get sequence building hints for Solitaire
     */
    private static function getSolitaireSequenceBuildingHints(array $state): array
    {
        $hints = [];
        
        $wasteCard = self::getWasteCard($state);
        if (!$wasteCard) {
            return $hints;
        }
        
        // Check if waste card can build on tableau
        for ($col = 0; $col < 7; $col++) {
            if (empty($state['tableau'][$col])) {
                if ($wasteCard['rank'] === 'K') {
                    $hints[] = [
                        'type' => 'building',
                        'priority' => 5,
                        'action' => 'waste_to_tableau',
                        'description' => "Place King of {$wasteCard['suit']} in empty column {$col}",
                        'target' => $col,
                        'reasoning' => 'Start building a new sequence'
                    ];
                }
            } else {
                $topCard = end($state['tableau'][$col]);
                if ($topCard['faceUp'] && self::canPlaceOnTableau($wasteCard, $topCard)) {
                    $sequenceValue = self::evaluateSequenceValue($state, $col, $wasteCard);
                    $hints[] = [
                        'type' => 'building',
                        'priority' => $sequenceValue,
                        'action' => 'waste_to_tableau',
                        'description' => "Place {$wasteCard['rank']} of {$wasteCard['suit']} on {$topCard['rank']} of {$topCard['suit']}",
                        'target' => $col,
                        'reasoning' => 'Continues tableau sequence'
                    ];
                }
            }
        }
        
        return $hints;
    }

    /**
     * Get advanced multi-step hints for expert players
     */
    private static function getSolitaireAdvancedHints(array $state): array
    {
        $hints = [];
        
        // Look for complex multi-move sequences
        // This would involve analyzing potential move chains
        // For now, implement a basic version
        
        $hints[] = [
            'type' => 'advanced',
            'priority' => 3,
            'action' => 'multi_step',
            'description' => 'Consider drawing from stock to find better moves',
            'reasoning' => 'Sometimes patience reveals better opportunities'
        ];
        
        return $hints;
    }

    /**
     * Get hints for 2048
     */
    private static function get2048Hints(array $state, array $options): array
    {
        $hints = [];
        $grid = $state['grid'];
        
        // Corner strategy hint
        $cornerHint = self::get2048CornerStrategy($grid);
        if ($cornerHint) {
            $hints[] = $cornerHint;
        }
        
        // Merge opportunity hints
        $mergeHints = self::get2048MergeHints($grid);
        $hints = array_merge($hints, $mergeHints);
        
        return $hints;
    }

    /**
     * Get corner strategy hint for 2048
     */
    private static function get2048CornerStrategy(array $grid): ?array
    {
        // Find the highest tile
        $maxValue = 0;
        $maxPos = null;
        
        for ($row = 0; $row < 4; $row++) {
            for ($col = 0; $col < 4; $col++) {
                if ($grid[$row][$col] > $maxValue) {
                    $maxValue = $grid[$row][$col];
                    $maxPos = [$row, $col];
                }
            }
        }
        
        if (!$maxPos || $maxValue < 64) {
            return null;
        }
        
        // Check if highest tile is in a corner
        $corners = [[0,0], [0,3], [3,0], [3,3]];
        $isInCorner = in_array($maxPos, $corners);
        
        if (!$isInCorner) {
            return [
                'type' => 'strategic',
                'priority' => 8,
                'action' => 'corner_strategy',
                'description' => 'Try to move your highest tile to a corner',
                'reasoning' => 'Corner strategy helps maintain organization and prevents the highest tile from getting trapped'
            ];
        }
        
        return null;
    }

    /**
     * Get merge hints for 2048
     */
    private static function get2048MergeHints(array $grid): array
    {
        $hints = [];
        
        // Check for available merges in each direction
        $directions = [
            'up' => [-1, 0],
            'down' => [1, 0],
            'left' => [0, -1],
            'right' => [0, 1]
        ];
        
        foreach ($directions as $direction => $delta) {
            $mergeCount = self::count2048Merges($grid, $delta);
            if ($mergeCount > 0) {
                $hints[] = [
                    'type' => 'tactical',
                    'priority' => 5 + $mergeCount,
                    'action' => 'merge',
                    'description' => "Swipe {$direction} to merge {$mergeCount} tile(s)",
                    'direction' => $direction,
                    'reasoning' => 'Merging creates space and higher-value tiles'
                ];
            }
        }
        
        return $hints;
    }

    /**
     * Count potential merges in a direction for 2048
     */
    private static function count2048Merges(array $grid, array $delta): int
    {
        $merges = 0;
        $dr = $delta[0];
        $dc = $delta[1];
        
        for ($row = 0; $row < 4; $row++) {
            for ($col = 0; $col < 4; $col++) {
                $nextRow = $row + $dr;
                $nextCol = $col + $dc;
                
                if ($nextRow >= 0 && $nextRow < 4 && $nextCol >= 0 && $nextCol < 4) {
                    if ($grid[$row][$col] !== 0 && $grid[$row][$col] === $grid[$nextRow][$nextCol]) {
                        $merges++;
                    }
                }
            }
        }
        
        return $merges;
    }

    /**
     * Get hints for Tic Tac Toe
     */
    private static function getTicTacToeHints(array $state, array $options): array
    {
        $hints = [];
        
        if ($state['mode'] !== 'pass-play') {
            return $hints; // AI handles hints
        }
        
        $board = $state['board'];
        $currentPlayer = $state['currentPlayer'];
        
        // Check for winning moves
        $winMove = self::findTicTacToeWinningMove($board, $currentPlayer);
        if ($winMove !== null) {
            $hints[] = [
                'type' => 'critical',
                'priority' => 10,
                'action' => 'winning_move',
                'description' => 'You can win by playing in this position!',
                'position' => $winMove,
                'reasoning' => 'Always take the winning move when available'
            ];
        }
        
        // Check for blocking moves
        $opponent = $currentPlayer === 'X' ? 'O' : 'X';
        $blockMove = self::findTicTacToeWinningMove($board, $opponent);
        if ($blockMove !== null) {
            $hints[] = [
                'type' => 'defensive',
                'priority' => 9,
                'action' => 'blocking_move',
                'description' => 'Block your opponent from winning!',
                'position' => $blockMove,
                'reasoning' => 'Prevent opponent victory'
            ];
        }
        
        return $hints;
    }

    /**
     * Find winning move for Tic Tac Toe
     */
    private static function findTicTacToeWinningMove(array $board, string $player): ?int
    {
        for ($i = 0; $i < 9; $i++) {
            if ($board[$i] === null) {
                $testBoard = $board;
                $testBoard[$i] = $player;
                
                if (self::checkTicTacToeWin($testBoard, $player)) {
                    return $i;
                }
            }
        }
        
        return null;
    }

    /**
     * Check for Tic Tac Toe win
     */
    private static function checkTicTacToeWin(array $board, string $player): bool
    {
        $winLines = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8], // Rows
            [0, 3, 6], [1, 4, 7], [2, 5, 8], // Columns
            [0, 4, 8], [2, 4, 6]             // Diagonals
        ];
        
        foreach ($winLines as $line) {
            if ($board[$line[0]] === $player && 
                $board[$line[1]] === $player && 
                $board[$line[2]] === $player) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get generic hints for unknown games
     */
    private static function getGenericHints(GameInterface $game, array $state, array $options): array
    {
        $hints = [];
        
        // Basic hint about using auto-move if available
        $hints[] = [
            'type' => 'generic',
            'priority' => 1,
            'action' => 'explore',
            'description' => 'Look for patterns and optimal moves',
            'reasoning' => 'Every game has underlying strategies to discover'
        ];
        
        return $hints;
    }

    /**
     * Rank and filter hints based on options
     */
    private static function rankAndFilterHints(array $hints, array $options): array
    {
        $maxHints = $options['maxHints'] ?? 3;
        $minPriority = $options['minPriority'] ?? 1;
        
        // Filter by minimum priority
        $filtered = array_filter($hints, function($hint) use ($minPriority) {
            return ($hint['priority'] ?? 0) >= $minPriority;
        });
        
        // Sort by priority (descending)
        usort($filtered, function($a, $b) {
            return ($b['priority'] ?? 0) - ($a['priority'] ?? 0);
        });
        
        // Limit to max hints
        return array_slice($filtered, 0, $maxHints);
    }

    // Helper methods for Solitaire
    private static function getWasteCard(array $state): ?array
    {
        if ($state['wasteIndex'] >= 0 && isset($state['waste'][$state['wasteIndex']])) {
            return $state['waste'][$state['wasteIndex']];
        }
        return null;
    }

    private static function canPlaceOnFoundation(array $card, array $foundation): bool
    {
        if (empty($foundation)) {
            return $card['rank'] === 'A';
        }
        
        $topCard = end($foundation);
        return $card['suit'] === $topCard['suit'] && 
               self::getCardValue($card['rank']) === self::getCardValue($topCard['rank']) + 1;
    }

    private static function canPlaceOnTableau(array $card, array $targetCard): bool
    {
        $cardValue = self::getCardValue($card['rank']);
        $targetValue = self::getCardValue($targetCard['rank']);
        
        $cardColor = in_array($card['suit'], ['hearts', 'diamonds']) ? 'red' : 'black';
        $targetColor = in_array($targetCard['suit'], ['hearts', 'diamonds']) ? 'red' : 'black';
        
        return $cardValue === $targetValue - 1 && $cardColor !== $targetColor;
    }

    private static function getCardValue(string $rank): int
    {
        return match($rank) {
            'A' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7,
            '8' => 8, '9' => 9, '10' => 10, 'J' => 11, 'Q' => 12, 'K' => 13
        };
    }

    private static function evaluateSequenceValue(array $state, int $col, array $card): int
    {
        // Simple heuristic - prioritize moves that reveal face-down cards
        if (count($state['tableau'][$col]) > 1) {
            $secondCard = $state['tableau'][$col][count($state['tableau'][$col]) - 2];
            if (!$secondCard['faceUp']) {
                return 6; // Higher priority for revealing cards
            }
        }
        
        return 4; // Standard building priority
    }

    /**
     * Get hints for Nine Men's Morris
     */
    private static function getNineMensMorrisHints(array $state, array $options): array
    {
        $hints = [];
        $currentPlayer = $state['currentPlayer'];
        $phase = $state['phase'];
        
        if ($state['mustCapture']) {
            $hints[] = [
                'type' => 'critical',
                'priority' => 10,
                'action' => 'capture',
                'description' => 'You must capture an opponent piece!',
                'reasoning' => 'Mill formed - capture is mandatory'
            ];
            return $hints;
        }
        
        if ($phase === 'placement') {
            // Suggest strategic placement positions
            $hints[] = [
                'type' => 'strategic',
                'priority' => 7,
                'action' => 'place_strategic',
                'description' => 'Try to place pieces where you can form mills',
                'reasoning' => 'Mills allow you to capture opponent pieces'
            ];
            
            // Suggest blocking opponent mills
            $hints[] = [
                'type' => 'defensive',
                'priority' => 8,
                'action' => 'place_defensive',
                'description' => 'Watch for opponent mill opportunities',
                'reasoning' => 'Block potential opponent mills when possible'
            ];
        } else {
            // Movement/Flying phase hints
            $hints[] = [
                'type' => 'tactical',
                'priority' => 6,
                'action' => 'move_tactical',
                'description' => 'Look for moves that create mill opportunities',
                'reasoning' => 'Strategic movement can set up multiple threats'
            ];
            
            if ($phase === 'flying') {
                $hints[] = [
                    'type' => 'strategic',
                    'priority' => 7,
                    'action' => 'fly_strategic',
                    'description' => 'Use flying ability to create unexpected mills',
                    'reasoning' => 'Flying allows long-distance strategic plays'
                ];
            }
        }
        
        return $hints;
    }

    /**
     * Get hints for Peg Solitaire
     */
    private static function getPegSolitaireHints(array $state, array $options): array
    {
        $hints = [];
        $pegsRemaining = $state['pegsRemaining'];
        
        // Get best move
        $bestMove = self::getPegSolitaireBestMove($state);
        if ($bestMove) {
            $hints[] = [
                'type' => 'immediate',
                'priority' => 9,
                'action' => 'best_move',
                'description' => "Jump peg from position {$bestMove['from']} to {$bestMove['to']}",
                'reasoning' => 'This move keeps the most options open'
            ];
        }
        
        // Strategy hints based on game progress
        if ($pegsRemaining > 10) {
            $hints[] = [
                'type' => 'strategic',
                'priority' => 6,
                'action' => 'early_strategy',
                'description' => 'Work from the outside toward the center',
                'reasoning' => 'This creates more opportunities for later moves'
            ];
        } elseif ($pegsRemaining > 5) {
            $hints[] = [
                'type' => 'strategic',
                'priority' => 7,
                'action' => 'mid_strategy',
                'description' => 'Look for moves that don\'t isolate pegs',
                'reasoning' => 'Isolated pegs become impossible to remove'
            ];
        } else {
            $hints[] = [
                'type' => 'critical',
                'priority' => 8,
                'action' => 'end_strategy',
                'description' => 'Plan carefully - few moves remaining!',
                'reasoning' => 'Each move is crucial with few pegs left'
            ];
        }
        
        // Pattern recognition hint
        if ($pegsRemaining <= 8) {
            $hints[] = [
                'type' => 'tactical',
                'priority' => 5,
                'action' => 'pattern_hint',
                'description' => 'Look for L-shaped and triangular patterns',
                'reasoning' => 'These patterns often lead to successful solutions'
            ];
        }
        
        return $hints;
    }

    /**
     * Simple best move calculation for Peg Solitaire
     */
    private static function getPegSolitaireBestMove(array $state): ?array
    {
        // This would integrate with PegSolitaireEngine::getBestMove()
        // For now, return a simple heuristic
        $validMoves = self::getPegSolitaireValidMoves($state);
        
        if (empty($validMoves)) {
            return null;
        }
        
        // Prefer moves that keep more options open
        $bestMove = null;
        $bestScore = -1;
        
        foreach ($validMoves as $move) {
            // Simple scoring: prefer moves toward center
            $score = 15 - abs(7 - $move['to']); // Distance from center area
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMove = $move;
            }
        }
        
        return $bestMove;
    }

    /**
     * Get valid moves for Peg Solitaire (simplified version)
     */
    private static function getPegSolitaireValidMoves(array $state): array
    {
        // This is a simplified version - real implementation would use PegSolitaireEngine
        $validMoves = [];
        $board = $state['board'];
        
        // Basic jump patterns for triangular board
        $patterns = [
            [0, 1, 3], [0, 2, 5], [1, 3, 6], [1, 4, 8], [2, 4, 7], [2, 5, 9],
            [3, 4, 5], [6, 7, 8], [7, 8, 9], [3, 6, 10], [4, 7, 11], [5, 8, 12]
        ];
        
        foreach ($patterns as $pattern) {
            [$from, $over, $to] = $pattern;
            if ($from < 15 && $over < 15 && $to < 15 &&
                $board[$from] && $board[$over] && !$board[$to]) {
                $validMoves[] = ['from' => $from, 'over' => $over, 'to' => $to];
            }
        }
        
        return $validMoves;
    }

    /**
     * Get hints for Connect 4
     */
    private static function getConnect4Hints(array $state, array $options): array
    {
        $hints = [];
        $currentPlayer = $state['currentPlayer'];
        $opponent = $currentPlayer === 'red' ? 'yellow' : 'red';

        // Check for immediate winning moves
        $winningMove = self::findConnect4WinningMove($state, $currentPlayer);
        if ($winningMove !== null) {
            $hints[] = [
                'type' => 'critical',
                'priority' => 10,
                'action' => 'winning_move',
                'description' => "Drop in column " . ($winningMove + 1) . " to win!",
                'reasoning' => 'Always take the winning move when available'
            ];
            return $hints; // Return immediately for winning moves
        }

        // Check for blocking moves
        $blockingMove = self::findConnect4WinningMove($state, $opponent);
        if ($blockingMove !== null) {
            $hints[] = [
                'type' => 'defensive',
                'priority' => 9,
                'action' => 'blocking_move',
                'description' => "Block opponent by dropping in column " . ($blockingMove + 1),
                'reasoning' => 'Prevent opponent from winning'
            ];
        }

        // Strategic hints
        $centerColumns = [2, 3, 4]; // Columns 3, 4, 5 (1-indexed)
        $validMoves = self::getConnect4ValidMoves($state);
        
        $centerAvailable = array_intersect($centerColumns, $validMoves);
        if (!empty($centerAvailable)) {
            $bestCenter = $centerAvailable[0];
            $hints[] = [
                'type' => 'strategic',
                'priority' => 7,
                'action' => 'center_strategy',
                'description' => "Consider column " . ($bestCenter + 1) . " for center control",
                'reasoning' => 'Center columns provide more winning opportunities'
            ];
        }

        // Look for two-in-a-row setups
        $setupMove = self::findConnect4SetupMove($state, $currentPlayer);
        if ($setupMove !== null) {
            $hints[] = [
                'type' => 'tactical',
                'priority' => 6,
                'action' => 'setup_move',
                'description' => "Column " . ($setupMove + 1) . " creates multiple threats",
                'reasoning' => 'Setting up multiple winning possibilities'
            ];
        }

        // Avoid giving opponent advantages
        $dangerousMove = self::findConnect4DangerousMove($state, $currentPlayer);
        if ($dangerousMove !== null) {
            $hints[] = [
                'type' => 'defensive',
                'priority' => 5,
                'action' => 'avoid_danger',
                'description' => "Avoid column " . ($dangerousMove + 1) . " - helps opponent",
                'reasoning' => 'This move would give opponent a winning opportunity'
            ];
        }

        return $hints;
    }

    /**
     * Find winning move for Connect 4
     */
    private static function findConnect4WinningMove(array $state, string $player): ?int
    {
        for ($col = 0; $col < 7; $col++) {
            if (self::canDropInConnect4Column($state, $col)) {
                $row = self::getConnect4LowestRow($state, $col);
                if ($row !== -1) {
                    // Simulate the move
                    $testState = $state;
                    $testState['board'][$row][$col] = $player;
                    
                    // Check if this creates a win
                    if (self::checkConnect4Win($testState, $row, $col, $player)) {
                        return $col;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Find setup move that creates multiple threats
     */
    private static function findConnect4SetupMove(array $state, string $player): ?int
    {
        $bestCol = null;
        $maxThreats = 0;

        for ($col = 0; $col < 7; $col++) {
            if (self::canDropInConnect4Column($state, $col)) {
                $row = self::getConnect4LowestRow($state, $col);
                if ($row !== -1) {
                    // Count potential threats after this move
                    $testState = $state;
                    $testState['board'][$row][$col] = $player;
                    $threats = self::countConnect4Threats($testState, $player);
                    
                    if ($threats > $maxThreats) {
                        $maxThreats = $threats;
                        $bestCol = $col;
                    }
                }
            }
        }

        return $maxThreats > 2 ? $bestCol : null;
    }

    /**
     * Find dangerous moves that help opponent
     */
    private static function findConnect4DangerousMove(array $state, string $player): ?int
    {
        $opponent = $player === 'red' ? 'yellow' : 'red';

        for ($col = 0; $col < 7; $col++) {
            if (self::canDropInConnect4Column($state, $col)) {
                $row = self::getConnect4LowestRow($state, $col);
                if ($row !== -1 && $row > 0) {
                    // Check if dropping here gives opponent a winning opportunity above
                    $testState = $state;
                    $testState['board'][$row][$col] = $player;
                    
                    // Now check if opponent can win by playing above this piece
                    $testState['board'][$row - 1][$col] = $opponent;
                    if (self::checkConnect4Win($testState, $row - 1, $col, $opponent)) {
                        return $col;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Helper methods for Connect 4 analysis
     */
    private static function canDropInConnect4Column(array $state, int $col): bool
    {
        return $col >= 0 && $col < 7 && $state['board'][0][$col] === null;
    }

    private static function getConnect4LowestRow(array $state, int $col): int
    {
        for ($row = 5; $row >= 0; $row--) {
            if ($state['board'][$row][$col] === null) {
                return $row;
            }
        }
        return -1;
    }

    private static function getConnect4ValidMoves(array $state): array
    {
        $validMoves = [];
        for ($col = 0; $col < 7; $col++) {
            if (self::canDropInConnect4Column($state, $col)) {
                $validMoves[] = $col;
            }
        }
        return $validMoves;
    }

    private static function checkConnect4Win(array $state, int $row, int $col, string $player): bool
    {
        $directions = [[0, 1], [1, 0], [1, 1], [1, -1]];

        foreach ($directions as $direction) {
            $count = 1; // Count the current piece
            $deltaRow = $direction[0];
            $deltaCol = $direction[1];

            // Check positive direction
            $r = $row + $deltaRow;
            $c = $col + $deltaCol;
            while ($r >= 0 && $r < 6 && $c >= 0 && $c < 7 && $state['board'][$r][$c] === $player) {
                $count++;
                $r += $deltaRow;
                $c += $deltaCol;
            }

            // Check negative direction
            $r = $row - $deltaRow;
            $c = $col - $deltaCol;
            while ($r >= 0 && $r < 6 && $c >= 0 && $c < 7 && $state['board'][$r][$c] === $player) {
                $count++;
                $r -= $deltaRow;
                $c -= $deltaCol;
            }

            if ($count >= 4) {
                return true;
            }
        }

        return false;
    }

    private static function countConnect4Threats(array $state, string $player): int
    {
        $threats = 0;
        
        // This is a simplified threat counting - could be enhanced
        for ($col = 0; $col < 7; $col++) {
            if (self::canDropInConnect4Column($state, $col)) {
                $row = self::getConnect4LowestRow($state, $col);
                if ($row !== -1) {
                    $testState = $state;
                    $testState['board'][$row][$col] = $player;
                    if (self::checkConnect4Win($testState, $row, $col, $player)) {
                        $threats++;
                    }
                }
            }
        }
        
        return $threats;
    }
}
