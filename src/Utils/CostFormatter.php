<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Utils;

class CostFormatter
{
    private array $cart;

    private array $currencyCodes = [
        'AUD' => '$',
        'CHF' => 'CHF',
        'CNY' => '¥',
        'EUR' => '€',
        'JPY' => '¥',
        'GBP' => '£',
        'NZD' => '$',
        'SGD' => '$',
        'ZAR' => 'R',
        'USD' => '$',
    ];

    public function __construct(array $cart)
    {
        $this->cart = $cart;
    }

    public function symbol($currencyCode): string
    {
        return $this->currencyCodes[$currencyCode] ?? $currencyCode;
    }

    public function estimatedCost(): string
    {
        if (!empty($this->cart)) {
            $totalAmount = $this->cart['estimatedCost']['totalAmount'] ?? null;
            if ($totalAmount) {
                return $this->symbol($totalAmount['currencyCode']) . $totalAmount['amount'];
            }
        }

        return '';
    }
}