<?php

namespace Tests\ShopifyStorefrontCheckout\Mock;

use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockGraphql;
use Tests\ShopifyStorefrontCheckout\TestCase;
use Tests\ShopifyStorefrontCheckout\Traits\MockCartTrait;

class MockGraphqlTest extends TestCase
{
    use MockCartTrait;

    public function test_create_mock_graphql_with_context()
    {
        $obj = new MockGraphql($this->getContext());
        $this->assertInstanceOf(MockGraphql::class, $obj);
    }

    public function test_get_mock_graphql_endpoints()
    {
        $obj = new MockGraphql($this->getContext());

        $endpoints = $obj->getEndpoints();

        $this->assertIsArray($endpoints);
        $this->assertNotEmpty($endpoints);
        $this->assertEquals($this->getExpectedGraphqlEndpoints(), array_keys($endpoints));
    }
}
