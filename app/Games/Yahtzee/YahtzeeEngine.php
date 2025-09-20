<?php

namespace App\Games\Yahtzee;

/**
 * Yahtzee Engine - Pure game logic functions
 */
class YahtzeeEngine
{
    public const CATEGORIES = [
        'aces', 'twos', 'threes', 'fours', 'fives', 'sixes',
        'three_of_a_kind', 'four_of_a_kind', 'full_house', 
        'small_straight', 'large_straight', 'yahtzee', 'chance'
    ];

    public const UPPER_SECTION = ['aces', 'twos', 'threes', 'fours', 'fives', 'sixes'];
    public const UPPER_BONUS_THRESHOLD = 63;
    public const UPPER_BONUS_POINTS = 35;

    public static function newGame(): array
    {
        return [
            'dice' => [1, 1, 1, 1, 1],
            'diceHeld' => [false, false, false, false, false],
            'rollsRemaining' => 3,
            'currentTurn' => 1,
            'scorecard' => array_fill_keys(self::CATEGORIES, null),
            'gameOver' => false,
            'phase' => 'rolling' // 'rolling' or 'scoring'
        ];
    }

    public static function isGameOver(array $state): bool
    {
        return $state['gameOver'] || $state['currentTurn'] > 13;
    }

    public static function validateMove(array $state, array $move): bool
    {
        $action = $move['action'] ?? '';

        switch ($action) {
            case 'roll':
                return self::canRoll($state);
            
            case 'hold_dice':
                return $state['phase'] === 'rolling' && 
                       isset($move['diceIndex']) && 
                       $move['diceIndex'] >= 0 && 
                       $move['diceIndex'] < 5;
            
            case 'score':
                return $state['phase'] === 'scoring' && 
                       isset($move['category']) && 
                       in_array($move['category'], self::CATEGORIES) &&
                       $state['scorecard'][$move['category']] === null;
            
            default:
                return false;
        }
    }

    public static function applyMove(array $state, array $move): array
    {
        $action = $move['action'] ?? '';

        switch ($action) {
            case 'roll':
                return self::rollDice($state);
            
            case 'hold_dice':
                return self::toggleDiceHold($state, $move['diceIndex']);
            
            case 'score':
                return self::scoreCategory($state, $move['category']);
            
            default:
                return $state;
        }
    }

    public static function canRoll(array $state): bool
    {
        return $state['phase'] === 'rolling' && 
               $state['rollsRemaining'] > 0 && 
               !$state['gameOver'];
    }

    public static function rollDice(array $state): array
    {
        if (!self::canRoll($state)) {
            return $state;
        }

        // Roll only the non-held dice
        for ($i = 0; $i < 5; $i++) {
            if (!$state['diceHeld'][$i]) {
                $state['dice'][$i] = rand(1, 6);
            }
        }

        $state['rollsRemaining']--;
        
        // After the first roll of a turn, switch to scoring phase if no rolls left
        if ($state['rollsRemaining'] === 0) {
            $state['phase'] = 'scoring';
        }

        return $state;
    }

    public static function toggleDiceHold(array $state, int $diceIndex): array
    {
        if ($diceIndex >= 0 && $diceIndex < 5) {
            $state['diceHeld'][$diceIndex] = !$state['diceHeld'][$diceIndex];
        }
        return $state;
    }

    public static function scoreCategory(array $state, string $category): array
    {
        if ($state['scorecard'][$category] !== null) {
            return $state; // Category already scored
        }

        // Calculate score for this category
        $score = self::calculateCategoryScore($state['dice'], $category);
        $state['scorecard'][$category] = $score;

        // Advance to next turn
        $state['currentTurn']++;
        $state['rollsRemaining'] = 3;
        $state['diceHeld'] = [false, false, false, false, false];
        $state['phase'] = 'rolling';

        // Check if game is over
        if ($state['currentTurn'] > 13) {
            $state['gameOver'] = true;
        }

        return $state;
    }

