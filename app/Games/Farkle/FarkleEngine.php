<?php

namespace App\Games\Farkle;

/**
 * Farkle Engine - Classic dice game of risk and reward
 */
class FarkleEngine
{
    public const DICE_COUNT = 6;
    public const WINNING_SCORE = 10000;
    public const MIN_SCORE_TO_START = 500;
    public const HOT_DICE_BONUS = 1000;

    public const SCORING = [
        'single_1' => 100,
        'single_5' => 50,
        'three_1s' => 1000,
        'three_2s' => 200,
        'three_3s' => 300,
        'three_4s' => 400,
        'three_5s' => 500,
        'three_6s' => 600,
        'four_of_a_kind' => 1000,
        'five_of_a_kind' => 2000,
        'six_of_a_kind' => 3000,
        'straight' => 1500,
        'three_pairs' => 1500,
        'two_triplets' => 2500
    ];

    public static function newGame(): array
    {
        $players = [
            [
                'id' => 'player',
                'name' => 'You',
                'score' => 0,
                'isHuman' => true
            ],
            [
                'id' => 'ai1',
                'name' => 'Alice',
                'score' => 0,
                'isHuman' => false
            ],
            [
                'id' => 'ai2',
                'name' => 'Bob',
                'score' => 0,
                'isHuman' => false
            ]
        ];

        return [
            'players' => $players,
            'currentPlayer' => 0,
            'dice' => [],
            'selectedDice' => [],
            'turnScore' => 0,
            'gameOver' => false,
            'gameStarted' => false,
            'winner' => null,
            'gamePhase' => 'playing', // playing, game_over
            'turnPhase' => 'rolling', // rolling, selecting, banking
            'lastRoll' => null,
            'farkleCount' => 0,
            'consecutiveRolls' => 0,
            'hotDice' => false,
            'moveHistory' => []
        ];
    }

    public static function validateMove(array $state, array $move): bool
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'roll_dice':
                return self::canRollDice($state);
            
            case 'select_dice':
                $diceIndices = $move['diceIndices'] ?? [];
                return self::canSelectDice($state) && 
                       !empty($diceIndices) && 
                       self::areValidSelections($state, $diceIndices);
            
            case 'bank_points':
                return self::canBankPoints($state);
            
            case 'start_game':
                return !$state['gameStarted'] && !$state['gameOver'];
            
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
            case 'roll_dice':
                return self::rollDice($state);
            
            case 'select_dice':
                return self::selectDice($state, $move['diceIndices']);
            
            case 'bank_points':
                return self::bankPoints($state);
            
            case 'start_game':
                $state['gameStarted'] = true;
                return $state;
            
            case 'new_game':
                return self::newGame();
            
