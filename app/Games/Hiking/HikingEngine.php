<?php

namespace App\Games\Hiking;

class HikingEngine
{
    // Game constants
    public const TARGET_DISTANCE = 1000;
    public const MAX_TURNS = 50;
    public const HAND_SIZE = 6;

    // Card types
    public const CARD_DISTANCE = 'distance';
    public const CARD_HAZARD = 'hazard';
    public const CARD_REMEDY = 'remedy';
    public const CARD_SAFETY = 'safety';

    // Distance values
    public const DISTANCE_25 = 25;
    public const DISTANCE_50 = 50;
    public const DISTANCE_75 = 75;
    public const DISTANCE_100 = 100;
    public const DISTANCE_200 = 200;

    // Hazard types
    public const HAZARD_INJURY = 'injury';
    public const HAZARD_DEHYDRATION = 'dehydration';
    public const HAZARD_BLISTERS = 'blisters';
    public const HAZARD_TRAIL_BLOCKAGE = 'trail_blockage';
    public const HAZARD_SLOW_PACE = 'slow_pace';

    // Remedy types
    public const REMEDY_FIRST_AID = 'first_aid';
    public const REMEDY_WATER_SUPPLY = 'water_supply';
    public const REMEDY_MOLESKIN = 'moleskin';
    public const REMEDY_ALTERNATE_ROUTE = 'alternate_route';
    public const REMEDY_ENERGY_BOOST = 'energy_boost';

    // Safety types
    public const SAFETY_MEDICAL_TRAINING = 'medical_training';
    public const SAFETY_HYDRATION_PLAN = 'hydration_plan';
    public const SAFETY_PROPER_FOOTWEAR = 'proper_footwear';
    public const SAFETY_TRAIL_MAP = 'trail_map';
    public const SAFETY_ENDURANCE_TRAINING = 'endurance_training';

    /**
     * Create the complete hiking deck
     */
    public static function createDeck(): array
    {
        $deck = [];

        // Distance cards (multiple copies for variety)
        $distanceCards = [
            ['type' => self::CARD_DISTANCE, 'value' => self::DISTANCE_25, 'name' => '25km'],
            ['type' => self::CARD_DISTANCE, 'value' => self::DISTANCE_50, 'name' => '50km'],
            ['type' => self::CARD_DISTANCE, 'value' => self::DISTANCE_75, 'name' => '75km'],
            ['type' => self::CARD_DISTANCE, 'value' => self::DISTANCE_100, 'name' => '100km'],
            ['type' => self::CARD_DISTANCE, 'value' => self::DISTANCE_200, 'name' => '200km'],
        ];

        // Add multiple copies of distance cards
        foreach ($distanceCards as $card) {
            for ($i = 0; $i < 4; $i++) {
                $deck[] = $card;
            }
        }

        // Hazard cards
        $hazardCards = [
            ['type' => self::CARD_HAZARD, 'value' => self::HAZARD_INJURY, 'name' => 'Injury'],
            ['type' => self::CARD_HAZARD, 'value' => self::HAZARD_DEHYDRATION, 'name' => 'Dehydration'],
            ['type' => self::CARD_HAZARD, 'value' => self::HAZARD_BLISTERS, 'name' => 'Blisters'],
            ['type' => self::CARD_HAZARD, 'value' => self::HAZARD_TRAIL_BLOCKAGE, 'name' => 'Trail Blockage'],
            ['type' => self::CARD_HAZARD, 'value' => self::HAZARD_SLOW_PACE, 'name' => 'Slow Pace'],
        ];

        foreach ($hazardCards as $card) {
            for ($i = 0; $i < 3; $i++) {
                $deck[] = $card;
            }
        }

        // Remedy cards
        $remedyCards = [
            ['type' => self::CARD_REMEDY, 'value' => self::REMEDY_FIRST_AID, 'name' => 'First Aid Kit'],
            ['type' => self::CARD_REMEDY, 'value' => self::REMEDY_WATER_SUPPLY, 'name' => 'Water Supply'],
            ['type' => self::CARD_REMEDY, 'value' => self::REMEDY_MOLESKIN, 'name' => 'Moleskin'],
            ['type' => self::CARD_REMEDY, 'value' => self::REMEDY_ALTERNATE_ROUTE, 'name' => 'Alternate Route'],
            ['type' => self::CARD_REMEDY, 'value' => self::REMEDY_ENERGY_BOOST, 'name' => 'Energy Boost'],
        ];

        foreach ($remedyCards as $card) {
            for ($i = 0; $i < 3; $i++) {
                $deck[] = $card;
            }
        }

        // Safety cards
        $safetyCards = [
            ['type' => self::CARD_SAFETY, 'value' => self::SAFETY_MEDICAL_TRAINING, 'name' => 'Medical Training'],
            ['type' => self::CARD_SAFETY, 'value' => self::SAFETY_HYDRATION_PLAN, 'name' => 'Hydration Plan'],
            ['type' => self::CARD_SAFETY, 'value' => self::SAFETY_PROPER_FOOTWEAR, 'name' => 'Proper Footwear'],
            ['type' => self::CARD_SAFETY, 'value' => self::SAFETY_TRAIL_MAP, 'name' => 'Trail Map'],
            ['type' => self::CARD_SAFETY, 'value' => self::SAFETY_ENDURANCE_TRAINING, 'name' => 'Endurance Training'],
        ];

        foreach ($safetyCards as $card) {
            for ($i = 0; $i < 2; $i++) {
                $deck[] = $card;
            }
        }

        return $deck;
    }

