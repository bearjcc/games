<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('shows a specific game page', function () {
    get('/games/sample')
        ->assertOk()
        ->assertSee('Sample');
});

