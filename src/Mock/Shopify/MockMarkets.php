<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify;

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

    protected function getMarket(string $countryCode): array
    {
        return self::$markets[$countryCode] ?? self::$markets['IE'];
    }

    public function has(string $countryCode): bool
    {
        return isset(self::$markets[$countryCode]);
    }

    public function getCurrency(string $countryCode): string
    {
        $market = $this->getMarket($countryCode);

        return $market['currency'];
    }

    public function getPrice(float $price, string $countryCode): float
    {
        $market = $this->getMarket($countryCode);

        return $price * $market['price_adjustment'];
    }

    public function getVat(string $countryCode): float
    {
        $market = $this->getMarket($countryCode);

        return $market['vat'];
    }
}
