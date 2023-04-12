<?php

namespace Tests\ShopifyStorefrontCheckout\Mock;

use Irishdistillers\ShopifyStorefrontCheckout\Mock\Factory\MockFactoryHandler;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockFactory;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockShopify;
use Tests\ShopifyStorefrontCheckout\TestCase;
use Tests\ShopifyStorefrontCheckout\Traits\MockCartTrait;

class MockFactoryTest extends TestCase
{
    use MockCartTrait;

    public function test_handle_mock_factory()
    {
        $factory = new MockFactory();
        $shopify = new MockShopify($this->getContext());

        $cartId = $this->getRandomCartId();

        // Handle create cart
        $ret = $factory->handle(MockFactory::FACTORY_CART_CREATE, $shopify, $cartId, 'IE');
        $this->assertNotNull($ret);
        $this->assertIsArray($ret);
        $this->assertArrayHasKey('id', $ret);

        // Do not handle creation of existing cart
        $ret = $factory->handle(MockFactory::FACTORY_CART_CREATE, $shopify, base64_decode($ret['id']), 'IE');
        $this->assertNull($ret);
    }

    public function test_register_mock_factory_as_callable()
    {
        $factory = new MockFactory();
        $shopify = new MockShopify($this->getContext());

        $factory->register('test', new MockFactoryHandler(function ($shopify, ...$params) {
            $this->assertInstanceOf(MockShopify::class, $shopify);
            $this->assertCount(2, $params);
            $this->assertEquals('hello', $params[0]);
            $this->assertEquals('world', $params[1]);

            return $params[0].'.'.$params[1];
        }));

        $ret = $factory->handle('test', $shopify, 'hello', 'world');
        $this->assertEquals('hello.world', $ret);
    }

    public function test_register_mock_factory_as_array()
    {
        $factory = new MockFactory();
        $shopify = new MockShopify($this->getContext());

        $closure = function ($shopify, ...$params) {
            $this->assertInstanceOf(MockShopify::class, $shopify);
            $this->assertCount(2, $params);
            $this->assertEquals('hello', $params[0]);
            $this->assertEquals('world', $params[1]);

            return $params[0].'.'.$params[1];
        };

        $factory->register('test', new MockFactoryHandler($closure));

        $ret = $factory->handle('test', $shopify, 'hello', 'world');
        $this->assertEquals('hello.world', $ret);
    }

    public function test_do_not_handle_non_existing_mock_factory()
    {
        $factory = new MockFactory();
        $shopify = new MockShopify($this->getContext());

        $ret = $factory->handle('test', $shopify, 'hello', 'world');
        $this->assertNull($ret);
    }

    public function test_do_not_handle_non_callable_mock_factory()
    {
        $factory = new MockFactory();
        $shopify = new MockShopify($this->getContext());

        $factory->register('test', new MockFactoryHandler('hello_world'));

        $ret = $factory->handle('test', $shopify, 'hello', 'world');
        $this->assertNull($ret);
    }
}
