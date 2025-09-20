<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('loads the 2048 page', function () {
    $response = get('/games/2048');
    $response->assertOk();
    $response->assertSee('2048');
});


