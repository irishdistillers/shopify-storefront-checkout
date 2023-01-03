<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Graphql;

use Irishdistillers\ShopifyStorefrontCheckout\Mock\Graphql\Query\MockGraphqlQuery;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockShopify;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;
use Irishdistillers\ShopifyStorefrontCheckout\Traits\ShopifyUtilsTrait;

abstract class MockBaseGraphql
{
    use ShopifyUtilsTrait;

    protected Context $context;

    protected MockShopify $mockShopify;

    public function __construct(Context $context)
    {
        $this->context = $context;
        $this->mockShopify = new MockShopify($context);
    }

    protected function response(?string $endpoint, MockGraphqlQuery $graphqlQuery, ?array $data): ?array
    {
        if ($endpoint) {
            // @todo Should return only fields in $graphqlQuery
            $ret = [
                $endpoint => $data,
            ];
        } else {
            // @todo Should return only fields in $graphqlQuery
            $ret = $data;
        }

        return $ret;
    }

    abstract public function getEndpoints(): array;
}
