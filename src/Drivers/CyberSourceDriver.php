<?php

namespace Laranex\LaravelMyanmarPayments\Drivers;

use Illuminate\Support\Facades\Crypt;
use Laranex\LaravelMyanmarPayments\Contracts\PaymentData;
use Laranex\LaravelMyanmarPayments\Contracts\PaymentDriver;
use Laranex\LaravelMyanmarPayments\Data\CyberSourcePaymentData;
use Laranex\LaravelMyanmarPayments\Data\RequestPaymentResult;
use Laranex\LaravelMyanmarPayments\Enums\PaymentStatus;
use Laranex\LaravelMyanmarPayments\Exceptions\PaymentException;

class CyberSourceDriver implements PaymentDriver
{
    public function __construct(private readonly array $config) {}

    public function initiate(PaymentData $data): RequestPaymentResult
    {
        if (! $data instanceof CyberSourcePaymentData) {
            throw new PaymentException('CyberSourceDriver expects CyberSourcePaymentData, got '.get_class($data).'.');
        }

        $data->validate();

        $profileId = $this->config['profile_id'];
        $accessKey = $this->config['access_key'];
        $secretKey = $this->config['secret_key'];
        $baseUrl = $this->config['base_url'];

        $transactionUuid = $data->transactionUuid ?: bin2hex(random_bytes(16));
        $referenceNumber = $data->referenceNumber ?: $data->orderId;

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

        $payload = Crypt::encryptString(json_encode([
            'formUrl' => $formUrl,
            'formData' => $fields,
        ]));

        return new RequestPaymentResult(
            status: PaymentStatus::Initiated,
            redirectUrl: route('myanmar-payments.form', ['payload' => $payload]),
            formUrl: $formUrl,
            formData: $fields,
            orderId: $data->orderId,
            raw: $fields,
        );
    }

    public function verify(string $orderId): RequestPaymentResult
    {
        throw new PaymentException('CyberSource does not support order verification via this driver. Use handleCallback() instead.');
    }

    public function handleCallback(array $payload): RequestPaymentResult
    {
        $secretKey = $this->config['secret_key'];
        $incomingSignature = $payload['signature'] ?? '';

        $expectedSignature = $this->sign($payload, $payload['signed_field_names'] ?? '', $secretKey);

        if (! hash_equals($expectedSignature, $incomingSignature)) {
            throw new PaymentException('CyberSource callback signature verification failed.');
        }

        $decision = $payload['decision'] ?? '';
        $paymentStatus = match ($decision) {
            'ACCEPT' => PaymentStatus::Successful,
            'DECLINE', 'ERROR' => PaymentStatus::Failed,
            'CANCEL' => PaymentStatus::Cancelled,
            default => PaymentStatus::Pending,
        };

        return new RequestPaymentResult(
            status: $paymentStatus,
            orderId: $payload['req_reference_number'] ?? null,
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
