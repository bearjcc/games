<?php

namespace App\Games\Solitaire;

class SolitaireEngine
{
    /**
     * Create a standard 52-card deck
     */
    public static function createDeck(): array
    {
        $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
        $ranks = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
        $deck = [];

        foreach ($suits as $suit) {
            foreach ($ranks as $rank) {
                $deck[] = [
                    'suit' => $suit,
                    'rank' => $rank,
                    'value' => self::getCardValue($rank),
                    'color' => in_array($suit, ['hearts', 'diamonds']) ? 'red' : 'black',
                    'faceUp' => false
                ];
            }
        }

        return $deck;
    }

    /**
     * Get numeric value for card rank
     */
    public static function getCardValue(string $rank): int
    {
        return match($rank) {
            'A' => 1,
            'J' => 11,
            'Q' => 12,
            'K' => 13,
            default => (int) $rank
        };
    }

    /**
     * Get card image filename for individual card images
     */
    public static function getCardSprite(array $card): string
    {
        $suitName = ucfirst($card['suit']);
        return "card{$suitName}{$card['rank']}.png";
    }

    /**
     * Initialize new Klondike Solitaire game
     */
    public static function newGame($options = []): array
    {
        $deck = self::createDeck();
        shuffle($deck);

        // Setup tableau (7 columns: 1, 2, 3, 4, 5, 6, 7 cards)
        $tableau = [];
        $deckIndex = 0;

        for ($col = 0; $col < 7; $col++) {
            $tableau[$col] = [];
            for ($row = 0; $row <= $col; $row++) {
                $card = $deck[$deckIndex++];
                // Only top card is face-up
                $card['faceUp'] = ($row === $col);
                $tableau[$col][] = $card;
            }
        }

        // Remaining cards go to stock
        $stock = array_slice($deck, $deckIndex);

        return [
            'tableau' => $tableau,
            'foundations' => [
                'hearts' => [],
                'diamonds' => [],
                'clubs' => [],
                'spades' => []
            ],
            'stock' => $stock,
            'waste' => [],
            'score' => $options['scoringMode'] === 'vegas' ? -52 : 0, // Vegas starts at -$52
            'moves' => 0,
            'gameWon' => false,
            'gameTime' => 0,
            'drawCount' => $options['drawCount'] ?? 3, // Draw 1 or 3 cards from stock
            'wasteIndex' => -1, // Index of currently visible waste card
            'scoringMode' => $options['scoringMode'] ?? 'standard', // standard, vegas, timed
            'difficulty' => $options['difficulty'] ?? 'normal', // easy, normal, hard
            'timerPressure' => $options['timerPressure'] ?? false,
            'perfectGame' => true, // Track if no mistakes made
            'streakBonus' => 0,
            'cardsToFoundation' => 0 // Track foundation moves for bonuses
        ];
    }

    /**
     * Draw cards from stock to waste
     */
    public static function drawFromStock(array $state): array
    {
        if (empty($state['stock'])) {
            // Reset stock from waste
            if (!empty($state['waste'])) {
                $state['stock'] = array_reverse($state['waste']);
                $state['waste'] = [];
                $state['wasteIndex'] = -1;
                $state['moves']++;
            }
            return $state;
        }

        // Draw up to 3 cards
        $drawCount = min($state['drawCount'], count($state['stock']));
        for ($i = 0; $i < $drawCount; $i++) {
            $card = array_pop($state['stock']);
            $card['faceUp'] = true;
            $state['waste'][] = $card;
        }

        $state['wasteIndex'] = count($state['waste']) - 1;
        $state['moves']++;

        return $state;
    }

    /**
     * Get currently visible waste card
     */
    public static function getWasteCard(array $state): ?array
    {
        if ($state['wasteIndex'] >= 0 && $state['wasteIndex'] < count($state['waste'])) {
            return $state['waste'][$state['wasteIndex']];
        }
        return null;
    }

    /**
     * Validate if a card can be placed on another card in tableau
     */
    public static function canPlaceOnTableau(array $card, ?array $target): bool
    {
        if ($target === null) {
            // Empty tableau slot - only Kings allowed
            return $card['rank'] === 'K';
        }

        // Must be descending rank and alternating colors
        return $card['value'] === $target['value'] - 1 && 
               $card['color'] !== $target['color'];
    }

    /**
     * Validate if a card can be placed on foundation
     */
    public static function canPlaceOnFoundation(array $card, array $foundation): bool
    {
        if (empty($foundation)) {
            // Empty foundation - only Aces allowed
            return $card['rank'] === 'A';
        }

        $topCard = end($foundation);
        
        // Must be same suit and ascending rank
        return $card['suit'] === $topCard['suit'] && 
               $card['value'] === $topCard['value'] + 1;
    }

