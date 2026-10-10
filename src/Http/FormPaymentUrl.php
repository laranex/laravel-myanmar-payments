<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Http;

use Illuminate\Contracts\Encryption\StringEncrypter;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Carbon;
use Laranex\PhpMyanmarPayments\Exceptions\ConfigurationException;
use Laranex\PhpMyanmarPayments\Results\FormPayment;
use Throwable;

/**
 * Builds and reads the encrypted link to the auto-submitting payment form.
 */
class FormPaymentUrl
{
    /**
     * @param  mixed  $ttlMinutes  `form_route.ttl_minutes`: a whole number greater than 0, checked when a link is built.
     */
    public function __construct(
        private readonly StringEncrypter $encrypter,
        private readonly UrlGenerator $url,
        private readonly mixed $ttlMinutes,
        private readonly bool $enabled = true,
    ) {}

    /**
     * The payment with its `autoSubmitUrl`, or unchanged when the form route is disabled.
     *
     * @throws ConfigurationException When `form_route.ttl_minutes` is missing or not a whole number greater than 0.
     */
    public function attach(FormPayment $payment): FormPayment
    {
        if (! $this->enabled) {
            return $payment;
        }

        $payload = $this->encrypter->encryptString((string) json_encode([
            'orderId' => $payment->orderId,
            'action' => $payment->action,
            'fields' => $payment->fields,
            'enctype' => $payment->enctype,
            'expiresAt' => Carbon::now()->addMinutes($this->ttlMinutes())->getTimestamp(),
        ]));

        return $payment->withAutoSubmitUrl($this->url->route('myanmar-payments.form', ['payload' => $payload]));
    }

    /**
     * The form behind a link, or null when the link is invalid or expired.
     */
    public function resolve(string $payload): ?FormPayment
    {
        try {
            $data = json_decode($this->encrypter->decryptString($payload), true);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($data) || ! is_int($data['expiresAt'] ?? null) || $data['expiresAt'] < Carbon::now()->getTimestamp() || ! is_array($data['fields'] ?? null)) {
            return null;
        }

        /** @var array<string, string> $fields */
        $fields = $data['fields'];

        return new FormPayment(
            orderId: (string) ($data['orderId'] ?? ''),
            action: (string) ($data['action'] ?? ''),
            fields: $fields,
            enctype: (string) ($data['enctype'] ?? 'application/x-www-form-urlencoded'),
        );
    }

    /**
     * `form_route.ttl_minutes` as a whole number greater than 0, given as an integer or integer text.
     *
     * @throws ConfigurationException When it is missing, blank or not a whole number greater than 0.
     */
    private function ttlMinutes(): int
    {
        $value = $this->ttlMinutes;

        if ($value === null || (is_string($value) && trim($value) === '')) {
            throw ConfigurationException::missing('form_route', 'ttl_minutes');
        }

        if (is_string($value) && preg_match('/^[+-]?[0-9]+\z/', trim($value)) === 1) {
            $value = (int) trim($value);
        }

        if (! is_int($value) || $value <= 0) {
            throw ConfigurationException::invalid('form_route', 'ttl_minutes');
        }

        return $value;
    }
}
