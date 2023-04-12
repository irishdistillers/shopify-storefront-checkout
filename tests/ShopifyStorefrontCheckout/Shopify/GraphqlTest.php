<?php

namespace Tests\ShopifyStorefrontCheckout\Shopify;

use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Graphql;
use Tests\ShopifyStorefrontCheckout\TestCase;
use Tests\ShopifyStorefrontCheckout\Traits\MockCartTrait;

class GraphqlTest extends TestCase
{
    use MockCartTrait;

    public function test_create_graphql_using_storefront_api()
    {
        $obj = new Graphql($this->getContext(), true);

        $this->assertEmpty($obj->getLastError());
        $this->assertEmpty($obj->getLastResponse());

        $url = 'https://'.$this->getDummyShopBaseUrl().'/api/'.$this->getDummyApiVersion().'/graphql.json';
        $this->assertEquals($url, $obj->getApiPath());

        $this->assertEquals([
            'Content-Type: application/json',
            'X-Shopify-Storefront-Access-Token: '.$this->getDummyStorefrontToken(),
        ], $obj->headers());
    }

    public function test_create_graphql_using_admin_api()
    {
        $obj = new Graphql($this->getContext(), false);

        $this->assertEmpty($obj->getLastError());
        $this->assertEmpty($obj->getLastResponse());

        $url = 'https://'.$this->getDummyShopBaseUrl().'/admin/api/'.$this->getDummyApiVersion().'/graphql.json';
        $this->assertEquals($url, $obj->getApiPath());

        $this->assertEquals([
            'Content-Type: application/json',
            'X-Shopify-Access-Token: '.$this->getDummyShopifyAccessToken(),
        ], $obj->headers());
    }
}
