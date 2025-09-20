<?php

namespace App\Games\Memory;

/**
 * Memory Match Engine - Card matching game logic
 */
class MemoryEngine
{
    public const DIFFICULTIES = [
        'easy' => ['size' => 4, 'pairs' => 8, 'label' => 'Easy'],
        'medium' => ['size' => 6, 'pairs' => 18, 'label' => 'Medium'],
        'hard' => ['size' => 8, 'pairs' => 32, 'label' => 'Hard'],
        'expert' => ['size' => 10, 'pairs' => 50, 'label' => 'Expert']
    ];

    public const CARD_TYPES = [
        'hearts', 'diamonds', 'clubs', 'spades',
        'ace', 'king', 'queen', 'jack',
        'ten', 'nine', 'eight', 'seven',
        'six', 'five', 'four', 'three', 'two'
    ];

    public const MATCH_SCORE = 10;
    public const TIME_BONUS_MULTIPLIER = 0.1;
    public const PERFECT_GAME_BONUS = 100;

    public static function newGame(string $difficulty = 'medium'): array
    {
        $config = self::DIFFICULTIES[$difficulty] ?? self::DIFFICULTIES['medium'];
        $boardSize = $config['size'];
        $totalPairs = $config['pairs'];
        
        $cards = self::generateCards($totalPairs);
        $board = self::createBoard($cards, $boardSize);
        
        return [
            'board' => $board,
            'flippedCards' => [],
            'matchedPairs' => [],
            'moves' => 0,
            'score' => 0,
            'gameComplete' => false,
            'gameStarted' => false,
            'difficulty' => $difficulty,
            'boardSize' => $boardSize,
            'totalPairs' => $totalPairs,
            'startTime' => null,
            'endTime' => null,
            'gameTime' => 0,
            'bestTime' => null,
            'bestMoves' => null,
            'perfectGame' => false
        ];
    }

    public static function generateCards(int $totalPairs): array
    {
        $cards = [];
        $availableTypes = array_slice(self::CARD_TYPES, 0, $totalPairs);
        
        // Create pairs
        foreach ($availableTypes as $type) {
            $cards[] = [
                'id' => $type,
                'type' => $type,
                'image' => "card{$type}.png",
                'matched' => false
            ];
            $cards[] = [
                'id' => $type,
                'type' => $type,
                'image' => "card{$type}.png",
                'matched' => false
            ];
        }
        
        return $cards;
    }

    public static function createBoard(array $cards, int $boardSize): array
    {
        shuffle($cards);
        $board = [];
        
        for ($i = 0; $i < $boardSize; $i++) {
            $row = [];
            for ($j = 0; $j < $boardSize; $j++) {
                $index = $i * $boardSize + $j;
                if ($index < count($cards)) {
                    $row[] = array_merge($cards[$index], [
                        'row' => $i,
                        'col' => $j,
                        'index' => $index,
                        'flipped' => false
                    ]);
                } else {
                    $row[] = null; // Empty slot for non-square grids
                }
            }
            $board[] = $row;
        }
        
        return $board;
    }

    public static function validateMove(array $state, array $move): bool
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'flip_card':
                $index = $move['index'] ?? -1;
                return $index >= 0 && 
                       $index < count($state['board']) * count($state['board'][0]) &&
                       self::canFlipCard($state, $index);
            
            case 'start_game':
                return !$state['gameStarted'] && !$state['gameComplete'];
            
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
            case 'flip_card':
                return self::flipCard($state, $move['index']);
            
            case 'start_game':
                $state['gameStarted'] = true;
                $state['startTime'] = time();
                return $state;
            
            case 'new_game':
                return self::newGame($state['difficulty'] ?? 'medium');
            
