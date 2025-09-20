<?php

namespace App\Games\Chess;

use App\Games\Contracts\GameInterface;

class ChessGame implements GameInterface
{
    public function id(): string
    {
        return 'chess';
    }

    public function name(): string
    {
        return 'Chess';
    }

    public function slug(): string
    {
        return 'chess';
    }

    public function description(): string
    {
        return 'The classic strategy game - checkmate your opponent\'s king with tactical precision and strategic thinking.';
    }

    public function rules(): array
    {
        return [
            'Each player starts with 16 pieces: 8 pawns, 2 rooks, 2 knights, 2 bishops, 1 queen, and 1 king',
            'Players alternate turns, with white moving first',
            'Each piece has unique movement patterns:',
            '  • Pawns move forward one square (two on first move), capture diagonally',
            '  • Rooks move horizontally and vertically any distance',
            '  • Knights move in an L-shape (2+1 squares)',
            '  • Bishops move diagonally any distance',
            '  • Queens combine rook and bishop movements',
            '  • Kings move one square in any direction',
            'Special moves include castling, en passant capture, and pawn promotion',
            'Win by checkmating the opponent\'s king (attacked with no legal escape)',
            'Games can end in stalemate (no legal moves but not in check) or draw by 50-move rule'
        ];
    }

    public function initialState(): array
    {
        return ChessEngine::initialState();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return ChessEngine::isGameOver($state);
    }

    public function applyMove(array $state, array $move): array
    {
        // Ensure move has required fields
        if (!isset($move['from'], $move['to'])) {
            return $state;
        }

        // Add piece information if not present
        if (!isset($move['piece'])) {
            $board = $state['board'];
            [$fromRow, $fromCol] = $move['from'];
            $move['piece'] = $board[$fromRow][$fromCol];
        }

        // Determine move type if not specified
        if (!isset($move['type'])) {
            $board = $state['board'];
            [$fromRow, $fromCol] = $move['from'];
            [$toRow, $toCol] = $move['to'];
            $piece = $move['piece'];
            $targetPiece = $board[$toRow][$toCol];

            // Check for special moves
            if (ChessEngine::getPieceType($piece) === 'king' && abs($fromCol - $toCol) === 2) {
                $move['type'] = $toCol > $fromCol ? 'castle_kingside' : 'castle_queenside';
            } elseif (ChessEngine::getPieceType($piece) === 'pawn' && !$targetPiece && $fromCol !== $toCol) {
                $move['type'] = 'en_passant';
                $move['captured'] = $board[$fromRow][$toCol];
            } elseif ($targetPiece) {
                $move['type'] = 'capture';
                $move['captured'] = $targetPiece;
            } else {
                $move['type'] = 'move';
            }
        }

        return ChessEngine::applyMove($state, $move);
    }

    public function validateMove(array $state, array $move): bool
    {
        return ChessEngine::validateMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return ChessEngine::getScore($state);
    }

    /**
     * Get all valid moves for the current player
     */
    public function getValidMoves(array $state): array
    {
        return ChessEngine::getValidMoves($state);
    }

    /**
     * Get game statistics
     */
    public function getStats(array $state): array
    {
        return ChessEngine::getStats($state);
    }

    /**
     * Check if player is in check
     */
    public function isInCheck(array $state, string $color): bool
    {
        return ChessEngine::isInCheck($state, $color);
    }

    /**
     * Get piece moves for a specific square
     */
    public function getPieceMoves(array $state, int $row, int $col): array
    {
        return ChessEngine::getPieceMoves($state, $row, $col);
    }

    /**
     * Get piece symbol for display
     */
    public function getPieceSymbol(string $piece): string
    {
        return ChessEngine::getPieceSymbol($piece);
    }

    /**
     * Convert algebraic notation to array coordinates
     */
    public function algebraicToCoords(string $notation): array
    {
        $col = ord(strtolower($notation[0])) - ord('a');
        $row = 8 - (int)$notation[1];
        return [$row, $col];
    }

    /**
     * Convert array coordinates to algebraic notation
     */
    public function coordsToAlgebraic(int $row, int $col): string
    {
        $file = chr(ord('a') + $col);
        $rank = 8 - $row;
        return $file . $rank;
    }

    /**
     * Get AI move using basic minimax algorithm
     */
    public function getAiMove(array $state, string $difficulty = 'medium'): ?array
    {
        $validMoves = $this->getValidMoves($state);
        
        if (empty($validMoves)) {
            return null;
        }

        return match ($difficulty) {
            'easy' => $this->getRandomMove($validMoves),
            'medium' => $this->getBestMove($state, $validMoves, 2),
            'hard' => $this->getBestMove($state, $validMoves, 3),
            'impossible' => $this->getBestMove($state, $validMoves, 4),
            default => $this->getBestMove($state, $validMoves, 2),
        };
    }

    /**
     * Get a random valid move (easy difficulty)
     */
    private function getRandomMove(array $validMoves): array
    {
        return $validMoves[array_rand($validMoves)];
    }

    /**
     * Get best move using minimax algorithm
     */
    private function getBestMove(array $state, array $validMoves, int $depth): array
    {
        $bestMove = null;
        $bestScore = $state['currentPlayer'] === ChessEngine::WHITE ? -999999 : 999999;

        foreach ($validMoves as $move) {
            $newState = $this->applyMove($state, $move);
            $score = $this->minimax($newState, $depth - 1, -999999, 999999, $state['currentPlayer'] === ChessEngine::BLACK);

            if ($state['currentPlayer'] === ChessEngine::WHITE && $score > $bestScore) {
                $bestScore = $score;
                $bestMove = $move;
            } elseif ($state['currentPlayer'] === ChessEngine::BLACK && $score < $bestScore) {
                $bestScore = $score;
                $bestMove = $move;
            }
        }

        return $bestMove ?? $validMoves[0];
    }

