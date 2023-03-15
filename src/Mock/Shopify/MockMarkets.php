<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify;

class MockMarkets
{
    protected static array $markets = [
        'AU' => [
            'vat' => 0.10,
            'currency' => 'AUD',
            'price_adjustment' => 1.10, // Price adjusted, compared to IE price. This is a dummy number!
        ],
        'CH' => [
            'vat' => 0.077,
            'currency' => 'CHF',
            'price_adjustment' => 1.077, // Price adjusted, compared to IE price. This is a dummy number!
        ],
        'CN' => [
            'vat' => 0.13,
            'currency' => 'CNY',
            'price_adjustment' => 1.13, // Price adjusted, compared to IE price. This is a dummy number!
        ],
        'DE' => [
            'vat' => 0.19,
            'currency' => 'EUR',
            'price_adjustment' => 1.19, // Price adjusted, compared to IE price. This is a dummy number!
        ],
        'FR' => [
            'vat' => 0.20,
            'currency' => 'EUR',
            'price_adjustment' => 1.20, // Price adjusted, compared to IE price. This is a dummy number!
        ],
        'IE' => [
            'vat' => 0.22,
            'currency' => 'EUR',
            'price_adjustment' => 1.0,
        ],
        'GB' => [
            'vat' => 0.23,
            'currency' => 'GBP',
            'price_adjustment' => 1.23, // Price adjusted, compared to IE price. This is a dummy number!
        ],
        'JP' => [
            'vat' => 0.10,
            'currency' => 'JPY',
            'price_adjustment' => 1.10, // Price adjusted, compared to IE price. This is a dummy number!
        ],
        'NZ' => [
            'vat' => 0.15,
            'currency' => 'NZD',
            'price_adjustment' => 1.15, // Price adjusted, compared to IE price. This is a dummy number!
        ],
        'SG' => [
            'vat' => 0.07,
            'currency' => 'SGD',
            'price_adjustment' => 1.07, // Price adjusted, compared to IE price. This is a dummy number!
        ],
        'ZA' => [
            'vat' => 0.15,
            'currency' => 'ZAR',
            'price_adjustment' => 1.15, // Price adjusted, compared to IE price. This is a dummy number!
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
