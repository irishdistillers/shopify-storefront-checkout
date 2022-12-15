<?php

namespace Tests\ShopifyStorefrontCheckout\Mock\Shopify;

class MockMarkets
{
    protected static array $markets = [
        'IE' => [
            'vat' => 0.22,
            'currency' => 'EUR',
            'price_adjustment' => 1.0,
        ],
        'GB' => [
            'vat' => 0.23,
            'currency' => 'GBP',
            'price_adjustment' => 1.23, // GB price adjusted by 1.23% compared to IE price. This is a dummy number.
        ],
    ];

    public function has(string $countryCode): bool
    {
        return isset(self::$markets[$countryCode]);
    }

    public function getCurrency(string $countryCode): string
    {
        $market = self::$markets[$countryCode ?? 'IE'];

        return $market['currency'];
    }

    public function getPrice(float $price, string $countryCode): float
    {
        $market = self::$markets[$countryCode ?? 'IE'];

        return $price * $market['price_adjustment'];
    }

    public function getVat(string $countryCode): float
    {
        $market = self::$markets[$countryCode ?? 'IE'];

        return $market['vat'];
    }
}
