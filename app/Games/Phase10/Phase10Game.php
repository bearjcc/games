<?php

namespace App\Games\Phase10;

use App\Games\Contracts\GameInterface;

class Phase10Game implements GameInterface
{
    public function id(): string
    {
        return 'phase10';
    }

    public function name(): string
    {
        return 'Phase 10';
    }

    public function slug(): string
    {
        return 'phase10';
    }

    public function description(): string
    {
        return 'Classic rummy-style card game! Complete 10 phases by making sets and runs, then be the first to go out to win!';
    }

    public function rules(): array
    {
        return [
            'Objective' => [
                'Complete all 10 phases in order',
                'Be the first player to go out (empty hand)',
                'Score points based on cards left in opponents\' hands',
                'Lowest total score after all phases wins'
            ],
            'Setup' => [
                'Each player starts with 10 cards',
                'Remaining cards form the draw pile',
                'Top card is turned face up as discard pile',
                'Players take turns drawing and discarding'
            ],
            'Gameplay' => [
                'Draw a card from draw pile or discard pile',
                'Try to complete your current phase',
                'Discard one card to end your turn',
                'Complete phase by laying down required sets/runs',
                'Go out by playing all cards in hand'
            ],
            'Phase Requirements' => [
                'Phase 1: 2 sets of 3',
                'Phase 2: 1 set of 3 + 1 run of 4',
                'Phase 3: 1 set of 4 + 1 run of 4',
                'Phase 4: 1 run of 7',
                'Phase 5: 1 run of 8',
                'Phase 6: 1 run of 9',
                'Phase 7: 2 sets of 4',
                'Phase 8: 7 cards of one color',
                'Phase 9: 1 set of 5 + 1 set of 2',
                'Phase 10: 1 set of 5 + 1 set of 3'
            ],
            'Card Values' => [
                'Number cards: Face value (1-12)',
                'Skip cards: 15 points',
                'Wild cards: 25 points',
                'Skip cards skip your next turn',
                'Wild cards can substitute any card'
            ],
            'Scoring' => [
                'Winner gets 0 points',
                'Losers get points equal to cards left in hand',
                'Skip cards: 15 points each',
                'Wild cards: 25 points each',
                'Lowest total score after all phases wins'
            ],
            'Strategy' => [
                'Focus on completing your current phase',
                'Use wild cards strategically',
                'Watch opponents\' progress',
                'Discard high-value cards when possible',
                'Plan ahead for future phases'
            ]
        ];
    }

    public function minPlayers(): int
    {
        return 2;
    }

    public function maxPlayers(): int
    {
        return 6;
    }

    public function estimatedDuration(): string
    {
        return '30-60 minutes';
    }

    public function difficulty(): string
    {
        return 'Medium';
    }

    public function tags(): array
    {
        return ['cards', 'rummy', 'sets', 'runs', 'strategy'];
    }

    public function initialState(): array
    {
        return Phase10Engine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return Phase10Engine::isGameOver($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return Phase10Engine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return Phase10Engine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return Phase10Engine::calculateScore($state);
    }

    public function getGameState(array $state): array
    {
        return Phase10Engine::getGameState($state);
    }

    public function getCurrentPlayer(array $state): int
    {
        return Phase10Engine::getCurrentPlayer($state);
    }

    public function getPlayerHands(array $state): array
    {
        return Phase10Engine::getPlayerHands($state);
    }

    public function getDiscardPile(array $state): array
    {
        return Phase10Engine::getDiscardPile($state);
    }

    public function getDrawPile(array $state): array
    {
        return Phase10Engine::getDrawPile($state);
    }

    public function getCurrentPhase(array $state): int
    {
        return Phase10Engine::getCurrentPhase($state);
    }

    public function getPhaseRequirements(array $state): array
    {
        return Phase10Engine::getPhaseRequirements($state);
    }

    public function getPlayerScores(array $state): array
    {
        return Phase10Engine::getPlayerScores($state);
    }

    public function getPlayerPhases(array $state): array
    {
        return Phase10Engine::getPlayerPhases($state);
    }

    public function canDrawCard(array $state): bool
    {
        return Phase10Engine::canDrawCard($state);
    }

    public function canDiscardCard(array $state): bool
    {
        return Phase10Engine::canDiscardCard($state);
    }

    public function canPlayPhase(array $state): bool
    {
        return Phase10Engine::canPlayPhase($state);
    }

    public function canGoOut(array $state): bool
    {
        return Phase10Engine::canGoOut($state);
    }

    public function getHint(array $state): array
    {
        return Phase10Engine::getHint($state);
    }
}