            default:
                return $state;
        }
    }

    public static function flipCard(array $state, int $index): array
    {
        if (!self::canFlipCard($state, $index)) {
            return $state;
        }
        
        $state['gameStarted'] = true;
        if (!$state['startTime']) {
            $state['startTime'] = time();
        }
        
        // Find the card
        $card = self::getCardByIndex($state['board'], $index);
        if (!$card) {
            return $state;
        }
        
        // Flip the card
        $state['board'][$card['row']][$card['col']]['flipped'] = true;
        $state['flippedCards'][] = $index;
        
        // Check for matches
        if (count($state['flippedCards']) === 2) {
            $state['moves']++;
            $state = self::checkForMatch($state);
        }
        
        return $state;
    }

    public static function checkForMatch(array $state): array
    {
        if (count($state['flippedCards']) !== 2) {
            return $state;
        }
        
        $index1 = $state['flippedCards'][0];
        $index2 = $state['flippedCards'][1];
        
        $card1 = self::getCardByIndex($state['board'], $index1);
        $card2 = self::getCardByIndex($state['board'], $index2);
        
        if ($card1 && $card2 && $card1['type'] === $card2['type']) {
            // Match found!
            $state['board'][$card1['row']][$card1['col']]['matched'] = true;
            $state['board'][$card2['row']][$card2['col']]['matched'] = true;
            $state['matchedPairs'][] = [$index1, $index2];
            $state['score'] += self::MATCH_SCORE;
            
            // Check if game is complete
            if (count($state['matchedPairs']) === $state['totalPairs']) {
                $state['gameComplete'] = true;
                $state['endTime'] = time();
                $state['gameTime'] = $state['endTime'] - $state['startTime'];
                $state = self::calculateFinalScore($state);
            }
        } else {
            // No match - flip cards back after delay
            // In a real implementation, you'd use a timer
            $state['board'][$card1['row']][$card1['col']]['flipped'] = false;
            $state['board'][$card2['row']][$card2['col']]['flipped'] = false;
        }
        
        $state['flippedCards'] = [];
        return $state;
    }

    public static function calculateFinalScore(array $state): array
    {
        $baseScore = $state['score'];
        
        // Time bonus
        $timeBonus = max(0, 300 - $state['gameTime']) * self::TIME_BONUS_MULTIPLIER;
        
        // Perfect game bonus (minimal moves)
        $minMoves = $state['totalPairs']; // Theoretical minimum
        if ($state['moves'] <= $minMoves + 2) {
            $state['perfectGame'] = true;
            $baseScore += self::PERFECT_GAME_BONUS;
        }
        
        $state['score'] = intval($baseScore + $timeBonus);
        
        // Update best scores
        if (!$state['bestTime'] || $state['gameTime'] < $state['bestTime']) {
            $state['bestTime'] = $state['gameTime'];
        }
        
        if (!$state['bestMoves'] || $state['moves'] < $state['bestMoves']) {
            $state['bestMoves'] = $state['moves'];
        }
        
        return $state;
    }

    public static function getCardByIndex(array $board, int $index): ?array
    {
        $boardSize = count($board);
        $row = intval($index / $boardSize);
        $col = $index % $boardSize;
        
        if ($row >= 0 && $row < $boardSize && $col >= 0 && $col < $boardSize) {
            return $board[$row][$col];
        }
        
        return null;
    }

    public static function canFlipCard(array $state, int $index): bool
    {
        if ($state['gameComplete']) {
            return false;
        }
        
        if (count($state['flippedCards']) >= 2) {
            return false;
        }
        
        if (in_array($index, $state['flippedCards'])) {
            return false;
        }
        
        $card = self::getCardByIndex($state['board'], $index);
        if (!$card || $card['matched'] || $card['flipped']) {
            return false;
        }
        
        return true;
    }

    public static function isGameComplete(array $state): bool
    {
        return $state['gameComplete'];
    }

    public static function calculateScore(array $state): int
    {
        return $state['score'];
    }

    public static function getGameState(array $state): array
    {
        return [
            'board' => $state['board'],
            'flippedCards' => $state['flippedCards'],
            'matchedPairs' => $state['matchedPairs'],
            'moves' => $state['moves'],
            'score' => $state['score'],
            'gameComplete' => $state['gameComplete'],
            'gameStarted' => $state['gameStarted'],
            'difficulty' => $state['difficulty'],
            'boardSize' => $state['boardSize'],
            'totalPairs' => $state['totalPairs'],
            'gameTime' => $state['gameTime'],
            'perfectGame' => $state['perfectGame']
        ];
    }

    public static function getBoardSize(array $state): array
    {
        return [
            'rows' => $state['boardSize'],
            'cols' => $state['boardSize']
        ];
    }

    public static function getFlippedCards(array $state): array
    {
        return $state['flippedCards'];
    }

    public static function getMatchedPairs(array $state): array
    {
        return $state['matchedPairs'];
    }

    public static function getCardImagePath(string $cardImage): string
    {
        return "/images/Cards/{$cardImage}";
    }

    public static function getCardBackImage(): string
    {
        return "/images/Cards/cardBack_blue1.png";
    }
}
