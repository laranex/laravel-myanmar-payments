<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Encryption\StringEncrypter;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use Laranex\LaravelMyanmarPayments\Http\FormPaymentUrl;
use Laranex\LaravelMyanmarPayments\Http\LaravelHttpClient;

class MyanmarPaymentsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/myanmar-payments.php', 'myanmar-payments');

        $this->app->singleton(FormPaymentUrl::class, fn (Container $app): FormPaymentUrl => new FormPaymentUrl(
            $app->make(StringEncrypter::class),
            $app->make(UrlGenerator::class),
            (int) $this->config($app, 'form_route.ttl_minutes', 30),
            (bool) $this->config($app, 'form_route.enabled', true),
        ));

        $this->app->singleton(MyanmarPayments::class, function (Container $app): MyanmarPayments {
            $config = $this->config($app, null, []);
            $cacheStore = $this->config($app, 'cache_store');

            return new MyanmarPayments(
                config: is_array($config) ? $config : [],
                httpClient: new LaravelHttpClient($app->make(HttpFactory::class), (int) $this->config($app, 'http.timeout', 30)),
                cache: $app->make(CacheFactory::class)->store(is_string($cacheStore) ? $cacheStore : null),
                formPaymentUrl: $app->make(FormPaymentUrl::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/myanmar-payments.php');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/myanmar-payments.php' => $this->app->configPath('myanmar-payments.php'),
        ], ['myanmar-payments', 'myanmar-payments-config']);
    }

    private function config(Container $app, ?string $key, mixed $default = null): mixed
    {
        return $app->make(ConfigRepository::class)->get('myanmar-payments'.($key === null ? '' : '.'.$key), $default);
    }
}
