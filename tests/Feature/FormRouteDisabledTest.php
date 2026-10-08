<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments;
use Laranex\LaravelMyanmarPayments\Tests\TestCase;
use Laranex\PhpMyanmarPayments\AyaPay\AyaPayMethod;
use Laranex\PhpMyanmarPayments\AyaPay\AyaPayPaymentData;

class FormRouteDisabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('myanmar-payments.form_route', ['enabled' => false, 'path' => 'pay/form', 'middleware' => ['api'], 'ttl_minutes' => 30]);
    }

    public function test_the_form_route_and_auto_submit_links_are_off_when_disabled(): void
    {
        $payment = MyanmarPayments::ayaPay()->initiate(new AyaPayPaymentData('ORDER123', 1000, 'aya_pay', AyaPayMethod::Qr));

        $this->assertNull(Route::getRoutes()->getByName('myanmar-payments.form'));
        $this->assertNull($payment->autoSubmitUrl);
        $this->assertSame('https://uat-pgw.ayainnovation.com/v1/payment/request', $payment->action);
        $this->get('/pay/form?payload=x')->assertNotFound();
    }
}
