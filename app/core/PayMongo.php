<?php
// app/core/PayMongo.php
// Thin wrapper around PayMongo's REST API (test mode). Only the pieces
// POS checkout needs: e-wallet Sources (GCash/PayMaya) and reading their
// status back. Secret key never leaves the server -- it's the HTTP Basic
// Auth username against api.paymongo.com, PayMongo's convention for
// server-side keys (no password half).

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
        if (!in_array($type, ['gcash', 'paymaya'], true)) {
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
}
