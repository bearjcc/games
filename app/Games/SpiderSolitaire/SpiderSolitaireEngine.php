<?php

namespace App\Games\SpiderSolitaire;

/**
 * Spider Solitaire Engine - Classic single-player card game logic
 */
class SpiderSolitaireEngine
{
    public const SUITS = ['hearts', 'diamonds', 'clubs', 'spades'];
    public const RANKS = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
    public const TABLEAU_COLUMNS = 10;
    public const CARDS_PER_DEAL = 10;
    public const CARDS_PER_SUIT = 13;

    public const SCORING = [
        'complete_suit' => 100,
        'move_penalty' => -1,
        'time_bonus_multiplier' => 10,
        'perfect_game_bonus' => 500
    ];

    public static function newGame(): array
    {
        $deck = self::createDeck();
        shuffle($deck);
        
        // Deal tableau: first 4 columns get 6 cards, last 6 columns get 5 cards
        $tableau = [];
        $cardIndex = 0;
        
        for ($col = 0; $col < self::TABLEAU_COLUMNS; $col++) {
            $cardsInColumn = $col < 4 ? 6 : 5;
            $column = [];
            
            for ($i = 0; $i < $cardsInColumn; $i++) {
                $card = $deck[$cardIndex++];
                $card['faceUp'] = $i === $cardsInColumn - 1; // Only top card face up
                $column[] = $card;
            }
            
            $tableau[] = $column;
        }
        
        // Remaining cards go to stock
        $stock = array_slice($deck, $cardIndex);
        
        return [
            'tableau' => $tableau,
            'stock' => $stock,
            'completedSuits' => [],
            'gameOver' => false,
            'gameWon' => false,
            'moves' => 0,
            'score' => 0,
            'startTime' => time(),
            'moveHistory' => [],
            'hintUsed' => false,
            'gamePhase' => 'playing' // playing, won, lost
        ];
    }

    public static function createDeck(): array
    {
        $deck = [];
        // Create 2 decks (104 cards total)
        for ($deckNum = 0; $deckNum < 2; $deckNum++) {
            foreach (self::SUITS as $suit) {
                foreach (self::RANKS as $rank) {
                    $deck[] = [
                        'suit' => $suit,
                        'rank' => $rank,
                        'value' => self::getCardValue($rank),
                        'faceUp' => false,
                        'image' => "card{$suit}{$rank}.png"
                    ];
                }
            }
        }
        return $deck;
    }

    public static function getCardValue(string $rank): int
    {
        if ($rank === 'A') return 1;
        if ($rank === 'K') return 13;
        if ($rank === 'Q') return 12;
        if ($rank === 'J') return 11;
        return intval($rank);
    }

    public static function validateMove(array $state, array $move): bool
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'move_cards':
                $fromColumn = $move['fromColumn'] ?? -1;
                $toColumn = $move['toColumn'] ?? -1;
                $cardCount = $move['cardCount'] ?? 1;
                
                return $fromColumn >= 0 && $fromColumn < self::TABLEAU_COLUMNS &&
                       $toColumn >= 0 && $toColumn < self::TABLEAU_COLUMNS &&
                       $fromColumn !== $toColumn &&
                       $cardCount > 0 &&
                       self::canMoveCards($state, $fromColumn, $toColumn, $cardCount);
            
            case 'deal_cards':
                return self::canDealCards($state);
            
            case 'undo':
                return self::canUndo($state);
            
            case 'new_game':
                return true;
            
