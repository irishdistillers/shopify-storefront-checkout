<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock;

use Irishdistillers\ShopifyStorefrontCheckout\Mock\Graphql\MokCartGraphql;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;

class MockGraphql
{
    protected Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getEndpoints(): array
    {
        // Add here Graphql mocks
        return array_merge(
            (new MokCartGraphql($this->context))->getEndpoints(),
        );
    }
}