    /**
     * Initialize new game state
     */
    public static function newGame(): array
    {
        $deck = self::createDeck();
        shuffle($deck);

        // Deal initial hand
        $hand = array_slice($deck, 0, self::HAND_SIZE);
        $drawPile = array_slice($deck, self::HAND_SIZE);

        return [
            'hand' => $hand,
            'drawPile' => $drawPile,
            'discardPile' => [],
            'distance' => 0,
            'turn' => 1,
            'maxTurns' => self::MAX_TURNS,
            'activeHazards' => [],
            'activeSafety' => [],
            'gameOver' => false,
            'winner' => null,
            'lastAction' => 'Game started! Draw your hand and begin your hike.',
            'message' => 'Welcome to the Mountain Trail! Complete 1000km in 50 turns.',
            'hazardDeck' => self::createHazardDeck(),
            'currentHazard' => null,
        ];
    }

    /**
     * Create separate hazard deck for random events
     */
    public static function createHazardDeck(): array
    {
        $hazards = [
            ['type' => self::CARD_HAZARD, 'value' => self::HAZARD_INJURY, 'name' => 'Injury'],
            ['type' => self::CARD_HAZARD, 'value' => self::HAZARD_DEHYDRATION, 'name' => 'Dehydration'],
            ['type' => self::CARD_HAZARD, 'value' => self::HAZARD_BLISTERS, 'name' => 'Blisters'],
            ['type' => self::CARD_HAZARD, 'value' => self::HAZARD_TRAIL_BLOCKAGE, 'name' => 'Trail Blockage'],
            ['type' => self::CARD_HAZARD, 'value' => self::HAZARD_SLOW_PACE, 'name' => 'Slow Pace'],
        ];

        $hazardDeck = [];
        foreach ($hazards as $hazard) {
            for ($i = 0; $i < 4; $i++) {
                $hazardDeck[] = $hazard;
            }
        }

        shuffle($hazardDeck);
        return $hazardDeck;
    }

