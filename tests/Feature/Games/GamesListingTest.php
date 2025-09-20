<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('shows games listing page', function () {
    get('/games')
        ->assertOk()
        ->assertSee('Games');
});

