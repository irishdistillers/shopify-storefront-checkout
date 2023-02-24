<?php

namespace Tests\ShopifyStorefrontCheckout\Traits;

use Irishdistillers\ShopifyStorefrontCheckout\Cart;
use Irishdistillers\ShopifyStorefrontCheckout\CartService;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockFactory;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockGraphql;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;

trait MockCartTrait
{
    protected function getDummyShopBaseUrl(): string
    {
        return 'dummy.shopify.com';
    }

    protected function getDummyApiVersion(): string
    {
        return '2023-01';
    }

    protected function getDummyStorefrontToken(): string
    {
        return 'dummy_store_front_token';
    }

    protected function getDummyShopifyAccessToken(): string
    {
        return 'dummy_shopify_access_token';
    }

    protected function getCart(?MockFactory $factory = null): Cart
    {
        $context = $this->getContext();
        $mock = new MockGraphql($context, $factory);

        return new Cart(
            $context,
            null,
            $mock->getEndpoints()
        );
    }

    protected function getCartService(?MockFactory $factory = null): CartService
    {
        $context = $this->getContext();
        $mock = new MockGraphql($context, $factory);

        // Create cart, mocking all Graphql endpoints
        return new CartService(
            $context,
            null,
            $mock->getEndpoints()
        );
    }

    protected function getContext(): Context
    {
        return new Context(
            $this->getDummyShopBaseUrl(),
            $this->getDummyApiVersion(),
            $this->getDummyStorefrontToken(),
            $this->getDummyShopifyAccessToken()
        );
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

    protected function getRandomCartId(): string
    {
        return 'gid://shopify/Cart/'.md5(uniqid());
    }

    protected function getExpectedGraphqlEndpoints(): array
    {
        return [
            'query cart',
            'mutation cartCreate',
            'mutation cartBuyerIdentityUpdate',
            'mutation cartLinesAdd',
            'mutation cartLinesUpdate',
            'mutation cartLinesRemove',
            'mutation cartNoteUpdate',
            'mutation cartAttributesUpdate',
            'mutation cartDiscountCodesUpdate',
        ];
    }
}
