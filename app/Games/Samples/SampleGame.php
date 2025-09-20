<?php

namespace App\Games\Samples;

use App\Games\Contracts\GameInterface;

class SampleGame implements GameInterface
{
    public function id(): string { return 'sample'; }
    public function slug(): string { return 'sample'; }
    public function name(): string { return 'Sample'; }
    public function description(): string { return 'A placeholder game used to validate scaffolding.'; }

    public function newGameState(): array
    {
        return ['counter' => 0];
    }

    public function isOver(array $state): bool
    {
        return $state['counter'] >= 3;
    }

    public function applyMove(array $state, array $move): array
    {
        if (($move['type'] ?? null) === 'increment') {
            $state['counter']++;
        }
        return $state;
    }
}


