<?php

namespace Laranex\LaravelMyanmarPayments;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class YomaMmqr
{
    /**
     * Seconds a generated QR stays valid before Yoma expires it.
     */
    public const QR_LIFETIME_IN_SECONDS = 120;

    /**
     * Request a client credentials access token.
     *
     * The token is intentionally NOT cached, even though Yoma issues it with an eight hour
     * lifetime and the specification suggests reusing it until it expires. Yoma is the only
     * gateway in this package that authenticates with a token at all, every other one signs
     * each request on its own with an hmac, so every gateway here stays stateless and holds
     * no shared state between requests. A fresh token is therefore requested per call, which
     * costs one extra round trip per operation. Cache it in the consuming application if that
     * overhead ever matters, rather than reintroducing state here.
     *
     * @throws Exception
     */
    public function getAccessToken(): string
    {
        $config = config("laravel-myanmar-payments.yoma_mmqr");
        $clientId = $config["client_id"];
        $clientSecret = $config["client_secret"];

        if (!$clientId || !$clientSecret) {
            throw new Exception("Invalid Yoma MMQR Client Id OR Invalid Yoma MMQR Client Secret");
        }

        $baseUrl = $config["base_url"];
        $response = Http::asForm()->withBasicAuth($clientId, $clientSecret)
            ->post("$baseUrl/token", [
                "grant_type" => "client_credentials",
            ]);

        if (!$response->successful()) {
            throw new Exception("Something went wrong in requesting an access token for Yoma MMQR with the status code of " . $response->status());
        }

        $accessToken = $response->json()["access_token"] ?? "";
        if (!$accessToken) {
            throw new Exception("Yoma MMQR did not return an access token");
        }

        return $accessToken;
    }

    /**
     * Register an order with Yoma. An order must be checked out before its QR can be generated.
     *
     * @return array{checkOutStatus: bool, errorCode: string|null, errorDescription: string|null}
     * @throws Exception
     */
    public function checkoutOrder(string $orderNumber, string $amount, string $description): array
    {
        $this->validateData($orderNumber, $amount, $description);

        return $this->sendRequest("payment/checkout", [
            "merchantId" => config("laravel-myanmar-payments.yoma_mmqr.merchant_id"),
            "orderNumber" => $orderNumber,
            "amount" => $amount,
            "description" => $description,
        ], "checking out the order");
    }

    /**
     * Generate the MMQR of an order that has already been checked out.
     *
     * qrString is an already rendered PNG in base64, so it can be displayed as is
     * with a data:image/png;base64 URI and needs no QR encoding library.
     *
     * expiresInSeconds is added by this package rather than returned by Yoma, which reports
     * no deadline of its own. It counts from the moment the QR was issued, so it is only
     * true for this response and goes stale if it is stored. Turn it into a deadline as
     * soon as it is received if it has to outlive the request.
     *
     * @return array{refLabel: string, qrString: string, expiresInSeconds: int, errorCode: string|null, errorDescription: string|null}
     * @throws Exception
     */
    public function generateQr(string $orderNumber): array
    {
        $result = $this->sendRequest("qr/generate", [
            "merchantId" => config("laravel-myanmar-payments.yoma_mmqr.merchant_id"),
            "orderNumber" => $orderNumber,
        ], "generating the MMQR");

        $result["expiresInSeconds"] = self::QR_LIFETIME_IN_SECONDS;

        return $result;
    }

    /**
     * Check out an order and generate its MMQR in one call.
     *
     * qrString is an already rendered PNG in base64, so it can be displayed as is
     * with a data:image/png;base64 URI and needs no QR encoding library.
     * expiresInSeconds carries how long the QR stays payable for, see generateQr().
     *
     * @return array{refLabel: string, qrString: string, expiresInSeconds: int, errorCode: string|null, errorDescription: string|null}
     * @throws Exception
     */
    public function getPaymentQr(string $orderNumber, string $amount, string $description): array
    {
        $this->checkoutOrder($orderNumber, $amount, $description);

        return $this->generateQr($orderNumber);
    }

