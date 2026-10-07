<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Http;

use Illuminate\Contracts\Encryption\StringEncrypter;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Carbon;
use Laranex\PhpMyanmarPayments\Results\FormPayment;
use Throwable;

/**
 * Builds and reads the encrypted link to the auto-submitting payment form.
 */
class FormPaymentUrl
{
    public function __construct(
        private readonly StringEncrypter $encrypter,
        private readonly UrlGenerator $url,
        private readonly int $ttlMinutes = 30,
        private readonly bool $enabled = true,
    ) {}

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
            'expiresAt' => Carbon::now()->addMinutes($this->ttlMinutes)->getTimestamp(),
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
}
