<?php

namespace Tests\ShopifyStorefrontCheckout\Shopify;

use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;
use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase
{

    public function test_create_shopify_context()
    {
        $context = new Context('dummy.shopify.com', '2022-04', 'dummy_store_front_token', 'dummy_access_token');

        $this->assertEquals('dummy.shopify.com', $context->getShopBaseUrl());
        $this->assertEquals('2022-04', $context->getApiVersion());
        $this->assertEquals('dummy_store_front_token', $context->getShopifyStoreFrontAccessToken());
        $this->assertEquals('dummy_access_token', $context->getShopifyAccessToken());
    }

    public function test_update_shopify_context()
    {
        $context = new Context('dummy.shopify.com', '2022-04', 'dummy_store_front_token', 'dummy_access_token');

        $this->assertEquals('dummy.shopify.com', $context->getShopBaseUrl());
        $this->assertEquals('2022-04', $context->getApiVersion());
        $this->assertEquals('dummy_store_front_token', $context->getShopifyStoreFrontAccessToken());
        $this->assertEquals('dummy_access_token', $context->getShopifyAccessToken());

        $context->setShopifyStoreFrontAccessToken('new_store_front_token');
        $this->assertEquals('new_store_front_token', $context->getShopifyStoreFrontAccessToken());

        $context->setShopifyAccessToken('new_access_token');
        $this->assertEquals('new_access_token', $context->getShopifyAccessToken());
    }
}