    /**
     * Enquire the payment status of a generated QR using the reference label it returned.
     *
     * @return array{refLabel: string, paymentStatus: string, errorCode: string|null, errorDescription: string|null}
     * @throws Exception
     */
    public function checkPaymentStatus(string $refLabel): array
    {
        if (!$refLabel) {
            throw new Exception("Reference label is required");
        }

        return $this->sendRequest("payment/check-status", [
            "merchantId" => config("laravel-myanmar-payments.yoma_mmqr.merchant_id"),
            "refLabel" => $refLabel,
        ], "checking the payment status");
    }

    /**
     * Verify the hash of a callback sent by the Yoma payment hub.
     *
     * The hash is an HMAC-SHA256 of "orderNumber=xxxx&status=xxxx" keyed with the order number
     * prefixed to the webhook hash key, which is how the payment hub computes it. That hash key
     * and the webhook secret are two separate credentials issued by Yoma: the hash key only
     * ever signs, while the webhook secret is echoed back in the X-Webhook-Secret header, which
     * is asserted as well when one is configured and left unchecked when it is not.
     *
     * @throws Exception
     */
    public function verifySignature(Request $request): bool
    {
        $config = config("laravel-myanmar-payments.yoma_mmqr");
        $hashKey = (string)$config["webhook_hashkey"];
        $webhookSecret = (string)$config["webhook_secret"];

        /**
         * Without a hash key the signing key collapses to the order number alone, which is
         * public, so a missing key must fail loudly rather than verify forgeable callbacks.
         */
        if (!$hashKey) {
            throw new Exception("Invalid Yoma MMQR Webhook Hash Key");
        }

        if ($webhookSecret && !hash_equals($webhookSecret, (string)$request->header("X-Webhook-Secret"))) {
            return false;
        }

        $orderNumber = (string)$request->input("orderNumber");
        $status = (string)$request->input("status");
        $hash = hash_hmac("sha256", "orderNumber=$orderNumber&status=$status", $orderNumber . $hashKey);

        return hash_equals($hash, (string)$request->input("hashValue"));
    }

    /**
     * Send an authenticated request and surface the error codes Yoma returns in the body.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws Exception
     */
    private function sendRequest(string $path, array $data, string $operation): array
    {
        $config = config("laravel-myanmar-payments.yoma_mmqr");
        $baseUrl = $config["base_url"];
        $apiVersion = $config["api_version"];

        $response = Http::withToken($this->getAccessToken())
            ->withHeaders([
                "Accept" => "application/json",
                "Content-Type" => "application/json",
            ])->post("$baseUrl/payment-gateway/$apiVersion/api/$path", $data);

        if (!$response->successful()) {
            throw new Exception("Something went wrong in $operation for Yoma MMQR with the status code of " . $response->status());
        }

        $payload = $response->json();

        /**
         * Yoma answers business failures with a 200 status and a populated errorCode,
         * so the body has to be inspected rather than the status code.
         */
        $errorCode = $payload["errorCode"] ?? null;
        if ($errorCode) {
            throw new Exception("Something went wrong in $operation for Yoma MMQR - $errorCode: " . ($payload["errorDescription"] ?? ""));
        }

        return $payload;
    }

    /**
     * @throws Exception
     */
    private function validateData(string $orderNumber, string $amount, string $description): void
    {
        if (!config("laravel-myanmar-payments.yoma_mmqr.merchant_id")) {
            throw new Exception("Invalid Yoma MMQR Merchant Id");
        }

        if (!$orderNumber || strlen($orderNumber) > 20) {
            throw new Exception("Order number is required and cannot be longer than 20 characters");
        }

        if (strlen($description) > 50) {
            throw new Exception("Payment description cannot be longer than 50 characters");
        }

        if (!is_numeric($amount) || $amount <= 0) {
            throw new Exception("Amount must be numeric and cannot be less than or equal to 0");
        }
    }
}
