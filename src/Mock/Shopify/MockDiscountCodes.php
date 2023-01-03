<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify;

class MockDiscountCodes
{
    protected array $validDiscountCodes = [
        'FOC',
        'TENPERCENT',
    ];

    public function all(): array
    {
        return $this->validDiscountCodes;
    }

    public function has(string $discountCode): bool
    {
        return in_array($discountCode, $this->validDiscountCodes);
    }
}
