<?php

namespace App\Games\Mastermind;

/**
 * Mastermind Engine - Classic code-breaking puzzle game
 */
class MastermindEngine
{
    public const CODE_LENGTH = 4;
    public const MAX_GUESSES = 10;
    public const FEEDBACK_BLACK = 'black';
    public const FEEDBACK_WHITE = 'white';
    public const FEEDBACK_NONE = 'none';

    public const DIFFICULTIES = [
        'easy' => [
            'colors' => 6,
            'maxGuesses' => 10,
            'colors' => ['red', 'blue', 'green', 'yellow', 'orange', 'purple']
        ],
        'medium' => [
            'colors' => 8,
            'maxGuesses' => 8,
            'colors' => ['red', 'blue', 'green', 'yellow', 'orange', 'purple', 'pink', 'cyan']
        ],
        'hard' => [
            'colors' => 10,
            'maxGuesses' => 6,
            'colors' => ['red', 'blue', 'green', 'yellow', 'orange', 'purple', 'pink', 'cyan', 'brown', 'gray']
        ],
        'expert' => [
            'colors' => 12,
            'maxGuesses' => 5,
            'colors' => ['red', 'blue', 'green', 'yellow', 'orange', 'purple', 'pink', 'cyan', 'brown', 'gray', 'lime', 'navy']
        ]
    ];

    public static function newGame(string $difficulty = 'medium'): array
    {
        $config = self::DIFFICULTIES[$difficulty];
        $secretCode = self::generateSecretCode($config['colors']);
        
        return [
            'secretCode' => $secretCode,
            'difficulty' => $difficulty,
            'maxGuesses' => $config['maxGuesses'],
            'availableColors' => $config['colors'],
            'guesses' => [],
            'currentGuess' => [],
            'feedback' => [],
            'gameOver' => false,
            'gameWon' => false,
            'gameLost' => false,
            'gameStarted' => false,
            'currentAttempt' => 0,
            'gamePhase' => 'playing', // playing, game_over
            'guessPhase' => 'selecting', // selecting, submitting, analyzing
            'moveHistory' => [],
            'hintsUsed' => 0,
            'startTime' => null,
            'endTime' => null,
            'totalTime' => 0
        ];
    }

    public static function generateSecretCode(int $colorCount): array
    {
        $colors = array_slice(self::DIFFICULTIES['expert']['colors'], 0, $colorCount);
        $code = [];
        
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code[] = $colors[array_rand($colors)];
        }
        
