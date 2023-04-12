<?php

namespace Tests\ShopifyStorefrontCheckout\Shopify;

use ArrayObject;
use Exception;
use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\ShopifyAccountInterface;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;
use Tests\ShopifyStorefrontCheckout\TestCase;
use TypeError;

class ContextTest extends TestCase
{
    public function test_create_shopify_context()
    {
        $context = new Context('dummy.shopify.com', '2023-01', 'dummy_store_front_token', 'dummy_access_token');

        $this->assertEquals('dummy.shopify.com', $context->getShopBaseUrl());
        $this->assertEquals('2023-01', $context->getApiVersion());
        $this->assertEquals('dummy_store_front_token', $context->getShopifyStoreFrontAccessToken());
        $this->assertEquals('dummy_access_token', $context->getShopifyAccessToken());
    }

    public function test_create_shopify_context_with_full_url()
    {
        $context = new Context('https://dummy.shopify.com/api/admin/2022-01', '2023-01', 'dummy_store_front_token', 'dummy_access_token');

        $this->assertEquals('dummy.shopify.com', $context->getShopBaseUrl());
        $this->assertEquals('2023-01', $context->getApiVersion());
        $this->assertEquals('dummy_store_front_token', $context->getShopifyStoreFrontAccessToken());
        $this->assertEquals('dummy_access_token', $context->getShopifyAccessToken());
    }

    public function test_do_not_create_shopify_context_with_wrong_full_url()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Shop full URL is not valid');

        $context = new Context('http://@/api/admin/2022-01', '2023-01', 'dummy_store_front_token', 'dummy_access_token');
    }

    public function test_create_shopify_context_with_valid_config_and_empty_fallback()
    {
        $config = new ArrayObject([
            ShopifyAccountInterface::SHOPIFY_BASE_URL => 'dummy.shopify.com',
            ShopifyAccountInterface::API_VERSION => '2022-01',
            ShopifyAccountInterface::STOREFRONT_ACCESS_TOKEN => 'dummy_store_front_token',
            ShopifyAccountInterface::APP_SIGNATURE => 'dummy_access_token',
        ]);

        $context = Context::createFromConfig($config);

        $this->assertEquals('dummy.shopify.com', $context->getShopBaseUrl());
        $this->assertEquals('2022-01', $context->getApiVersion());
        $this->assertEquals('dummy_store_front_token', $context->getShopifyStoreFrontAccessToken());
        $this->assertEquals('dummy_access_token', $context->getShopifyAccessToken());
    }

    public function test_create_shopify_context_with_valid_config_and_valid_fallback()
    {
        $config = new ArrayObject([
            ShopifyAccountInterface::SHOPIFY_BASE_URL => 'dummy.shopify.com',
            ShopifyAccountInterface::API_VERSION => '2022-01',
            ShopifyAccountInterface::STOREFRONT_ACCESS_TOKEN => 'dummy_store_front_token',
            ShopifyAccountInterface::APP_SIGNATURE => 'dummy_access_token',
        ]);

        $fallback = [
            ShopifyAccountInterface::SHOPIFY_BASE_URL => 'dummy.shopify.com',
            ShopifyAccountInterface::API_VERSION => '2023-01',
            ShopifyAccountInterface::STOREFRONT_ACCESS_TOKEN => 'dummy_store_front_token',
            ShopifyAccountInterface::APP_SIGNATURE => 'dummy_access_token',
        ];

        $context = Context::createFromConfig($config, $fallback);

        $this->assertEquals('dummy.shopify.com', $context->getShopBaseUrl());
        $this->assertEquals('2022-01', $context->getApiVersion());
        $this->assertEquals('dummy_store_front_token', $context->getShopifyStoreFrontAccessToken());
        $this->assertEquals('dummy_access_token', $context->getShopifyAccessToken());
    }

    public function test_create_shopify_context_with_empty_config_and_valid_fallback()
    {
        $config = new ArrayObject();

        $fallback = [
            ShopifyAccountInterface::SHOPIFY_BASE_URL => 'dummy.shopify.com',
            ShopifyAccountInterface::API_VERSION => '2023-01',
            ShopifyAccountInterface::STOREFRONT_ACCESS_TOKEN => 'dummy_store_front_token',
            ShopifyAccountInterface::APP_SIGNATURE => 'dummy_access_token',
        ];

        $context = Context::createFromConfig($config, $fallback);

        $this->assertEquals('dummy.shopify.com', $context->getShopBaseUrl());
        $this->assertEquals('2023-01', $context->getApiVersion());
        $this->assertEquals('dummy_store_front_token', $context->getShopifyStoreFrontAccessToken());
        $this->assertEquals('dummy_access_token', $context->getShopifyAccessToken());
    }

    public function test_do_not_create_shopify_context_with_empty_config_and_empty_fallback()
    {
        $config = new ArrayObject();

        $fallback = [];

        $this->expectException(TypeError::class);

        Context::createFromConfig($config, $fallback);
    }

    public function test_update_shopify_context()
    {
        $context = new Context('dummy.shopify.com', '2023-01', 'dummy_store_front_token', 'dummy_access_token');

        $this->assertEquals('dummy.shopify.com', $context->getShopBaseUrl());
        $this->assertEquals('2023-01', $context->getApiVersion());
        $this->assertEquals('dummy_store_front_token', $context->getShopifyStoreFrontAccessToken());
        $this->assertEquals('dummy_access_token', $context->getShopifyAccessToken());

        $context->setShopifyStoreFrontAccessToken('new_store_front_token');
        $this->assertEquals('new_store_front_token', $context->getShopifyStoreFrontAccessToken());

        $context->setShopifyAccessToken('new_access_token');
        $this->assertEquals('new_access_token', $context->getShopifyAccessToken());
    }
}
