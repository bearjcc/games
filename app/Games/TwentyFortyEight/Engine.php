<?php

namespace App\Games\TwentyFortyEight;

final class Engine
{
    /**
     * Move the board in a direction and return [newBoard, scoreGained].
     * Board is 16-length array row-major.
     * @param array<int,int> $board
     * @return array{0:array<int,int>,1:int}
     */
    public function move(array $board, string $direction): array
    {
        $score = 0;
        $rows = array_chunk($board, 4);
        $rotate = function(array $g): array { // rotate clockwise
            return [
                [$g[3][0],$g[2][0],$g[1][0],$g[0][0]],
                [$g[3][1],$g[2][1],$g[1][1],$g[0][1]],
                [$g[3][2],$g[2][2],$g[1][2],$g[0][2]],
                [$g[3][3],$g[2][3],$g[1][3],$g[0][3]],
            ];
        };

        $grid = $rows;
        if ($direction === 'up') {
            $grid = $rotate($rotate($rotate($grid))); // 270 deg
        } elseif ($direction === 'right') {
            $grid = $rotate($rotate($grid));
        } elseif ($direction === 'down') {
            $grid = $rotate($grid);
        }

        // Now treat as moving left
        for ($r = 0; $r < 4; $r++) {
            $line = $grid[$r];
            $line = array_values(array_filter($line, fn($v) => $v !== 0));
            $merged = [];
            for ($i = 0; $i < count($line); $i++) {
                if ($i + 1 < count($line) && $line[$i] === $line[$i+1]) {
                    $val = $line[$i] * 2;
                    $score += $val;
                    $merged[] = $val;
                    $i++;
                } else {
                    $merged[] = $line[$i];
                }
            }
            while (count($merged) < 4) { $merged[] = 0; }
            $grid[$r] = $merged;
        }

        if ($direction === 'up') {
            $grid = $rotate($grid);
        } elseif ($direction === 'right') {
            $grid = $rotate($rotate($grid));
        } elseif ($direction === 'down') {
            $grid = $rotate($rotate($rotate($grid)));
        }

        return [array_merge(...$grid), $score];
    }

    /** Determine if any moves are possible. */
    public function canMove(array $board): bool
    {
        if (in_array(0, $board, true)) return true;
        $g = array_chunk($board, 4);
        for ($r = 0; $r < 4; $r++) {
            for ($c = 0; $c < 4; $c++) {
                $v = $g[$r][$c];
                if ($c+1 < 4 && $g[$r][$c+1] === $v) return true;
                if ($r+1 < 4 && $g[$r+1][$c] === $v) return true;
            }
        }
        return false;
    }
}


