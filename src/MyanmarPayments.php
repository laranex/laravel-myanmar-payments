<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments;

use Laranex\LaravelMyanmarPayments\Gateways\AyaPay;
use Laranex\LaravelMyanmarPayments\Gateways\CyberSource;
use Laranex\LaravelMyanmarPayments\Gateways\KbzPay;
use Laranex\LaravelMyanmarPayments\Gateways\WaveMoney;
use Laranex\LaravelMyanmarPayments\Gateways\YomaMmqr;
use Laranex\LaravelMyanmarPayments\Http\CallbackResponse;
use Laranex\LaravelMyanmarPayments\Http\FormPaymentUrl;
use Laranex\PhpMyanmarPayments\AyaPay\AyaPayConfig;
use Laranex\PhpMyanmarPayments\CyberSource\CyberSourceConfig;
use Laranex\PhpMyanmarPayments\KbzPay\KbzPayConfig;
use Laranex\PhpMyanmarPayments\Results\PaymentCallback;
use Laranex\PhpMyanmarPayments\WaveMoney\WaveMoneyConfig;
use Laranex\PhpMyanmarPayments\YomaMmqr\YomaMmqrConfig;
use Psr\Http\Client\ClientInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Entry point to every gateway, configured from `config/myanmar-payments.php`. Gateways are built on first use.
 */
class MyanmarPayments
{
    private ?KbzPay $kbzPay = null;

    private ?WaveMoney $waveMoney = null;

    private ?AyaPay $ayaPay = null;

    private ?YomaMmqr $yomaMmqr = null;

    private ?CyberSource $cyberSource = null;

    /**
     * @param  array<string, mixed>  $config  The `myanmar-payments` config.
     */
    public function __construct(
        private readonly array $config,
        private readonly ClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly FormPaymentUrl $formPaymentUrl,
    ) {}

    /**
     * KBZ Pay: `pwa()`, `qr()`, `app()`, `status()` and `handleCallback()`.
     */
    public function kbzPay(): KbzPay
    {
        return $this->kbzPay ??= new KbzPay(KbzPayConfig::fromArray($this->configFor('kbz_pay')), $this->httpClient);
    }

    /**
     * Wave Money: `initiate()` and `handleCallback()`.
     */
    public function waveMoney(): WaveMoney
    {
        return $this->waveMoney ??= new WaveMoney(WaveMoneyConfig::fromArray($this->configFor('wave_money')), $this->httpClient);
    }

    /**
     * AYA Payment Gateway: `services()`, `initiate()`, `status()`, `handleCallback()` and `verifyRedirect()`.
     */
    public function ayaPay(): AyaPay
    {
        return $this->ayaPay ??= new AyaPay(AyaPayConfig::fromArray($this->configFor('aya_pay')), $this->httpClient, $this->formPaymentUrl);
    }

    /**
     * Yoma MMQR: `initiate()`, `renewQr()`, `status()` and `handleCallback()`.
     */
    public function yomaMmqr(): YomaMmqr
    {
        return $this->yomaMmqr ??= new YomaMmqr(YomaMmqrConfig::fromArray($this->configFor('yoma_mmqr')), $this->httpClient, $this->cache);
    }

    /**
     * CyberSource Secure Acceptance: `initiate()` and `handleCallback()`.
     */
    public function cyberSource(): CyberSource
    {
        return $this->cyberSource ??= new CyberSource(CyberSourceConfig::fromArray($this->configFor('cyber_source')), $this->formPaymentUrl);
    }

    /**
     * The response that tells the gateway the callback was received, so it stops retrying.
     */
    public function acknowledge(PaymentCallback $callback): CallbackResponse
    {
        return new CallbackResponse($callback);
    }

    /**
     * @return array<string, mixed>
     */
    private function configFor(string $gateway): array
    {
        $config = $this->config[$gateway] ?? [];

        return is_array($config) ? $config : [];
    }
}