        return $code;
    }

    public static function validateMove(array $state, array $move): bool
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'select_color':
                $position = $move['position'] ?? -1;
                $color = $move['color'] ?? '';
                return self::canSelectColor($state) && 
                       $position >= 0 && $position < self::CODE_LENGTH &&
                       in_array($color, $state['availableColors']);
            
            case 'submit_guess':
                return self::canSubmitGuess($state);
            
            case 'clear_guess':
                return self::canSelectColor($state);
            
            case 'start_game':
                return !$state['gameStarted'] && !$state['gameOver'];
            
            case 'new_game':
                $difficulty = $move['difficulty'] ?? 'medium';
                return in_array($difficulty, array_keys(self::DIFFICULTIES));
            
            case 'get_hint':
                return !$state['gameOver'] && $state['gameStarted'];
            
            default:
                return false;
        }
    }

    public static function applyMove(array $state, array $move): array
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'select_color':
                return self::selectColor($state, $move['position'], $move['color']);
            
            case 'submit_guess':
                return self::submitGuess($state);
            
            case 'clear_guess':
                return self::clearGuess($state);
            
            case 'start_game':
                $state['gameStarted'] = true;
                $state['startTime'] = microtime(true);
                return $state;
            
            case 'new_game':
                $difficulty = $move['difficulty'] ?? 'medium';
                return self::newGame($difficulty);
            
            case 'get_hint':
                return self::getHint($state);
            
            default:
                return $state;
        }
    }

    public static function selectColor(array $state, int $position, string $color): array
    {
        if (!self::canSelectColor($state)) {
            return $state;
        }

        $state['currentGuess'][$position] = $color;
        $state['guessPhase'] = 'selecting';
        
        return $state;
    }

    public static function submitGuess(array $state): array
    {
        if (!self::canSubmitGuess($state)) {
            return $state;
        }

        // Save state for undo
        $state['moveHistory'][] = self::createMoveSnapshot($state);
        
        $guess = $state['currentGuess'];
        $state['guesses'][] = $guess;
        $state['currentAttempt']++;
        
        // Calculate feedback
        $feedback = self::calculateFeedback($state['secretCode'], $guess);
        $state['feedback'][] = $feedback;
        
        // Check for win/lose conditions
        if ($feedback['black'] === self::CODE_LENGTH) {
            $state['gameOver'] = true;
            $state['gameWon'] = true;
            $state['gamePhase'] = 'game_over';
            $state['endTime'] = microtime(true);
            $state['totalTime'] = $state['endTime'] - $state['startTime'];
        } elseif ($state['currentAttempt'] >= $state['maxGuesses']) {
            $state['gameOver'] = true;
            $state['gameLost'] = true;
            $state['gamePhase'] = 'game_over';
            $state['endTime'] = microtime(true);
            $state['totalTime'] = $state['endTime'] - $state['startTime'];
        }
        
        // Reset current guess
        $state['currentGuess'] = [];
        $state['guessPhase'] = 'selecting';
        
        return $state;
    }

    public static function clearGuess(array $state): array
    {
        if (!self::canSelectColor($state)) {
            return $state;
        }

        $state['currentGuess'] = [];
        $state['guessPhase'] = 'selecting';
        
        return $state;
    }

    public static function calculateFeedback(array $secretCode, array $guess): array
    {
        $black = 0; // Correct color in correct position
        $white = 0; // Correct color in wrong position
        
        $secretCounts = [];
        $guessCounts = [];
        
        // Count exact matches (black pegs)
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            if ($secretCode[$i] === $guess[$i]) {
                $black++;
            } else {
                // Count remaining colors for white peg calculation
                $secretCounts[$secretCode[$i]] = ($secretCounts[$secretCode[$i]] ?? 0) + 1;
                $guessCounts[$guess[$i]] = ($guessCounts[$guess[$i]] ?? 0) + 1;
            }
        }
        
        // Count color matches in wrong positions (white pegs)
        foreach ($guessCounts as $color => $count) {
            if (isset($secretCounts[$color])) {
                $white += min($count, $secretCounts[$color]);
            }
        }
        
        return [
            'black' => $black,
            'white' => $white,
            'none' => self::CODE_LENGTH - $black - $white
        ];
    }

    public static function canSelectColor(array $state): bool
    {
        return !$state['gameOver'] && 
               $state['gameStarted'] && 
               $state['guessPhase'] === 'selecting' &&
               $state['currentAttempt'] < $state['maxGuesses'];
    }

    public static function canSubmitGuess(array $state): bool
    {
        return !$state['gameOver'] && 
               $state['gameStarted'] && 
               $state['guessPhase'] === 'selecting' &&
               count($state['currentGuess']) === self::CODE_LENGTH &&
               $state['currentAttempt'] < $state['maxGuesses'];
    }

    public static function canMakeGuess(array $state): bool
    {
        return self::canSelectColor($state) || self::canSubmitGuess($state);
    }

    public static function getHint(array $state): array
    {
        if ($state['gameOver'] || !$state['gameStarted']) {
            return [
                'type' => 'start',
                'message' => 'Start the game to begin guessing!',
                'action' => 'start_game'
            ];
        }

        if (empty($state['guesses'])) {
            return [
                'type' => 'first_guess',
                'message' => 'Try using 4 different colors for your first guess to gather information.',
                'suggestion' => array_slice($state['availableColors'], 0, 4)
            ];
        }

        $lastGuess = end($state['guesses']);
        $lastFeedback = end($state['feedback']);
        
        if ($lastFeedback['black'] > 0) {
            return [
                'type' => 'black_pegs',
                'message' => "You have {$lastFeedback['black']} correct colors in correct positions. Keep those colors in the same positions.",
                'strategy' => 'maintain_correct_positions'
            ];
        }
        
        if ($lastFeedback['white'] > 0) {
            return [
                'type' => 'white_pegs',
                'message' => "You have {$lastFeedback['white']} correct colors in wrong positions. Try rearranging these colors.",
                'strategy' => 'rearrange_colors'
            ];
        }
        
        if ($lastFeedback['none'] === self::CODE_LENGTH) {
            return [
                'type' => 'no_matches',
                'message' => 'None of these colors are in the secret code. Try completely different colors.',
                'strategy' => 'try_new_colors'
            ];
        }

        return [
            'type' => 'general',
            'message' => 'Use process of elimination. Try colors you haven\'t used yet.',
            'strategy' => 'elimination'
        ];
    }

    public static function createMoveSnapshot(array $state): array
    {
        return [
            'guesses' => $state['guesses'],
            'currentGuess' => $state['currentGuess'],
            'feedback' => $state['feedback'],
            'currentAttempt' => $state['currentAttempt'],
            'guessPhase' => $state['guessPhase']
        ];
    }

    public static function isGameOver(array $state): bool
    {
        return $state['gameOver'];
    }

    public static function isGameWon(array $state): bool
    {
        return $state['gameWon'];
    }

    public static function isGameLost(array $state): bool
    {
        return $state['gameLost'];
    }

    public static function calculateScore(array $state): int
    {
        if (!$state['gameWon']) {
            return 0;
        }

        $baseScore = 1000;
        $attemptBonus = ($state['maxGuesses'] - $state['currentAttempt']) * 100;
        $timeBonus = max(0, 300 - floor($state['totalTime']));
        $difficultyMultiplier = [
            'easy' => 1.0,
            'medium' => 1.5,
            'hard' => 2.0,
            'expert' => 3.0
        ][$state['difficulty']];

        return floor(($baseScore + $attemptBonus + $timeBonus) * $difficultyMultiplier);
    }

    public static function getGameState(array $state): array
    {
        return [
            'difficulty' => $state['difficulty'],
            'maxGuesses' => $state['maxGuesses'],
            'availableColors' => $state['availableColors'],
            'guesses' => $state['guesses'],
            'currentGuess' => $state['currentGuess'],
            'feedback' => $state['feedback'],
            'gameOver' => $state['gameOver'],
            'gameWon' => $state['gameWon'],
            'gameLost' => $state['gameLost'],
            'gamePhase' => $state['gamePhase'],
            'guessPhase' => $state['guessPhase'],
            'currentAttempt' => $state['currentAttempt'],
            'hintsUsed' => $state['hintsUsed'],
            'totalTime' => $state['totalTime']
        ];
    }

    public static function getSecretCode(array $state): array
    {
        return $state['secretCode'];
    }

    public static function getCurrentGuess(array $state): array
    {
        return $state['currentGuess'];
    }

    public static function getGuesses(array $state): array
    {
        return $state['guesses'];
    }

    public static function getFeedback(array $state): array
    {
        return $state['feedback'];
    }

    public static function getAvailableColors(array $state): array
    {
        return $state['availableColors'];
    }

    public static function getDifficulty(array $state): string
    {
        return $state['difficulty'];
    }

    public static function getRemainingGuesses(array $state): int
    {
        return max(0, $state['maxGuesses'] - $state['currentAttempt']);
    }
}
