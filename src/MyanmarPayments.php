<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Laranex\LaravelMyanmarPayments\Gateways\AyaPay;
use Laranex\LaravelMyanmarPayments\Gateways\CyberSource;
use Laranex\LaravelMyanmarPayments\Gateways\KbzPay;
use Laranex\LaravelMyanmarPayments\Gateways\WaveMoney;
use Laranex\LaravelMyanmarPayments\Gateways\YomaMmqr;
use Laranex\LaravelMyanmarPayments\Http\CallbackResponse;
use Laranex\LaravelMyanmarPayments\Http\FormPaymentUrl;
use Laranex\LaravelMyanmarPayments\Http\LaravelHttpClient;
use Laranex\PhpMyanmarPayments\AyaPay\AyaPayConfig;
use Laranex\PhpMyanmarPayments\CyberSource\CyberSourceConfig;
use Laranex\PhpMyanmarPayments\Http\CallbackRequest;
use Laranex\PhpMyanmarPayments\KbzPay\KbzPayConfig;
use Laranex\PhpMyanmarPayments\Results\PaymentCallback;
use Laranex\PhpMyanmarPayments\WaveMoney\WaveMoneyConfig;
use Laranex\PhpMyanmarPayments\YomaMmqr\YomaMmqrConfig;
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
     * @param  HttpFactory  $http  Laravel's HTTP client, so `Http::fake()` and request events see gateway calls.
     */
    public function __construct(
        private readonly array $config,
        private readonly HttpFactory $http,
        private readonly CacheInterface $cache,
        private readonly FormPaymentUrl $formPaymentUrl,
    ) {}

    /**
     * KBZ Pay: `pwa()`, `qr()`, `app()`, `status()` and `handleCallback()`.
     */
    public function kbzPay(): KbzPay
    {
        if ($this->kbzPay === null) {
            $config = KbzPayConfig::fromArray($this->apiConfigFor('kbz_pay'));
            $this->kbzPay = new KbzPay($config, $this->httpClient($config->timeoutSeconds));
        }

        return $this->kbzPay;
    }

    /**
     * Wave Money: `initiate()` and `handleCallback()`.
     */
    public function waveMoney(): WaveMoney
    {
        if ($this->waveMoney === null) {
            $config = WaveMoneyConfig::fromArray($this->apiConfigFor('wave_money'));
            $this->waveMoney = new WaveMoney($config, $this->httpClient($config->timeoutSeconds));
        }

        return $this->waveMoney;
    }

    /**
     * AYA Payment Gateway: `services()`, `initiate()`, `status()`, `handleCallback()` and `verifyRedirect()`.
     */
    public function ayaPay(): AyaPay
    {
        if ($this->ayaPay === null) {
            $config = AyaPayConfig::fromArray($this->apiConfigFor('aya_pay'));
            $this->ayaPay = new AyaPay($config, $this->httpClient($config->timeoutSeconds), $this->formPaymentUrl);
        }

        return $this->ayaPay;
    }

    /**
     * Yoma MMQR: `initiate()`, `renewQr()`, `status()` and `handleCallback()`.
     */
    public function yomaMmqr(): YomaMmqr
    {
        if ($this->yomaMmqr === null) {
            $config = YomaMmqrConfig::fromArray($this->apiConfigFor('yoma_mmqr'));
            $this->yomaMmqr = new YomaMmqr($config, $this->httpClient($config->timeoutSeconds), $this->cache);
        }

        return $this->yomaMmqr;
    }

    /**
     * CyberSource Secure Acceptance: `initiate()` and `handleCallback()`.
     */
    public function cyberSource(): CyberSource
    {
        return $this->cyberSource ??= new CyberSource(CyberSourceConfig::fromArray($this->configFor('cyber_source')), $this->formPaymentUrl);
    }

    /**
     * The gateway names {@see gateway()} and {@see handleCallback()} accept, e.g. in a callback route.
     *
     * @return list<string>
     */
    public function gateways(): array
    {
        return ['kbz-pay', 'wave-money', 'aya-pay', 'yoma-mmqr', 'cyber-source'];
    }

    /**
     * The gateway for a name in {@see gateways()}, such as `kbz-pay`.
     *
     * @throws InvalidArgumentException for an unknown name
     */
    public function gateway(string $name): KbzPay|WaveMoney|AyaPay|YomaMmqr|CyberSource
    {
        return match ($name) {
            'kbz-pay' => $this->kbzPay(),
            'wave-money' => $this->waveMoney(),
            'aya-pay' => $this->ayaPay(),
            'yoma-mmqr' => $this->yomaMmqr(),
            'cyber-source' => $this->cyberSource(),
            default => throw new InvalidArgumentException(sprintf(
                'Unknown payment gateway [%s]; use one of %s.',
                $name,
                implode(', ', $this->gateways()),
            )),
        };
    }

    /**
     * Verify a callback with the named gateway, e.g. in one route that serves every gateway.
     *
     * @throws InvalidArgumentException for an unknown name
     */
    public function handleCallback(string $gateway, CallbackRequest|Request $request): PaymentCallback
    {
        return $this->gateway($gateway)->handleCallback($request);
    }

    /**
     * The response that tells the gateway the callback was received, so it stops retrying.
     * Without a callback it is an empty `200`.
     */
    public function acknowledge(?PaymentCallback $callback = null): CallbackResponse
    {
        return new CallbackResponse($callback);
    }

    /**
     * A gateway's config with the shared `http.timeout` as its `timeout_in_seconds`, unless the gateway sets its own.
     *
     * @return array<string, mixed>
     */
    private function apiConfigFor(string $gateway): array
    {
        $config = $this->configFor($gateway);
        $http = $this->config['http'] ?? [];
        $config['timeout_in_seconds'] ??= is_array($http) ? ($http['timeout'] ?? null) : null;

        return $config;
    }

    private function httpClient(int $timeoutSeconds): LaravelHttpClient
    {
        return new LaravelHttpClient($this->http, $timeoutSeconds);
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