            default:
                return false;
        }
    }

    public static function applyMove(array $state, array $move): array
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'move_cards':
                return self::moveCards($state, $move['fromColumn'], $move['toColumn'], $move['cardCount']);
            
            case 'deal_cards':
                return self::dealCards($state);
            
            case 'undo':
                return self::undo($state);
            
            case 'new_game':
                return self::newGame();
            
            default:
                return $state;
        }
    }

    public static function moveCards(array $state, int $fromColumn, int $toColumn, int $cardCount): array
    {
        // Save state for undo
        $state['moveHistory'][] = self::createMoveSnapshot($state);
        
        $tableau = $state['tableau'];
        $fromCards = array_slice($tableau[$fromColumn], -$cardCount);
        
        // Validate the move
        if (!self::isValidSequence($fromCards) || !self::canPlaceCards($tableau[$toColumn], $fromCards)) {
            return $state;
        }
        
        // Remove cards from source column
        $tableau[$fromColumn] = array_slice($tableau[$fromColumn], 0, -$cardCount);
        
        // Add cards to destination column
        $tableau[$toColumn] = array_merge($tableau[$toColumn], $fromCards);
        
        // Flip face-down card if column is now empty or has face-down cards
        if (!empty($tableau[$fromColumn])) {
            $lastCard = end($tableau[$fromColumn]);
            if (!$lastCard['faceUp']) {
                $tableau[$fromColumn][count($tableau[$fromColumn]) - 1]['faceUp'] = true;
            }
        }
        
        $state['tableau'] = $tableau;
        $state['moves']++;
        
        // Check for completed suits
        $state = self::checkCompletedSuits($state);
        
        // Check for game over
        $state = self::checkGameOver($state);
        
        return $state;
    }

    public static function dealCards(array $state): array
    {
        if (!self::canDealCards($state)) {
            return $state;
        }
        
        // Save state for undo
        $state['moveHistory'][] = self::createMoveSnapshot($state);
        
        $stock = $state['stock'];
        $tableau = $state['tableau'];
        
        // Deal one card to each tableau column
        for ($col = 0; $col < self::TABLEAU_COLUMNS; $col++) {
            if (!empty($stock)) {
                $card = array_pop($stock);
                $card['faceUp'] = true;
                $tableau[$col][] = $card;
            }
        }
        
        $state['stock'] = $stock;
        $state['tableau'] = $tableau;
        $state['moves']++;
        
        // Check for game over
        $state = self::checkGameOver($state);
        
        return $state;
    }

    public static function undo(array $state): array
    {
        if (!self::canUndo($state)) {
            return $state;
        }
        
        $previousState = array_pop($state['moveHistory']);
        $state = array_merge($state, $previousState);
        
        return $state;
    }

    public static function canMoveCards(array $state, int $fromColumn, int $toColumn, int $cardCount): bool
    {
        $tableau = $state['tableau'];
        
        if ($fromColumn < 0 || $fromColumn >= self::TABLEAU_COLUMNS ||
            $toColumn < 0 || $toColumn >= self::TABLEAU_COLUMNS ||
            $fromColumn === $toColumn ||
            $cardCount <= 0) {
            return false;
        }
        
        $fromColumnCards = $tableau[$fromColumn];
        if (count($fromColumnCards) < $cardCount) {
            return false;
        }
        
        $cardsToMove = array_slice($fromColumnCards, -$cardCount);
        
        // Check if all cards to move are face up
        foreach ($cardsToMove as $card) {
            if (!$card['faceUp']) {
                return false;
            }
        }
        
        // Check if cards form a valid sequence
        if (!self::isValidSequence($cardsToMove)) {
            return false;
        }
        
        // Check if cards can be placed on destination column
        return self::canPlaceCards($tableau[$toColumn], $cardsToMove);
    }

    public static function isValidSequence(array $cards): bool
    {
        if (count($cards) <= 1) {
            return true;
        }
        
        $suit = $cards[0]['suit'];
        
        // Check if all cards are the same suit
        foreach ($cards as $card) {
            if ($card['suit'] !== $suit) {
                return false;
            }
        }
        
        // Check if cards form descending sequence
        for ($i = 0; $i < count($cards) - 1; $i++) {
            if ($cards[$i]['value'] !== $cards[$i + 1]['value'] + 1) {
                return false;
            }
        }
        
        return true;
    }

    public static function canPlaceCards(array $destinationColumn, array $cardsToPlace): bool
    {
        if (empty($destinationColumn)) {
            // Can only place King on empty column
            return $cardsToPlace[0]['rank'] === 'K';
        }
        
        $topCard = end($destinationColumn);
        $bottomCard = $cardsToPlace[0];
        
        // Can place if bottom card is one rank lower than top card
        return $bottomCard['value'] === $topCard['value'] - 1;
    }

    public static function canDealCards(array $state): bool
    {
        return count($state['stock']) >= self::TABLEAU_COLUMNS;
    }

    public static function checkCompletedSuits(array $state): array
    {
        $tableau = $state['tableau'];
        $completedSuits = $state['completedSuits'];
        
        foreach ($tableau as $columnIndex => $column) {
            if (count($column) >= self::CARDS_PER_SUIT) {
                $lastCards = array_slice($column, -self::CARDS_PER_SUIT);
                
                if (self::isCompleteSuit($lastCards)) {
                    // Remove the completed suit
                    $state['tableau'][$columnIndex] = array_slice($column, 0, -self::CARDS_PER_SUIT);
                    $completedSuits[] = $lastCards[0]['suit'];
                    $state['score'] += self::SCORING['complete_suit'];
                }
            }
        }
        
        $state['completedSuits'] = $completedSuits;
        return $state;
    }

    public static function isCompleteSuit(array $cards): bool
    {
        if (count($cards) !== self::CARDS_PER_SUIT) {
            return false;
        }
        
        $suit = $cards[0]['suit'];
        $values = array_map(fn($card) => $card['value'], $cards);
        sort($values);
        
        // Check if we have all 13 cards of the same suit
        return $values === range(1, 13) && 
               count(array_unique(array_map(fn($card) => $card['suit'], $cards))) === 1;
    }

    public static function checkGameOver(array $state): array
    {
        // Check if won (all 8 suits completed)
        if (count($state['completedSuits']) >= 8) {
            $state['gameOver'] = true;
            $state['gameWon'] = true;
            $state['gamePhase'] = 'won';
            $state['score'] += self::SCORING['perfect_game_bonus'];
            return $state;
        }
        
        // Check if lost (no moves possible and can't deal)
        if (!self::canDealCards($state) && !self::hasPossibleMoves($state)) {
            $state['gameOver'] = true;
            $state['gameWon'] = false;
            $state['gamePhase'] = 'lost';
        }
        
        return $state;
    }

    public static function hasPossibleMoves(array $state): bool
    {
        $possibleMoves = self::getPossibleMoves($state);
        return !empty($possibleMoves);
    }

    public static function getPossibleMoves(array $state): array
    {
        $moves = [];
        $tableau = $state['tableau'];
        
        for ($fromCol = 0; $fromCol < self::TABLEAU_COLUMNS; $fromCol++) {
            $fromColumn = $tableau[$fromCol];
            if (empty($fromColumn)) continue;
            
            // Find sequences that can be moved
            $sequences = self::findMovableSequences($fromColumn);
            
            foreach ($sequences as $sequence) {
                $cardCount = count($sequence);
                $bottomCard = $sequence[0];
                
                // Check all possible destination columns
                for ($toCol = 0; $toCol < self::TABLEAU_COLUMNS; $toCol++) {
                    if ($fromCol === $toCol) continue;
                    
                    if (self::canPlaceCards($tableau[$toCol], $sequence)) {
                        $moves[] = [
                            'fromColumn' => $fromCol,
                            'toColumn' => $toCol,
                            'cardCount' => $cardCount,
                            'cards' => $sequence,
                            'description' => self::describeMove($fromCol, $toCol, $cardCount, $bottomCard)
                        ];
                    }
                }
            }
        }
        
        return $moves;
    }

    public static function findMovableSequences(array $column): array
    {
        $sequences = [];
        
        for ($i = 0; $i < count($column); $i++) {
            if (!$column[$i]['faceUp']) break;
            
            $sequence = [$column[$i]];
            
            // Extend sequence as far as possible
            for ($j = $i + 1; $j < count($column); $j++) {
                if (!$column[$j]['faceUp']) break;
                
                if ($column[$j]['suit'] === $sequence[0]['suit'] &&
                    $column[$j]['value'] === $sequence[count($sequence) - 1]['value'] - 1) {
                    $sequence[] = $column[$j];
                } else {
                    break;
                }
            }
            
            if (count($sequence) > 0) {
                $sequences[] = $sequence;
            }
        }
        
        return $sequences;
    }

    public static function describeMove(int $fromCol, int $toCol, int $cardCount, array $bottomCard): string
    {
        $fromColName = chr(65 + $fromCol); // A, B, C, etc.
        $toColName = chr(65 + $toCol);
        
        if ($cardCount === 1) {
            return "Move {$bottomCard['rank']} of {$bottomCard['suit']} from column {$fromColName} to column {$toColName}";
        } else {
            return "Move {$cardCount} cards starting with {$bottomCard['rank']} of {$bottomCard['suit']} from column {$fromColName} to column {$toColName}";
        }
    }

    public static function getHint(array $state): array
    {
        $possibleMoves = self::getPossibleMoves($state);
        
        if (empty($possibleMoves)) {
            return [
                'type' => 'no_moves',
                'message' => 'No moves available. Try dealing cards or undo your last move.',
                'action' => 'deal_cards'
            ];
        }
        
        // Prioritize moves that expose face-down cards
        $exposingMoves = array_filter($possibleMoves, function($move) use ($state) {
            $fromColumn = $state['tableau'][$move['fromColumn']];
            $remainingCards = count($fromColumn) - $move['cardCount'];
            return $remainingCards > 0 && !$fromColumn[$remainingCards - 1]['faceUp'];
        });
        
        if (!empty($exposingMoves)) {
            $move = $exposingMoves[0];
            return [
                'type' => 'expose_card',
                'message' => $move['description'] . ' (This will expose a face-down card)',
                'move' => $move
            ];
        }
        
        // Return the first available move
        $move = $possibleMoves[0];
        return [
            'type' => 'move',
            'message' => $move['description'],
            'move' => $move
        ];
    }

    public static function createMoveSnapshot(array $state): array
    {
        return [
            'tableau' => $state['tableau'],
            'stock' => $state['stock'],
            'completedSuits' => $state['completedSuits'],
            'moves' => $state['moves'],
            'score' => $state['score']
        ];
    }

    public static function canUndo(array $state): bool
    {
        return !empty($state['moveHistory']);
    }

    public static function isGameOver(array $state): bool
    {
        return $state['gameOver'];
    }

    public static function calculateScore(array $state): int
    {
        $score = $state['score'];
        $score += $state['moves'] * self::SCORING['move_penalty'];
        
        // Time bonus
        $timeElapsed = time() - $state['startTime'];
        $timeBonus = max(0, 300 - $timeElapsed) * self::SCORING['time_bonus_multiplier'];
        $score += $timeBonus;
        
        return max(0, $score);
    }

    public static function getGameState(array $state): array
    {
        return [
            'tableau' => $state['tableau'],
            'stock' => $state['stock'],
            'completedSuits' => $state['completedSuits'],
            'gameOver' => $state['gameOver'],
            'gameWon' => $state['gameWon'],
            'gamePhase' => $state['gamePhase'],
            'moves' => $state['moves'],
            'score' => $state['score'],
            'canDeal' => self::canDealCards($state),
            'canUndo' => self::canUndo($state),
            'possibleMoves' => count(self::getPossibleMoves($state))
        ];
    }

    public static function getTableau(array $state): array
    {
        return $state['tableau'];
    }

    public static function getStock(array $state): array
    {
        return $state['stock'];
    }

    public static function getCompletedSuits(array $state): array
    {
        return $state['completedSuits'];
    }
}