    /**
     * Move card from waste to tableau
     */
    public static function moveWasteToTableau(array $state, int $tableauCol): array
    {
        $wasteCard = self::getWasteCard($state);
        if (!$wasteCard) {
            return $state;
        }

        $targetCard = !empty($state['tableau'][$tableauCol]) ? 
                     end($state['tableau'][$tableauCol]) : null;

        if (!self::canPlaceOnTableau($wasteCard, $targetCard)) {
            return $state;
        }

        // Remove from waste
        array_splice($state['waste'], $state['wasteIndex'], 1);
        $state['wasteIndex']--;

        // Add to tableau
        $state['tableau'][$tableauCol][] = $wasteCard;
        $state['moves']++;
        $state['score'] += 5; // Points for waste to tableau

        return $state;
    }

    /**
     * Move card from waste to foundation
     */
    public static function moveWasteToFoundation(array $state, string $suit): array
    {
        $wasteCard = self::getWasteCard($state);
        if (!$wasteCard || $wasteCard['suit'] !== $suit) {
            return $state;
        }

        if (!self::canPlaceOnFoundation($wasteCard, $state['foundations'][$suit])) {
            return $state;
        }

        // Remove from waste
        array_splice($state['waste'], $state['wasteIndex'], 1);
        $state['wasteIndex']--;

        // Add to foundation
        $state['foundations'][$suit][] = $wasteCard;
        $state['moves']++;
        $state['cardsToFoundation']++;
        
        // Scoring based on mode
        $scoringMode = $state['scoringMode'] ?? 'standard';
        if ($scoringMode === 'vegas') {
            $state['score'] += 5; // $5 per card in Vegas mode
        } else {
            $state['score'] += 10; // Standard points
        }

        return $state;
    }

    /**
     * Move cards between tableau columns
     */
    public static function moveTableauToTableau(array $state, int $fromCol, int $cardIndex, int $toCol): array
    {
        if (!isset($state['tableau'][$fromCol][$cardIndex]) || $fromCol === $toCol) {
            return $state;
        }

        $movingCards = array_slice($state['tableau'][$fromCol], $cardIndex);
        $firstMovingCard = $movingCards[0];

        // Validate sequence is properly built (alternating colors, descending)
        for ($i = 1; $i < count($movingCards); $i++) {
            if (!self::canPlaceOnTableau($movingCards[$i], $movingCards[$i-1])) {
                return $state; // Invalid sequence
            }
        }

        $targetCard = !empty($state['tableau'][$toCol]) ? 
                     end($state['tableau'][$toCol]) : null;

        if (!self::canPlaceOnTableau($firstMovingCard, $targetCard)) {
            return $state;
        }

        // Remove cards from source
        $state['tableau'][$fromCol] = array_slice($state['tableau'][$fromCol], 0, $cardIndex);

        // Flip new top card if needed
        if (!empty($state['tableau'][$fromCol])) {
            $topCard = &$state['tableau'][$fromCol][count($state['tableau'][$fromCol]) - 1];
            if (!$topCard['faceUp']) {
                $topCard['faceUp'] = true;
                $state['score'] += 5; // Points for revealing card
            }
        }

        // Add cards to destination
        $state['tableau'][$toCol] = array_merge($state['tableau'][$toCol], $movingCards);
        $state['moves']++;

        return $state;
    }

    /**
     * Move card from tableau to foundation
     */
    public static function moveTableauToFoundation(array $state, int $fromCol, string $suit): array
    {
        if (empty($state['tableau'][$fromCol])) {
            return $state;
        }

        $card = end($state['tableau'][$fromCol]);
        if (!$card['faceUp'] || $card['suit'] !== $suit) {
            return $state;
        }

        if (!self::canPlaceOnFoundation($card, $state['foundations'][$suit])) {
            return $state;
        }

        // Remove from tableau
        array_pop($state['tableau'][$fromCol]);

        // Flip new top card if needed
        if (!empty($state['tableau'][$fromCol])) {
            $topCard = &$state['tableau'][$fromCol][count($state['tableau'][$fromCol]) - 1];
            if (!$topCard['faceUp']) {
                $topCard['faceUp'] = true;
                $state['score'] += 5; // Points for revealing card
            }
        }

        // Add to foundation
        $state['foundations'][$suit][] = $card;
        $state['moves']++;
        $state['cardsToFoundation']++;
        
        // Scoring based on mode
        $scoringMode = $state['scoringMode'] ?? 'standard';
        if ($scoringMode === 'vegas') {
            $state['score'] += 5; // $5 per card in Vegas mode
        } else {
            $state['score'] += 10; // Standard points
        }

        return $state;
    }

