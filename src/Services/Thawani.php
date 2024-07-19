<?php

namespace FriendsOfBotble\Thawani\Services;

use GuzzleHttp\Client;

class Thawani
{
    public function callAPI(string $url, array $params, string $method = 'POST'): array
    {
        $secretKey = get_payment_setting('secret_key', THAWANI_PAYMENT_METHOD_NAME);
        $publishableKey = $this->getPublishableKey();

        $client = new Client();

        $params['key'] = $publishableKey;

        $response = $client->request($method, $this->getEndpoint('/api/v1/' . ltrim($url, '/')), [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Thawani-Api-Key' => $secretKey,
            ],
            'json' => $params,
            'verify' => false,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getEndpoint(string $path): string
    {
        $apiURL = setting('payment_' . THAWANI_PAYMENT_METHOD_NAME . '_mode') == 0 ? 'https://uatcheckout.thawani.om' : 'https://checkout.thawani.om';

        return $apiURL . $path;
    }

    public function getPublishableKey(): string
    {
        return get_payment_setting('publishable_key', THAWANI_PAYMENT_METHOD_NAME);
    }
}