    /**
     * Minimax algorithm with alpha-beta pruning
     */
    private function minimax(array $state, int $depth, int $alpha, int $beta, bool $maximizingPlayer): int
    {
        if ($depth === 0 || $this->isOver($state)) {
            return $this->evaluatePosition($state);
        }

        $validMoves = $this->getValidMoves($state);

        if ($maximizingPlayer) {
            $maxEval = -999999;
            foreach ($validMoves as $move) {
                $newState = $this->applyMove($state, $move);
                $eval = $this->minimax($newState, $depth - 1, $alpha, $beta, false);
                $maxEval = max($maxEval, $eval);
                $alpha = max($alpha, $eval);
                if ($beta <= $alpha) {
                    break; // Alpha-beta pruning
                }
            }
            return $maxEval;
        } else {
            $minEval = 999999;
            foreach ($validMoves as $move) {
                $newState = $this->applyMove($state, $move);
                $eval = $this->minimax($newState, $depth - 1, $alpha, $beta, true);
                $minEval = min($minEval, $eval);
                $beta = min($beta, $eval);
                if ($beta <= $alpha) {
                    break; // Alpha-beta pruning
                }
            }
            return $minEval;
        }
    }

    /**
     * Evaluate the current position
     */
    private function evaluatePosition(array $state): int
    {
        // If game is over, return extreme values
        if ($this->isOver($state)) {
            if ($state['checkmate']) {
                return $state['winner'] === ChessEngine::WHITE ? 999999 : -999999;
            }
            return 0; // Draw
        }

        $score = 0;
        $board = $state['board'];

        // Material evaluation
        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                $piece = $board[$row][$col];
                if ($piece) {
                    $value = ChessEngine::PIECE_VALUES[$piece];
                    $positionalBonus = $this->getPositionalValue($piece, $row, $col);
                    
                    if (ChessEngine::getPieceColor($piece) === ChessEngine::WHITE) {
                        $score += $value + $positionalBonus;
                    } else {
                        $score -= $value + $positionalBonus;
                    }
                }
            }
        }

        // Mobility evaluation (number of legal moves)
        $whiteMoves = 0;
        $blackMoves = 0;
        
        $tempState = $state;
        $tempState['currentPlayer'] = ChessEngine::WHITE;
        $whiteMoves = count($this->getValidMoves($tempState));
        
        $tempState['currentPlayer'] = ChessEngine::BLACK;
        $blackMoves = count($this->getValidMoves($tempState));
        
        $score += ($whiteMoves - $blackMoves) * 0.1;

        // King safety evaluation
        if ($this->isInCheck($state, ChessEngine::WHITE)) {
            $score -= 50;
        }
        if ($this->isInCheck($state, ChessEngine::BLACK)) {
            $score += 50;
        }

        return $score;
    }

    /**
     * Get positional value for a piece based on its location
     */
    private function getPositionalValue(string $piece, int $row, int $col): float
    {
        $pieceType = ChessEngine::getPieceType($piece);
        $color = ChessEngine::getPieceColor($piece);
        
        // Adjust row for black pieces (flip the board)
        $evalRow = $color === ChessEngine::WHITE ? $row : 7 - $row;
        
        return match ($pieceType) {
            'pawn' => $this->getPawnPositionalValue($evalRow, $col),
            'knight' => $this->getKnightPositionalValue($evalRow, $col),
            'bishop' => $this->getBishopPositionalValue($evalRow, $col),
            'rook' => $this->getRookPositionalValue($evalRow, $col),
            'queen' => $this->getQueenPositionalValue($evalRow, $col),
            'king' => $this->getKingPositionalValue($evalRow, $col),
            default => 0,
        };
    }

    private function getPawnPositionalValue(int $row, int $col): float
    {
        // Encourage pawn advancement
        $advancement = (7 - $row) * 0.1;
        
        // Encourage central pawns
        $centrality = (3.5 - abs($col - 3.5)) * 0.05;
        
        return $advancement + $centrality;
    }

    private function getKnightPositionalValue(int $row, int $col): float
    {
        // Knights are better in the center
        $centerDistance = abs($row - 3.5) + abs($col - 3.5);
        return (7 - $centerDistance) * 0.1;
    }

    private function getBishopPositionalValue(int $row, int $col): float
    {
        // Bishops prefer long diagonals
        return ($row === $col || $row + $col === 7) ? 0.2 : 0;
    }

    private function getRookPositionalValue(int $row, int $col): float
    {
        // Rooks prefer open files and ranks
        return 0; // Could be enhanced with file/rank openness detection
    }

    private function getQueenPositionalValue(int $row, int $col): float
    {
        // Queen prefers central positions but not too early
        $centerDistance = abs($row - 3.5) + abs($col - 3.5);
        return (4 - $centerDistance) * 0.05;
    }

    private function getKingPositionalValue(int $row, int $col): float
    {
        // King safety in early/mid game, activity in endgame
        // For simplicity, encourage king to stay on back rank early
        return $row === 7 ? 0.5 : -0.2;
    }
}
