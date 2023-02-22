<?php

namespace Tests\ShopifyStorefrontCheckout\Traits;

use Irishdistillers\ShopifyStorefrontCheckout\Cart;
use Irishdistillers\ShopifyStorefrontCheckout\CartService;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockGraphql;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;

trait MockCartTrait
{
    protected function getCart(): Cart
    {
        $context = $this->getContext();
        $mock = new MockGraphql($context);

        return new Cart(
            $context,
            null,
            $mock->getEndpoints()
        );
    }

    protected function getCartService(): CartService
    {
        $context = $this->getContext();
        $mock = new MockGraphql($context);

        // Create cart, mocking all Graphql endpoints
        return new CartService(
            $context,
            null,
            $mock->getEndpoints()
        );
    }

    protected function getContext(): Context
    {
        return new Context('dummy.shopify.com', '2023-01', 'dummy_store_front_token');
    }

    protected function getNewVariantId(): string
    {
        return 'gid://shopify/ProductVariant/'.md5(uniqid());
    }

    protected function getNewVariantIdWithoutGidPrefix(): string
    {
        return md5(uniqid());
    }

    protected function getNewLineItemId(): string
    {
        return 'gid://shopify/CartLine/'.md5(uniqid());
    }

    protected function getRandomCartId(): string{
        return 'gid://shopify/Cart/'.md5(uniqid());
    }
}
