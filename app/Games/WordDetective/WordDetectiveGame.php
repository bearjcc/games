<?php

namespace App\Games\WordDetective;

use App\Games\Contracts\GameInterface;

class WordDetectiveGame implements GameInterface
{
    public function id(): string
    {
        return 'word-detective';
    }

    public function name(): string
    {
        return 'Word Detective';
    }

    public function slug(): string
    {
        return 'word-detective';
    }

    public function description(): string
    {
        return 'Solve mysteries by guessing letters! Uncover clues and detective tools as you work to reveal the hidden word.';
    }

    public function rules(): array
    {
        return [
            'Objective' => [
                'Guess letters to reveal the mystery word',
                'Use detective skills to solve the case',
                'Avoid too many wrong guesses or the case goes cold'
            ],
            'Gameplay' => [
                'Click letters or type on keyboard to guess',
                'Correct guesses reveal letters and unlock detective tools',
                'Wrong guesses add red herrings (false clues)',
                'Use the hint button for help when stuck',
                'Solve the mystery before running out of chances!'
            ],
            'Features' => [
                '5 difficulty levels: Rookie to Superintendent',
                'Progressive clue revelation with detective tools',
                'Category-based word lists for each difficulty',
                'Smart hint system for when you need help',
                'Positive, mystery-solving theme',
                'Beautiful detective-themed interface'
            ]
        ];
    }

    public function minPlayers(): int
    {
        return 1;
    }

    public function maxPlayers(): int
    {
        return 1;
    }

    public function estimatedDuration(): string
    {
        return '2-10 minutes';
    }

    public function difficulty(): string
    {
        return 'Medium';
    }

    public function tags(): array
    {
        return ['word', 'puzzle', 'mystery', 'single-player', 'vocabulary'];
    }

    public function initialState(): array
    {
        return WordDetectiveEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return WordDetectiveEngine::isGameComplete($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return WordDetectiveEngine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return WordDetectiveEngine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return WordDetectiveEngine::calculateScore($state);
    }

    public function getHint(array $state): ?array
    {
        return WordDetectiveEngine::getHint($state);
    }

    public function getBoardState(array $state): array
    {
        return WordDetectiveEngine::getBoardState($state);
    }

    public function canUseHint(array $state): bool
    {
        return WordDetectiveEngine::canUseHint($state);
    }

    public function getConflicts(array $state): array
    {
        // Word Detective doesn't have conflicts in the traditional sense
        // Return wrong guesses as "conflicts" for UI purposes
        return array_slice($state['guessedLetters'], 0, $state['wrongGuesses']);
    }

    public function generatePuzzle(string $difficulty = 'detective'): array
    {
        return WordDetectiveEngine::newGame($difficulty);
    }

    public function loadCustomPuzzle(array $puzzle): array
    {
        // For Word Detective, custom puzzles would be custom words
        // This could be extended to allow custom word lists
        throw new \BadMethodCallException('Custom puzzles not implemented for Word Detective');
    }

    public function autoSolve(array $state): array
    {
        // Auto-solve by revealing all letters
        $solvedState = $state;
        $solvedState['displayWord'] = $state['word'];
        $solvedState['gameComplete'] = true;
        $solvedState['gameWon'] = true;
        $solvedState['gameStarted'] = true;
        
        return $solvedState;
    }

    public function solveStep(array $state): ?array
    {
        // Solve one letter at a time
        $word = $state['word'];
        $displayWord = $state['displayWord'];
        
        // Find next unrevealed letter
        for ($i = 0; $i < strlen($word); $i++) {
            if ($displayWord[$i] === '_') {
                $letter = $word[$i];
                return WordDetectiveEngine::guessLetter($state, $letter);
            }
        }
        
        return null; // Already solved
    }

    public function canAutoSolve(array $state): bool
    {
        return !$state['gameComplete'] && strpos($state['displayWord'], '_') !== false;
    }

    public function getPuzzleForPrinting(array $state): array
    {
        return [
            'mysteryTitle' => $state['mysteryTitle'],
            'category' => $state['category'],
            'difficulty' => $state['difficulty'],
            'displayWord' => $state['displayWord'],
            'guessedLetters' => $state['guessedLetters'],
            'timestamp' => now()->format('Y-m-d H:i:s')
        ];
    }
}
