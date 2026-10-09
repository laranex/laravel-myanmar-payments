<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments;
use Laranex\LaravelMyanmarPayments\Tests\TestCase;
use Laranex\PhpMyanmarPayments\AyaPay\AyaPayMethod;
use Laranex\PhpMyanmarPayments\AyaPay\AyaPayPaymentData;

class FormRouteCustomTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('myanmar-payments.form_route', ['enabled' => true, 'path' => 'checkout/pay', 'middleware' => ['web', 'throttle:60,1'], 'ttl_minutes' => 30]);
    }

    public function test_the_form_route_uses_the_configured_path_and_middleware(): void
    {
        $route = Route::getRoutes()->getByName('myanmar-payments.form');
        $payment = MyanmarPayments::ayaPay()->initiate(new AyaPayPaymentData('ORDER123', 1000, 'aya_pay', AyaPayMethod::Qr));

        $this->assertNotNull($route);
        $this->assertSame('checkout/pay', $route->uri());
        $this->assertSame(['web', 'throttle:60,1'], $route->middleware());
        $this->assertStringStartsWith('http://localhost/checkout/pay?payload=', (string) $payment->autoSubmitUrl);
        $this->get((string) $payment->autoSubmitUrl)->assertOk();
    }
}
