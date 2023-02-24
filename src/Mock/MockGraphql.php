<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock;

use Irishdistillers\ShopifyStorefrontCheckout\Mock\Graphql\MockCartGraphql;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;

class MockGraphql
{
    protected Context $context;

    protected ?MockFactory $factory;

    public function __construct(Context $context, ?MockFactory $factory = null)
    {
        $this->context = $context;
        $this->factory = $factory;
    }

    public function getEndpoints(): array
    {
        // Add here Graphql mocks
        return array_merge(
            (new MockCartGraphql($this->context, $this->factory))->getEndpoints(),
        );
    }
}
