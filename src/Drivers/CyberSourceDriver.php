<?php

namespace Laranex\LaravelMyanmarPayments\Drivers;

use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use Laranex\LaravelMyanmarPayments\Contracts\PaymentDriver;
use Laranex\LaravelMyanmarPayments\Contracts\RequestPaymentData;
use Laranex\LaravelMyanmarPayments\Data\HandlePaymentResult;
use Laranex\LaravelMyanmarPayments\Data\Request\CyberSourceRequestPaymentData;
use Laranex\LaravelMyanmarPayments\Data\RequestPaymentResult;
use Laranex\LaravelMyanmarPayments\Enums\HandlePaymentStatus;
use Laranex\LaravelMyanmarPayments\Enums\PaymentFlow;
use Laranex\LaravelMyanmarPayments\Exceptions\PaymentException;
use Laranex\LaravelMyanmarPayments\Exceptions\SignatureVerificationException;

class CyberSourceDriver implements PaymentDriver
{
    public function __construct(private readonly array $config) {}

    public function initiate(RequestPaymentData $data): RequestPaymentResult
    {
        if (! $data instanceof CyberSourceRequestPaymentData) {
            throw new InvalidArgumentException('expects '.CyberSourceRequestPaymentData::class.', got '.get_class($data));
        }

        $data->validate();

        $profileId = $this->config['profile_id'];
        $accessKey = $this->config['access_key'];
        $secretKey = $this->config['secret_key'];
        $baseUrl = $this->config['base_url'];

        $transactionUuid = $data->transactionUuid ?: bin2hex(random_bytes(16));
        $referenceNumber = $data->referenceNumber ?: $data->transactionId;

        $signedFieldNames = 'access_key,profile_id,transaction_uuid,signed_field_names,signed_date_time,locale,transaction_type,reference_number,amount,currency,override_custom_receipt_page,override_backoffice_post_url,override_custom_cancel_page';

        $fields = [
            'access_key' => $accessKey,
            'profile_id' => $profileId,
            'transaction_uuid' => $transactionUuid,
            'signed_field_names' => $signedFieldNames,
            'signed_date_time' => gmdate('Y-m-d\TH:i:s\Z'),
            'locale' => 'en',
            'transaction_type' => $data->transactionType,
            'reference_number' => $referenceNumber,
            'amount' => number_format((float) $data->amount, 2, '.', ''),
            'currency' => $data->currency,
            'override_custom_receipt_page' => $data->frontendUrl,
            'override_backoffice_post_url' => $data->callbackUrl,
            'override_custom_cancel_page' => $data->cancelUrl,
        ];

        $signature = $this->sign($fields, $signedFieldNames, $secretKey);
        $fields['signature'] = $signature;

        $formUrl = $baseUrl.'/pay';

        $payload = Crypt::encryptString(json_encode(['formUrl' => $formUrl, 'formData' => $fields]));

        return new RequestPaymentResult(
            flow: PaymentFlow::FormBased,
            value: route('myanmar-payments.form', ['payload' => $payload]),
            form: ['url' => $formUrl, 'data' => $fields],
            transactionId: $data->transactionId,
            raw: $fields,
        );
    }

    public function getPaymentFlow(): PaymentFlow
    {
        return PaymentFlow::FormBased;
    }

    public function getPaymentStatus(string $status): HandlePaymentStatus
    {
        return match ($status) {
            'ACCEPT' => HandlePaymentStatus::Successful,
            'DECLINE' => HandlePaymentStatus::Failed,
            default => throw new PaymentException("CyberSource returned an unrecognised callback status: $status"),
        };
    }

    public function handleCallback(array $payload): HandlePaymentResult
    {
        $secretKey = $this->config['secret_key'];
        $incomingSignature = $payload['signature'] ?? '';

        $expectedSignature = $this->sign($payload, $payload['signed_field_names'] ?? '', $secretKey);

        if (! hash_equals($expectedSignature, $incomingSignature)) {
            throw new SignatureVerificationException('CyberSource callback signature verification failed.', raw: $payload);
        }

        return new HandlePaymentResult(
            status: $this->getPaymentStatus($payload['decision'] ?? ''),
            transactionId: $payload['transaction_id'] ?? null,
            raw: $payload,
        );
    }

    private function sign(array $fields, string $signedFieldNames, string $secretKey): string
    {
        $fieldList = explode(',', $signedFieldNames);
        $parts = array_map(fn ($field) => "$field={$fields[$field]}", $fieldList);

        return base64_encode(hash_hmac('sha256', implode(',', $parts), $secretKey, true));
    }
}
