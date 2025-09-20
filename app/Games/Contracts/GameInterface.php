<?php

namespace App\Games\Contracts;

interface GameInterface
{
    public function id(): string;

    public function slug(): string;

    public function name(): string;

    public function description(): string;

    public function newGameState(): array;

    public function isOver(array $state): bool;

    public function applyMove(array $state, array $move): array;
}