    /**
     * Check if game is won
     */
    public static function isGameWon(array $state): bool
    {
        foreach ($state['foundations'] as $foundation) {
            if (count($foundation) !== 13) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get possible auto-moves to foundation
     */
    public static function getAutoMoves(array $state): array
    {
        $autoMoves = [];

        // Check waste card
        $wasteCard = self::getWasteCard($state);
        if ($wasteCard) {
            $suit = $wasteCard['suit'];
            if (self::canPlaceOnFoundation($wasteCard, $state['foundations'][$suit])) {
                $autoMoves[] = ['type' => 'waste_to_foundation', 'suit' => $suit];
            }
        }

        // Check tableau top cards
        for ($col = 0; $col < 7; $col++) {
            if (!empty($state['tableau'][$col])) {
                $card = end($state['tableau'][$col]);
                if ($card['faceUp']) {
                    $suit = $card['suit'];
                    if (self::canPlaceOnFoundation($card, $state['foundations'][$suit])) {
                        $autoMoves[] = ['type' => 'tableau_to_foundation', 'col' => $col, 'suit' => $suit];
                    }
                }
            }
        }

        return $autoMoves;
    }

    /**
     * Calculate final score based on scoring mode
     */
    public static function getScore(array $state): int
    {
        $score = $state['score'];
        $isWon = self::isGameWon($state);
        $scoringMode = $state['scoringMode'] ?? 'standard';

        switch ($scoringMode) {
            case 'vegas':
                return self::calculateVegasScore($state);
                
            case 'timed':
                return self::calculateTimedScore($state);
                
            default: // standard
                return self::calculateStandardScore($state);
        }
    }

    /**
     * Calculate standard scoring
     */
    private static function calculateStandardScore(array $state): int
    {
        $score = $state['score'];

        if (self::isGameWon($state)) {
            $score += 500; // Base win bonus
            
            // Time bonus (max 5 minutes for full bonus)
            $timeBonus = max(0, 300 - $state['gameTime']);
            $score += $timeBonus;
            
            // Move efficiency bonus
            $moveBonus = max(0, 200 - $state['moves']);
            $score += $moveBonus;
            
            // Perfect game bonus (no undo used)
            if ($state['perfectGame'] ?? false) {
                $score += 300;
            }
            
            // Difficulty multiplier
            $difficulty = $state['difficulty'] ?? 'normal';
            $multiplier = match($difficulty) {
                'easy' => 0.8,
                'hard' => 1.5,
                default => 1.0
            };
            
            $score = (int)($score * $multiplier);
        }

        return max(0, $score);
    }

    /**
     * Calculate Vegas scoring (money-based)
     */
    private static function calculateVegasScore(array $state): int
    {
        // Vegas scoring: -$52 to start, +$5 per card to foundation
        $score = -52 + ($state['cardsToFoundation'] * 5);
        
        if (self::isGameWon($state)) {
            $score += 208; // Bonus for completing ($5 * 52 cards - $52 cost = $208 profit)
        }
        
        return $score;
    }

    /**
     * Calculate timed scoring with pressure
     */
    private static function calculateTimedScore(array $state): int
    {
        $score = $state['score'];
        $gameTime = $state['gameTime'];
        
        // Time pressure penalties
        if ($gameTime > 300) { // 5 minutes
            $penalty = ($gameTime - 300) * 2; // 2 points per second over 5 minutes
            $score -= $penalty;
        }
        
        if (self::isGameWon($state)) {
            // Time-based win bonus (more points for faster completion)
            $timeBonus = max(0, 600 - $gameTime); // Up to 10 minutes
            $score += $timeBonus * 2;
            
            // Speed bonus for very fast completion
            if ($gameTime < 180) { // Under 3 minutes
                $score += 500;
            } elseif ($gameTime < 240) { // Under 4 minutes
                $score += 300;
            }
        }
        
        return max(0, $score);
    }

    /**
     * Get game statistics
     */
    public static function getStats(array $state): array
    {
        $foundationCards = array_sum(array_map('count', $state['foundations']));
        $tableauCards = array_sum(array_map('count', $state['tableau']));
        $stockWasteCards = count($state['stock']) + count($state['waste']);

        return [
            'foundationCards' => $foundationCards,
            'tableauCards' => $tableauCards,
            'stockCards' => count($state['stock']),
            'wasteCards' => count($state['waste']),
            'moves' => $state['moves'],
            'score' => $state['score'],
            'gameTime' => $state['gameTime'],
            'completion' => round(($foundationCards / 52) * 100, 1)
        ];
    }
}