    /**
     * Draw a random hazard at the start of each turn
     */
    public static function drawRandomHazard(array $state): array
    {
        if (empty($state['hazardDeck'])) {
            // Reshuffle hazard deck if empty
            $state['hazardDeck'] = self::createHazardDeck();
        }

        $hazard = array_shift($state['hazardDeck']);
        $state['currentHazard'] = $hazard;

        // Check if safety card prevents this hazard
        $hazardValue = $hazard['value'];
        $safetyPrevention = [
            self::HAZARD_INJURY => self::SAFETY_MEDICAL_TRAINING,
            self::HAZARD_DEHYDRATION => self::SAFETY_HYDRATION_PLAN,
            self::HAZARD_BLISTERS => self::SAFETY_PROPER_FOOTWEAR,
            self::HAZARD_TRAIL_BLOCKAGE => self::SAFETY_TRAIL_MAP,
            self::HAZARD_SLOW_PACE => self::SAFETY_ENDURANCE_TRAINING,
        ];

        if (in_array($safetyPrevention[$hazardValue] ?? null, $state['activeSafety'])) {
            $state['currentHazard'] = null;
            $state['lastAction'] = "Safety card prevented {$hazard['name']}!";
            $state['message'] = "Your {$safetyPrevention[$hazardValue]} protected you from {$hazard['name']}.";
        } else {
            $state['activeHazards'][] = $hazardValue;
            $state['lastAction'] = "Hazard encountered: {$hazard['name']}";
            $state['message'] = "You encountered {$hazard['name']}! Play a remedy card to continue.";
        }

        return $state;
    }

    /**
     * Validate a move
     */
    public static function validateMove(array $state, array $move): bool
    {
        if ($state['gameOver']) {
            return false;
        }

        if (!isset($move['action'])) {
            return false;
        }

        switch ($move['action']) {
            case 'play_card':
                return self::validatePlayCard($state, $move);
            case 'draw_card':
                return self::validateDrawCard($state);
            case 'discard_card':
                return self::validateDiscardCard($state, $move);
            case 'end_turn':
                return self::validateEndTurn($state);
            default:
                return false;
        }
    }

    /**
     * Validate playing a card
     */
    public static function validatePlayCard(array $state, array $move): bool
    {
        if (!isset($move['cardIndex']) || !is_numeric($move['cardIndex'])) {
            return false;
        }

        $cardIndex = (int) $move['cardIndex'];
        if ($cardIndex < 0 || $cardIndex >= count($state['hand'])) {
            return false;
        }

        $card = $state['hand'][$cardIndex];

        switch ($card['type']) {
            case self::CARD_DISTANCE:
                return self::canPlayDistanceCard($state, $card);
            case self::CARD_REMEDY:
                return self::canPlayRemedyCard($state, $card);
            case self::CARD_SAFETY:
                return self::canPlaySafetyCard($state, $card);
            default:
                return false;
        }
    }

    /**
     * Check if distance card can be played
     */
    public static function canPlayDistanceCard(array $state, array $card): bool
    {
        // Check for slow pace restriction first
        if (in_array(self::HAZARD_SLOW_PACE, $state['activeHazards'])) {
            return in_array($card['value'], [self::DISTANCE_25, self::DISTANCE_50]);
        }

        // Can't play distance cards if there are other unresolved hazards
        if (!empty($state['activeHazards'])) {
            return false;
        }

        return true;
    }

    /**
     * Check if remedy card can be played
     */
    public static function canPlayRemedyCard(array $state, array $card): bool
    {
        $remedyMap = [
            self::REMEDY_FIRST_AID => self::HAZARD_INJURY,
            self::REMEDY_WATER_SUPPLY => self::HAZARD_DEHYDRATION,
            self::REMEDY_MOLESKIN => self::HAZARD_BLISTERS,
            self::REMEDY_ALTERNATE_ROUTE => self::HAZARD_TRAIL_BLOCKAGE,
            self::REMEDY_ENERGY_BOOST => self::HAZARD_SLOW_PACE,
        ];

        $targetHazard = $remedyMap[$card['value']] ?? null;
        return $targetHazard && in_array($targetHazard, $state['activeHazards']);
    }

    /**
     * Check if safety card can be played
     */
    public static function canPlaySafetyCard(array $state, array $card): bool
    {
        // Safety cards can be played at any time, but only once
        return !in_array($card['value'], $state['activeSafety']);
    }

    /**
     * Validate drawing a card
     */
    public static function validateDrawCard(array $state): bool
    {
        return !empty($state['drawPile']) && count($state['hand']) < self::HAND_SIZE;
    }

