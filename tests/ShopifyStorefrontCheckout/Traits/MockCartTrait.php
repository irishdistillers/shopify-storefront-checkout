<?php

namespace Tests\ShopifyStorefrontCheckout\Traits;

use Exception;
use Irishdistillers\ShopifyStorefrontCheckout\Cart;
use Irishdistillers\ShopifyStorefrontCheckout\CartService;
use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\LogLevelConstants;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockFactory;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockGraphql;
use Irishdistillers\ShopifyStorefrontCheckout\SellingPlanGroupService;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;
use Monolog\Logger;

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

    /**
     * @throws Exception
     */
    protected function getCart(?MockFactory $factory = null, ?Logger $logger = null, int $logLevel = LogLevelConstants::LOG_LEVEL_NORMAL): Cart
    {
        $context = $this->getContext();

        return new Cart(
            $context,
            $logger,
            (new MockGraphql($context, $factory))->getEndpoints(),
            $logLevel
        );
    }

    /**
     * @throws Exception
     */
    protected function getCartService(?MockFactory $factory = null, ?Logger $logger = null, int $logLevel = LogLevelConstants::LOG_LEVEL_NORMAL): CartService
    {
        $context = $this->getContext();

        // Create cart, mocking all Graphql endpoints
        return new CartService(
            $context,
            $logger,
            (new MockGraphql($context, $factory))->getEndpoints(),
            $logLevel
        );
    }

    /**
     * @throws Exception
     */
    protected function getSellingPlanGroupService(?MockFactory $factory = null, ?Logger $logger = null, int $logLevel = LogLevelConstants::LOG_LEVEL_NORMAL): SellingPlanGroupService
    {
        $context = $this->getContext();

        // Create cart, mocking all Graphql endpoints
        return new SellingPlanGroupService(
            $context,
            $logger,
            (new MockGraphql($context, $factory))->getEndpoints(),
            $logLevel
        );
    }

    /**
     * @throws Exception
     */
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

    protected function getRandomSellingCartId(): string
    {
        return 'gid://shopify/SellingPlan/'.md5(uniqid());
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
            'query SellingPlanGroup',
            'query SellingPlanGroupsList',
            'mutation sellingPlanGroupCreate',
            'mutation sellingPlanGroupAddProducts',
            'mutation sellingPlanGroupAddProductVariants',
            'mutation sellingPlanGroupDelete',
        ];
    }
}
