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

// Helpers defined only by laravel/framework (Illuminate/Foundation/helpers.php); the
// package requires standalone illuminate/* components, so it must not call them.
arch('it only calls helpers that the illuminate/* components define')
    ->expect('Laranex\LaravelMyanmarPayments')
    ->not->toUse([
        '__', 'abort', 'abort_if', 'abort_unless', 'action', 'app', 'app_path', 'asset', 'auth',
        'back', 'base_path', 'bcrypt', 'broadcast', 'broadcast_if', 'broadcast_unless', 'cache',
        'config', 'config_path', 'context', 'cookie', 'csrf_field', 'csrf_token', 'database_path',
        'decrypt', 'defer', 'dispatch', 'dispatch_sync', 'encrypt', 'event', 'fake', 'info',
        'lang_path', 'logger', 'logs', 'method_field', 'mix', 'now', 'old', 'policy',
        'precognitive', 'public_path', 'redirect', 'report', 'report_if', 'report_unless',
        'request', 'rescue', 'resolve', 'resource_path', 'response', 'route', 'secure_asset',
        'secure_url', 'session', 'storage_path', 'to_action', 'to_route', 'today', 'trans',
        'trans_choice', 'uri', 'url', 'validator', 'view',
    ]);