    public static function calculateCategoryScore(array $dice, string $category): int
    {
        $counts = array_count_values($dice);
        $sum = array_sum($dice);

        switch ($category) {
            case 'aces':
                return ($counts[1] ?? 0) * 1;
            case 'twos':
                return ($counts[2] ?? 0) * 2;
            case 'threes':
                return ($counts[3] ?? 0) * 3;
            case 'fours':
                return ($counts[4] ?? 0) * 4;
            case 'fives':
                return ($counts[5] ?? 0) * 5;
            case 'sixes':
                return ($counts[6] ?? 0) * 6;
            
            case 'three_of_a_kind':
                foreach ($counts as $count) {
                    if ($count >= 3) return $sum;
                }
                return 0;
            
            case 'four_of_a_kind':
                foreach ($counts as $count) {
                    if ($count >= 4) return $sum;
                }
                return 0;
            
            case 'full_house':
                $hasThree = false;
                $hasTwo = false;
                foreach ($counts as $count) {
                    if ($count === 3) $hasThree = true;
                    if ($count === 2) $hasTwo = true;
                }
                return ($hasThree && $hasTwo) ? 25 : 0;
            
            case 'small_straight':
                sort($dice);
                $unique = array_unique($dice);
                sort($unique);
                $str = implode('', $unique);
                if (strpos($str, '1234') !== false || 
                    strpos($str, '2345') !== false || 
                    strpos($str, '3456') !== false) {
                    return 30;
                }
                return 0;
            
            case 'large_straight':
                sort($dice);
                $unique = array_unique($dice);
                sort($unique);
                $str = implode('', $unique);
                if ($str === '12345' || $str === '23456') {
                    return 40;
                }
                return 0;
            
            case 'yahtzee':
                return (count($counts) === 1) ? 50 : 0;
            
            case 'chance':
                return $sum;
            
            default:
                return 0;
        }
    }

    public static function calculateTotalScore(array $state): int
    {
        $scorecard = $state['scorecard'];
        $total = 0;

        // Upper section
        $upperTotal = 0;
        foreach (self::UPPER_SECTION as $category) {
            if ($scorecard[$category] !== null) {
                $upperTotal += $scorecard[$category];
            }
        }
        $total += $upperTotal;

        // Upper section bonus
        if ($upperTotal >= self::UPPER_BONUS_THRESHOLD) {
            $total += self::UPPER_BONUS_POINTS;
        }

        // Lower section
        $lowerCategories = array_diff(self::CATEGORIES, self::UPPER_SECTION);
        foreach ($lowerCategories as $category) {
            if ($scorecard[$category] !== null) {
                $total += $scorecard[$category];
            }
        }

        return $total;
    }

    public static function getScorecard(array $state): array
    {
        $scorecard = $state['scorecard'];
        $upperTotal = 0;
        $upperCount = 0;

        // Calculate upper section totals
        foreach (self::UPPER_SECTION as $category) {
            if ($scorecard[$category] !== null) {
                $upperTotal += $scorecard[$category];
                $upperCount++;
            }
        }

        $upperBonus = ($upperTotal >= self::UPPER_BONUS_THRESHOLD) ? self::UPPER_BONUS_POINTS : 0;
        $needsForBonus = max(0, self::UPPER_BONUS_THRESHOLD - $upperTotal);

        return [
            'scorecard' => $scorecard,
            'upperTotal' => $upperTotal,
            'upperBonus' => $upperBonus,
            'needsForBonus' => $needsForBonus,
            'grandTotal' => self::calculateTotalScore($state)
        ];
    }

    public static function getPossibleScores(array $state): array
    {
        $possibleScores = [];
        $dice = $state['dice'];

        foreach (self::CATEGORIES as $category) {
            if ($state['scorecard'][$category] === null) {
                $possibleScores[$category] = self::calculateCategoryScore($dice, $category);
            }
        }

        return $possibleScores;
    }
}
