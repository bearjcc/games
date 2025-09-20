<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\UserBestScoreService;

it('updates only when score improves', function () {
    $user = User::factory()->create();
    $svc = new UserBestScoreService();
    expect($svc->get($user, '2048'))->toBe(0);
    $svc->updateIfBetter($user, '2048', 100);
    expect($svc->get($user, '2048'))->toBe(100);
    $svc->updateIfBetter($user, '2048', 50);
    expect($svc->get($user, '2048'))->toBe(100);
});


