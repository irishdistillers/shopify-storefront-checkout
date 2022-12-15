<?php

namespace Tests\ShopifyStorefrontCheckout\Traits;

use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;

trait MockCartTrait
{
    protected function getContext(): Context
    {
        return new Context('dummy.shopify.com', '2022-04', 'dummy_store_front_token');
    }

    protected function getNewVariantId(): string
    {
        return 'gid://shopify/ProductVariant/'.md5(uniqid());
    }

    protected function getNewLineItemId(): string
    {
        return 'gid://shopify/CartLine/'.md5(uniqid());
    }
}