    /**
     * Validate discarding a card
     */
    public static function validateDiscardCard(array $state, array $move): bool
    {
        if (!isset($move['cardIndex']) || !is_numeric($move['cardIndex'])) {
            return false;
        }

        $cardIndex = (int) $move['cardIndex'];
        return $cardIndex >= 0 && $cardIndex < count($state['hand']);
    }

    /**
     * Validate ending turn
     */
    public static function validateEndTurn(array $state): bool
    {
        return true; // Can always end turn
    }

    /**
     * Apply a move to the game state
     */
    public static function applyMove(array $state, array $move): array
    {
        if (!self::validateMove($state, $move)) {
            return $state;
        }

        switch ($move['action']) {
            case 'play_card':
                return self::playCard($state, $move);
            case 'draw_card':
                return self::drawCard($state);
            case 'discard_card':
                return self::discardCard($state, $move);
            case 'end_turn':
                return self::endTurn($state);
            default:
                return $state;
        }
    }

    /**
     * Play a card
     */
    public static function playCard(array $state, array $move): array
    {
        $cardIndex = (int) $move['cardIndex'];
        $card = $state['hand'][$cardIndex];

        // Remove card from hand
        array_splice($state['hand'], $cardIndex, 1);

        // Add to discard pile
        $state['discardPile'][] = $card;

        switch ($card['type']) {
            case self::CARD_DISTANCE:
                return self::playDistanceCard($state, $card);
            case self::CARD_REMEDY:
                return self::playRemedyCard($state, $card);
            case self::CARD_SAFETY:
                return self::playSafetyCard($state, $card);
        }

        return $state;
    }

    /**
     * Play a distance card
     */
    public static function playDistanceCard(array $state, array $card): array
    {
        $state['distance'] += $card['value'];
        $state['lastAction'] = "Hiked {$card['name']}";
        $state['message'] = "You hiked {$card['name']}! Total distance: {$state['distance']}km";

        // Check for win condition
        if ($state['distance'] >= self::TARGET_DISTANCE) {
            $state['gameOver'] = true;
            $state['winner'] = 'player';
            $state['message'] = "Congratulations! You completed the 1000km trail!";
        }

        return $state;
    }

    /**
     * Play a remedy card
     */
    public static function playRemedyCard(array $state, array $card): array
    {
        $remedyMap = [
            self::REMEDY_FIRST_AID => self::HAZARD_INJURY,
            self::REMEDY_WATER_SUPPLY => self::HAZARD_DEHYDRATION,
            self::REMEDY_MOLESKIN => self::HAZARD_BLISTERS,
            self::REMEDY_ALTERNATE_ROUTE => self::HAZARD_TRAIL_BLOCKAGE,
            self::REMEDY_ENERGY_BOOST => self::HAZARD_SLOW_PACE,
        ];

        $targetHazard = $remedyMap[$card['value']] ?? null;
        if ($targetHazard && in_array($targetHazard, $state['activeHazards'])) {
            $state['activeHazards'] = array_values(array_filter($state['activeHazards'], fn($h) => $h !== $targetHazard));
            $state['lastAction'] = "Used {$card['name']}";
            $state['message'] = "You used {$card['name']} to overcome the hazard!";
        }

        return $state;
    }

    /**
     * Play a safety card
     */
    public static function playSafetyCard(array $state, array $card): array
    {
        $state['activeSafety'][] = $card['value'];
        $state['lastAction'] = "Played {$card['name']}";
        $state['message'] = "You played {$card['name']} for protection!";

        return $state;
    }

    /**
     * Draw a card
     */
    public static function drawCard(array $state): array
    {
        if (empty($state['drawPile'])) {
            return $state;
        }

        $card = array_shift($state['drawPile']);
        $state['hand'][] = $card;
        $state['lastAction'] = "Drew a card";
        $state['message'] = "You drew {$card['name']}";

        return $state;
    }

    /**
     * Discard a card
     */
    public static function discardCard(array $state, array $move): array
    {
        $cardIndex = (int) $move['cardIndex'];
        $card = $state['hand'][$cardIndex];

        array_splice($state['hand'], $cardIndex, 1);
        $state['discardPile'][] = $card;

        $state['lastAction'] = "Discarded {$card['name']}";
        $state['message'] = "You discarded {$card['name']}";

        return $state;
    }

