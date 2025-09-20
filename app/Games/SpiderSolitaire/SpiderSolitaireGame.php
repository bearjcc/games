<?php

namespace App\Games\SpiderSolitaire;

use App\Games\Contracts\GameInterface;

class SpiderSolitaireGame implements GameInterface
{
    public function id(): string
    {
        return 'spider-solitaire';
    }

    public function name(): string
    {
        return 'Spider Solitaire';
    }

    public function slug(): string
    {
        return 'spider-solitaire';
    }

    public function description(): string
    {
        return 'Classic single-player card game! Build sequences in descending order within the same suit. Complete a full suit to remove it from the board.';
    }

    public function rules(): array
    {
        return [
            'Objective' => [
                'Build sequences in descending order (King to Ace)',
                'Sequences must be within the same suit',
                'Complete a full suit (King to Ace) to remove it',
                'Goal: Remove all 8 suits from the board'
            ],
            'Setup' => [
                '54 cards dealt into 10 tableau columns',
                'First 4 columns have 6 cards each',
                'Last 6 columns have 5 cards each',
                'Top card of each column is face up',
                'Remaining cards form the stock pile'
            ],
            'Gameplay' => [
                'Move cards or sequences to build descending sequences',
                'Only Kings can be placed on empty columns',
                'Deal 10 cards from stock when no moves are possible',
                'Cards dealt face up to each tableau column',
                'Use undo to reverse moves'
            ],
            'Scoring' => [
                'Complete suit removal: 100 points',
                'Move penalty: -1 point per move',
                'Time bonus: Based on completion speed',
                'Perfect game: All 8 suits removed'
            ],
            'Strategy' => [
                'Expose face-down cards when possible',
                'Build sequences in suits you have more cards of',
                'Keep empty columns for Kings',
                'Plan ahead for suit completion'
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
        return '10-30 minutes';
    }

    public function difficulty(): string
    {
        return 'Medium';
    }

    public function tags(): array
    {
        return ['cards', 'solitaire', 'single-player', 'strategy', 'classic'];
    }

    public function initialState(): array
    {
        return SpiderSolitaireEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return SpiderSolitaireEngine::isGameOver($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return SpiderSolitaireEngine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return SpiderSolitaireEngine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return SpiderSolitaireEngine::calculateScore($state);
    }

    public function getGameState(array $state): array
    {
        return SpiderSolitaireEngine::getGameState($state);
    }

    public function getTableau(array $state): array
    {
        return SpiderSolitaireEngine::getTableau($state);
    }

    public function getStock(array $state): array
    {
        return SpiderSolitaireEngine::getStock($state);
    }

    public function getCompletedSuits(array $state): array
    {
        return SpiderSolitaireEngine::getCompletedSuits($state);
    }

    public function canDealCards(array $state): bool
    {
        return SpiderSolitaireEngine::canDealCards($state);
    }

    public function dealCards(array $state): array
    {
        return SpiderSolitaireEngine::dealCards($state);
    }

    public function getPossibleMoves(array $state): array
    {
        return SpiderSolitaireEngine::getPossibleMoves($state);
    }

    public function getHint(array $state): array
    {
        return SpiderSolitaireEngine::getHint($state);
    }

    public function undo(array $state): array
    {
        return SpiderSolitaireEngine::undo($state);
    }

    public function canUndo(array $state): bool
    {
        return SpiderSolitaireEngine::canUndo($state);
    }
}