            default:
                return $state;
        }
    }

    public static function rollDice(array $state): array
    {
        // Save state for undo
        $state['moveHistory'][] = self::createMoveSnapshot($state);
        
        $diceCount = empty($state['dice']) ? self::DICE_COUNT : count($state['dice']);
        $dice = [];
        
        for ($i = 0; $i < $diceCount; $i++) {
            $dice[] = rand(1, 6);
        }
        
        $state['dice'] = $dice;
        $state['selectedDice'] = [];
        $state['lastRoll'] = $dice;
        $state['consecutiveRolls']++;
        
        // Check for scoring combinations
        $scoringCombinations = self::findScoringCombinations($dice);
        
        if (empty($scoringCombinations)) {
            // Farkle!
            $state = self::handleFarkle($state);
        } else {
            $state['turnPhase'] = 'selecting';
        }
        
        return $state;
    }

    public static function selectDice(array $state, array $diceIndices): array
    {
        $dice = $state['dice'];
        $selectedDice = [];
        
        foreach ($diceIndices as $index) {
            if (isset($dice[$index])) {
                $selectedDice[] = $dice[$index];
            }
        }
        
        // Validate selection
        if (!self::isValidSelection($selectedDice)) {
            return $state;
        }
        
        $state['selectedDice'] = $selectedDice;
        $state['turnScore'] += self::calculateSelectionScore($selectedDice);
        
        // Remove selected dice
        $remainingDice = [];
        foreach ($dice as $index => $value) {
            if (!in_array($index, $diceIndices)) {
                $remainingDice[] = $value;
            }
        }
        
        $state['dice'] = $remainingDice;
        
        // Check if all dice are used (hot dice)
        if (empty($remainingDice)) {
            $state['hotDice'] = true;
            $state['turnScore'] += self::HOT_DICE_BONUS;
            $state['dice'] = []; // Will roll all 6 dice again
        }
        
        $state['turnPhase'] = 'banking';
        
        return $state;
    }

    public static function bankPoints(array $state): array
    {
        $currentPlayer = &$state['players'][$state['currentPlayer']];
        $currentPlayer['score'] += $state['turnScore'];
        
        // Check for win
        if ($currentPlayer['score'] >= self::WINNING_SCORE) {
            $state['gameOver'] = true;
            $state['gamePhase'] = 'game_over';
            $state['winner'] = $state['currentPlayer'];
            return $state;
        }
        
        // Reset turn
        $state['turnScore'] = 0;
        $state['dice'] = [];
        $state['selectedDice'] = [];
        $state['turnPhase'] = 'rolling';
        $state['consecutiveRolls'] = 0;
        $state['hotDice'] = false;
        
        // Next player
        $state['currentPlayer'] = ($state['currentPlayer'] + 1) % count($state['players']);
        
        return $state;
    }

    public static function handleFarkle(array $state): array
    {
        $state['turnScore'] = 0;
        $state['dice'] = [];
        $state['selectedDice'] = [];
        $state['turnPhase'] = 'rolling';
        $state['consecutiveRolls'] = 0;
        $state['hotDice'] = false;
        $state['farkleCount']++;
        
        // Next player
        $state['currentPlayer'] = ($state['currentPlayer'] + 1) % count($state['players']);
        
        return $state;
    }

    public static function findScoringCombinations(array $dice): array
    {
        $combinations = [];
        $counts = array_count_values($dice);
        
        // Single 1s and 5s
        if (isset($counts[1])) {
            $combinations[] = ['type' => 'single_1s', 'count' => $counts[1], 'dice' => array_fill(0, $counts[1], 1)];
        }
        if (isset($counts[5])) {
            $combinations[] = ['type' => 'single_5s', 'count' => $counts[5], 'dice' => array_fill(0, $counts[5], 5)];
        }
        
        // Three of a kind
        foreach ($counts as $value => $count) {
            if ($count >= 3) {
                $combinations[] = [
                    'type' => "three_{$value}s",
                    'count' => 3,
                    'dice' => array_fill(0, 3, $value)
                ];
            }
        }
        
        // Four of a kind
        foreach ($counts as $value => $count) {
            if ($count >= 4) {
                $combinations[] = [
                    'type' => 'four_of_a_kind',
                    'count' => 4,
                    'dice' => array_fill(0, 4, $value)
                ];
            }
        }
        
        // Five of a kind
        foreach ($counts as $value => $count) {
            if ($count >= 5) {
                $combinations[] = [
                    'type' => 'five_of_a_kind',
                    'count' => 5,
                    'dice' => array_fill(0, 5, $value)
                ];
            }
        }
        
        // Six of a kind
        foreach ($counts as $value => $count) {
            if ($count >= 6) {
                $combinations[] = [
                    'type' => 'six_of_a_kind',
                    'count' => 6,
                    'dice' => array_fill(0, 6, $value)
                ];
            }
        }
        
        // Straight (1-6)
        if (count($dice) >= 6 && 
            isset($counts[1]) && isset($counts[2]) && isset($counts[3]) &&
            isset($counts[4]) && isset($counts[5]) && isset($counts[6])) {
            $combinations[] = [
                'type' => 'straight',
                'count' => 6,
                'dice' => [1, 2, 3, 4, 5, 6]
            ];
        }
        
        // Three pairs
        $pairCount = 0;
        foreach ($counts as $count) {
            if ($count >= 2) {
                $pairCount += floor($count / 2);
            }
        }
        if ($pairCount >= 3) {
            $combinations[] = [
                'type' => 'three_pairs',
                'count' => 6,
                'dice' => $dice
            ];
        }
        
        // Two triplets
        $tripletCount = 0;
        foreach ($counts as $count) {
            if ($count >= 3) {
                $tripletCount++;
            }
        }
        if ($tripletCount >= 2) {
            $combinations[] = [
                'type' => 'two_triplets',
                'count' => 6,
                'dice' => $dice
            ];
        }
        
        return $combinations;
    }

    public static function isValidSelection(array $selectedDice): bool
    {
        if (empty($selectedDice)) {
            return false;
        }
        
        $combinations = self::findScoringCombinations($selectedDice);
        return !empty($combinations);
    }

    public static function areValidSelections(array $state, array $diceIndices): bool
    {
        $dice = $state['dice'];
        $selectedDice = [];
        
        foreach ($diceIndices as $index) {
            if (isset($dice[$index])) {
                $selectedDice[] = $dice[$index];
            }
        }
        
        return self::isValidSelection($selectedDice);
    }

    public static function calculateSelectionScore(array $selectedDice): int
    {
        $score = 0;
        $counts = array_count_values($selectedDice);
        
        // Single 1s and 5s
        if (isset($counts[1])) {
            $score += $counts[1] * self::SCORING['single_1'];
        }
        if (isset($counts[5])) {
            $score += $counts[5] * self::SCORING['single_5'];
        }
        
        // Three of a kind
        foreach ($counts as $value => $count) {
            if ($count >= 3) {
                $score += self::SCORING["three_{$value}s"];
                $count -= 3; // Remove the three we just scored
                
                // Score remaining singles
                if ($value === 1) {
                    $score += $count * self::SCORING['single_1'];
                } elseif ($value === 5) {
                    $score += $count * self::SCORING['single_5'];
                }
            }
        }
        
        // Special combinations
        if (count($selectedDice) >= 4) {
            $combinations = self::findScoringCombinations($selectedDice);
            foreach ($combinations as $combo) {
                if (in_array($combo['type'], ['four_of_a_kind', 'five_of_a_kind', 'six_of_a_kind', 'straight', 'three_pairs', 'two_triplets'])) {
                    $score += self::SCORING[$combo['type']];
                    break; // Only count the highest scoring combination
                }
            }
        }
        
        return $score;
    }

    public static function canRollDice(array $state): bool
    {
        return !$state['gameOver'] && 
               $state['turnPhase'] === 'rolling' && 
               $state['gameStarted'];
    }

    public static function canSelectDice(array $state): bool
    {
        return !$state['gameOver'] && 
               $state['turnPhase'] === 'selecting' && 
               !empty($state['dice']);
    }

    public static function canBankPoints(array $state): bool
    {
        return !$state['gameOver'] && 
               $state['turnPhase'] === 'banking' && 
               $state['turnScore'] > 0;
    }

    public static function getScoringCombinations(array $state): array
    {
        if (empty($state['dice'])) {
            return [];
        }
        
        return self::findScoringCombinations($state['dice']);
    }

    public static function getHint(array $state): array
    {
        if ($state['turnPhase'] !== 'selecting') {
            return [
                'type' => 'roll',
                'message' => 'Roll the dice to start your turn!',
                'action' => 'roll_dice'
            ];
        }
        
        $combinations = self::getScoringCombinations($state);
        
        if (empty($combinations)) {
            return [
                'type' => 'farkle',
                'message' => 'No scoring combinations available. This roll will result in a Farkle!',
                'action' => 'bank_points'
            ];
        }
        
        // Find the best scoring combination
        $bestCombo = null;
        $bestScore = 0;
        
        foreach ($combinations as $combo) {
            $score = self::calculateSelectionScore($combo['dice']);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCombo = $combo;
            }
        }
        
        if ($bestCombo) {
            return [
                'type' => 'select',
                'message' => "Select the {$bestCombo['type']} combination for {$bestScore} points!",
                'combination' => $bestCombo,
                'score' => $bestScore
            ];
        }
        
        return [
            'type' => 'bank',
            'message' => 'Consider banking your points to avoid Farkling.',
            'action' => 'bank_points'
        ];
    }

    public static function createMoveSnapshot(array $state): array
    {
        return [
            'players' => $state['players'],
            'currentPlayer' => $state['currentPlayer'],
            'dice' => $state['dice'],
            'selectedDice' => $state['selectedDice'],
            'turnScore' => $state['turnScore'],
            'turnPhase' => $state['turnPhase'],
            'consecutiveRolls' => $state['consecutiveRolls'],
            'hotDice' => $state['hotDice']
        ];
    }

    public static function isGameOver(array $state): bool
    {
        return $state['gameOver'];
    }

    public static function calculateScore(array $state): int
    {
        $player = $state['players'][0]; // Human player
        return $player['score'];
    }

    public static function getGameState(array $state): array
    {
        return [
            'players' => $state['players'],
            'currentPlayer' => $state['currentPlayer'],
            'dice' => $state['dice'],
            'selectedDice' => $state['selectedDice'],
            'turnScore' => $state['turnScore'],
            'gameOver' => $state['gameOver'],
            'gamePhase' => $state['gamePhase'],
            'turnPhase' => $state['turnPhase'],
            'lastRoll' => $state['lastRoll'],
            'farkleCount' => $state['farkleCount'],
            'consecutiveRolls' => $state['consecutiveRolls'],
            'hotDice' => $state['hotDice'],
            'winner' => $state['winner']
        ];
    }

    public static function getDice(array $state): array
    {
        return $state['dice'];
    }

    public static function getSelectedDice(array $state): array
    {
        return $state['selectedDice'];
    }

    public static function getTurnScore(array $state): int
    {
        return $state['turnScore'];
    }

    public static function getCurrentPlayer(array $state): int
    {
        return $state['currentPlayer'];
    }

    public static function getPlayerScores(array $state): array
    {
        $scores = [];
        foreach ($state['players'] as $player) {
            $scores[] = $player['score'];
        }
        return $scores;
    }
}