    /**
     * End turn
     */
    public static function endTurn(array $state): array
    {
        // Draw random hazard
        $state = self::drawRandomHazard($state);

        // Increment turn
        $state['turn']++;

        // Check for game over (out of turns)
        if ($state['turn'] > $state['maxTurns']) {
            $state['gameOver'] = true;
            $state['winner'] = 'timeout';
            $state['message'] = "Time's up! You didn't complete the trail in time.";
        }

        return $state;
    }

    /**
     * Check if game is over
     */
    public static function isGameOver(array $state): bool
    {
        return $state['gameOver'] ?? false;
    }

    /**
     * Get winner
     */
    public static function getWinner(array $state): ?string
    {
        if (!$state['gameOver']) {
            return null;
        }

        return $state['winner'];
    }

    /**
     * Calculate score
     */
    public static function calculateScore(array $state): int
    {
        if (!$state['gameOver']) {
            return 0;
        }

        $score = 0;

        // Base score for distance covered
        $score += $state['distance'];

        // Bonus for completing the trail
        if ($state['winner'] === 'player') {
            $score += 1000;
        }

        // Efficiency bonus (fewer turns used)
        $turnsUsed = $state['turn'] - 1;
        if ($turnsUsed < $state['maxTurns']) {
            $score += ($state['maxTurns'] - $turnsUsed) * 10;
        }

        // Safety cards bonus
        $score += count($state['activeSafety']) * 50;

        return max(0, $score);
    }

    /**
     * Get game statistics
     */
    public static function getStats(array $state): array
    {
        return [
            'distance' => $state['distance'],
            'targetDistance' => self::TARGET_DISTANCE,
            'turn' => $state['turn'],
            'maxTurns' => $state['maxTurns'],
            'handSize' => count($state['hand']),
            'drawPileSize' => count($state['drawPile']),
            'activeHazards' => count($state['activeHazards']),
            'activeSafety' => count($state['activeSafety']),
            'progress' => min(100, (int) round(($state['distance'] / self::TARGET_DISTANCE) * 100)),
        ];
    }

    /**
     * Autoplay the entire game
     */
    public static function autoplayGame(array $state): array
    {
        $maxMoves = 1000; // Prevent infinite loops
        $moves = 0;

        while (!$state['gameOver'] && $moves < $maxMoves) {
            // Simple AI: try to play distance cards, then remedies, then draw/end turn
            $moved = false;

            // Try to play distance cards
            for ($i = 0; $i < count($state['hand']); $i++) {
                $card = $state['hand'][$i];
                if ($card['type'] === self::CARD_DISTANCE && self::canPlayDistanceCard($state, $card)) {
                    $state = self::playCard($state, ['action' => 'play_card', 'cardIndex' => $i]);
                    $moved = true;
                    break;
                }
            }

            // Try to play remedy cards
            if (!$moved) {
                for ($i = 0; $i < count($state['hand']); $i++) {
                    $card = $state['hand'][$i];
                    if ($card['type'] === self::CARD_REMEDY && self::canPlayRemedyCard($state, $card)) {
                        $state = self::playCard($state, ['action' => 'play_card', 'cardIndex' => $i]);
                        $moved = true;
                        break;
                    }
                }
            }

            // Try to play safety cards
            if (!$moved) {
                for ($i = 0; $i < count($state['hand']); $i++) {
                    $card = $state['hand'][$i];
                    if ($card['type'] === self::CARD_SAFETY && self::canPlaySafetyCard($state, $card)) {
                        $state = self::playCard($state, ['action' => 'play_card', 'cardIndex' => $i]);
                        $moved = true;
                        break;
                    }
                }
            }

            // Draw card if hand is small
            if (!$moved && count($state['hand']) < self::HAND_SIZE && !empty($state['drawPile'])) {
                $state = self::drawCard($state);
                $moved = true;
            }

            // End turn if nothing else to do
            if (!$moved) {
                $state = self::endTurn($state);
            }

            $moves++;
        }

        return $state;
    }
}
