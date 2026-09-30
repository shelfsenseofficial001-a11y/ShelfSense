<?php
// app/core/PayMongo.php
// Thin wrapper around PayMongo's REST API (test mode). Secret key never
// leaves the server -- it's the HTTP Basic Auth username against
// api.paymongo.com, PayMongo's convention for server-side keys (no
// password half).
//
// Two different e-wallet integrations live here, because PayMongo itself
// splits them across two different APIs:
//  - GCash goes through the older Sources API (createEwalletSource /
//    getSource / createPaymentFromSource): create a Source, customer
//    approves it via its checkout_url, then a separate call actually
//    charges it once it reports "chargeable".
//  - PayMaya is NOT a valid Sources `type` (PayMongo rejects it outright)
//    -- it only works through the newer Payment Intent workflow
//    (createPaymentIntent -> createPaymentMethod -> attachPaymentMethod
//    -> getPaymentIntent): create a Payment Intent, create a Payment
//    Method of type paymaya, attach the two (which itself triggers the
//    charge once the customer approves), then poll the Payment Intent's
//    own status -- there's no separate "charge" call for this path.

namespace App\Core;

class PayMongo
{
    private const API_BASE = 'https://api.paymongo.com/v1';

    private static function secretKey(): string
    {
        $key = $_ENV['PAYMONGO_SECRET_KEY'] ?? '';
        if ($key === '') {
            throw new \Exception('PayMongo is not configured (missing PAYMONGO_SECRET_KEY).');
        }
        return $key;
    }

    private static function request(string $method, string $path, ?array $body = null): array
    {
        $ch = curl_init(self::API_BASE . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_USERPWD, self::secretKey() . ':');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \Exception('PayMongo request failed: ' . $err);
        }
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true);
        if ($statusCode >= 400) {
            $message = $decoded['errors'][0]['detail'] ?? 'PayMongo request failed (HTTP ' . $statusCode . ').';
            throw new \Exception($message);
        }

        return $decoded['data'] ?? [];
    }

    /**
     * Creates an e-wallet Source (GCash or PayMaya). The customer approves
     * it on their own phone via the returned checkout_url -- shown as a
     * QR code at the register, since the phone doing the approving is
     * usually not the register's own browser.
     */
    public static function createEwalletSource(int $amountCentavos, string $type, string $redirectSuccess, string $redirectFailed): array
    {
        // PayMongo's Sources API only ever accepted gcash/grab_pay --
        // paymaya has never been a valid Source type there (see the class
        // doc comment above for where PayMaya actually lives).
        if (!in_array($type, ['gcash', 'grab_pay'], true)) {
            throw new \Exception('Unsupported e-wallet type.');
        }

        $data = self::request('POST', '/sources', [
            'data' => [
                'attributes' => [
                    'amount' => $amountCentavos,
                    'currency' => 'PHP',
                    'type' => $type,
                    'redirect' => [
                        'success' => $redirectSuccess,
                        'failed' => $redirectFailed
                    ]
                ]
            ]
        ]);

        return [
            'id' => $data['id'],
            'status' => $data['attributes']['status'],
            'checkout_url' => $data['attributes']['redirect']['checkout_url']
        ];
    }

    public static function getSource(string $sourceId): array
    {
        $data = self::request('GET', '/sources/' . $sourceId);
        return [
            'id' => $data['id'],
            'status' => $data['attributes']['status']
        ];
    }

    /**
     * Charges a Source once it's chargeable (customer approved on their
     * phone) -- this is the step that actually moves money.
     */
    public static function createPaymentFromSource(string $sourceId, int $amountCentavos, string $description): array
    {
        $data = self::request('POST', '/payments', [
            'data' => [
                'attributes' => [
                    'amount' => $amountCentavos,
                    'currency' => 'PHP',
                    'description' => $description,
                    'source' => ['id' => $sourceId, 'type' => 'source']
                ]
            ]
        ]);

        return [
            'id' => $data['id'],
            'status' => $data['attributes']['status']
        ];
    }

    /**
     * Step 1 of the PayMaya flow: opens a Payment Intent for the cart
     * total. Nothing is charged yet -- this just reserves the amount and
     * hands back a client_key the attach step needs.
     */
    public static function createPaymentIntent(int $amountCentavos, string $description): array
    {
        $data = self::request('POST', '/payment_intents', [
            'data' => [
                'attributes' => [
                    'amount' => $amountCentavos,
                    'currency' => 'PHP',
                    'capture_type' => 'automatic',
                    'description' => $description,
                    'payment_method_allowed' => ['paymaya'],
                ]
            ]
        ]);

        return [
            'id' => $data['id'],
            'client_key' => $data['attributes']['client_key'],
            'status' => $data['attributes']['status']
        ];
    }

    /**
     * Step 2: a PayMaya "payment method" is just a type tag here -- there
     * are no card/billing details to collect for an e-wallet, so this is
     * effectively a formality the Payment Intent workflow requires before
     * attach() can run.
     */
    public static function createPaymentMethod(string $type): array
    {
        if (!in_array($type, ['paymaya', 'gcash'], true)) {
            throw new \Exception('Unsupported payment method type.');
        }

        $data = self::request('POST', '/payment_methods', [
            'data' => [
                'attributes' => [
                    'type' => $type,
                ]
            ]
        ]);

        return ['id' => $data['id']];
    }

    /**
     * Step 3: links the Payment Method to the Payment Intent -- this is
     * the call that actually starts moving money. For an e-wallet it
     * comes back with status "awaiting_next_action" and a redirect URL
     * the customer approves on their own phone (shown as a QR, same as
     * the Sources flow); once they approve, the Intent finishes on its
     * own and there is no separate "charge" call to make afterward.
     */
    public static function attachPaymentMethod(string $paymentIntentId, string $paymentMethodId, string $clientKey, string $returnUrl): array
    {
        $data = self::request('POST', '/payment_intents/' . $paymentIntentId . '/attach', [
            'data' => [
                'attributes' => [
                    'payment_method' => $paymentMethodId,
                    'client_key' => $clientKey,
                    'return_url' => $returnUrl,
                ]
            ]
        ]);

        return [
            'id' => $data['id'],
            'status' => $data['attributes']['status'],
            'redirect_url' => $data['attributes']['next_action']['redirect']['url'] ?? null,
        ];
    }

    /**
     * Polled by the register while waiting for the customer to approve.
     * Once status is "succeeded", the settled payment's own id (pay_...)
     * is pulled out for the order's payment_reference -- the equivalent
     * of the Sources flow's createPaymentFromSource() return id, just
     * arriving as part of the Intent instead of a separate call.
     */
    public static function getPaymentIntent(string $paymentIntentId): array
    {
        $data = self::request('GET', '/payment_intents/' . $paymentIntentId);

        $payments = $data['attributes']['payments'] ?? [];
        $paymentId = $payments[0]['id'] ?? null;

        return [
            'id' => $data['id'],
            'status' => $data['attributes']['status'],
            'payment_id' => $paymentId,
        ];
    }
}
