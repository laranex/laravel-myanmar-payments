<?php

declare(strict_types=1);

arch('it will not use debugging functions')
    ->expect(['dd', 'ddd', 'dump', 'ray', 'var_dump', 'print_r', 'var_export', 'die', 'exit', 'eval'])
    ->each->not->toBeUsed();

arch('it will not use insecure or environment-reading functions')
    ->expect(['env', 'md5', 'sha1', 'uniqid', 'rand', 'mt_rand', 'str_shuffle', 'unserialize', 'extract', 'exec', 'shell_exec', 'system', 'passthru', 'assert'])
    ->each->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('Laranex\LaravelMyanmarPayments')
    ->toUseStrictTypes();
