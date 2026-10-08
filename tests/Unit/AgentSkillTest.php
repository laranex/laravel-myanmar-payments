<?php

declare(strict_types=1);

it('ships the same agent skill to Laravel Boost and to npx skills', function (): void {
    $root = dirname(__DIR__, 2);

    expect($root.'/skills/laravel-myanmar-payments/SKILL.md')->toBeFile()
        ->and(file_get_contents($root.'/skills/laravel-myanmar-payments/SKILL.md'))
        ->toBe(file_get_contents($root.'/resources/boost/skills/laravel-myanmar-payments/SKILL.md'));
});
