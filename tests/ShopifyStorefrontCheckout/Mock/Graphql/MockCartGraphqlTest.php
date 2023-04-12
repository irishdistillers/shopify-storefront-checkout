<?php

namespace Tests\ShopifyStorefrontCheckout\Mock\Graphql;

use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\ShopifyConstants;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\Graphql\MockCartGraphql;
use Tests\ShopifyStorefrontCheckout\TestCase;
use Tests\ShopifyStorefrontCheckout\Traits\MockCartTrait;

class MockCartGraphqlTest extends TestCase
{
    use MockCartTrait;

    public function test_create_mock_cart_graphql_with_context()
    {
        $obj = new MockCartGraphql($this->getContext());
        $this->assertInstanceOf(MockCartGraphql::class, $obj);
    }

    public function test_get_mock_cart_graphql_endpoints()
    {
        $obj = new MockCartGraphql($this->getContext());

        $expectedEndpoints = [
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
        $this->assertEquals(
            $expectedEndpoints,
            array_keys($obj->getEndpoints())
        );
    }

    public function test_create_cart_via_mock_cart_graphql()
    {
        $obj = new MockCartGraphql($this->getContext());

        $countryCode = 'GB';

        $query = <<<'QUERY'
 mutation cartCreate($input: CartInput) {
      cartCreate(input: $input) {
        cart {
          id
        }
        userErrors {
          field
          message
        }
      }
    }
QUERY;

        $variables = [
            'buyerIdentity' => [
                'countryCode' => $countryCode,
            ],
        ];

        $cart = $obj->cartCreate($query, $variables);

        $this->assertNotNull($cart);
        $this->assertEquals(['cartCreate'], array_keys($cart));
        $this->assertEquals(['cart'], array_keys($cart['cartCreate']));
        $this->assertNotNull($cart['cartCreate']['cart']['id']);
        $this->assertEquals($countryCode, $cart['cartCreate']['cart']['buyerIdentity']['countryCode']);
    }

    public function test_do_not_create_cart_via_mock_cart_graphql_with_empty_query()
    {
        $obj = new MockCartGraphql($this->getContext());

        $query = <<<'QUERY'
 mutation cartCreate($input: CartInput) {
 }
QUERY;

        $variables = [
            'buyerIdentity' => [
                'countryCode' => ShopifyConstants::DEFAULT_MARKET,
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Empty query');

        $obj->cartCreate($query, $variables);
    }

    public function test_create_cart_via_mock_cart_graphql_with_empty_variables()
    {
        $obj = new MockCartGraphql($this->getContext());

        $query = <<<'QUERY'
 mutation cartCreate($input: CartInput) {
      cartCreate(input: $input) {
        cart {
          id
        }
        userErrors {
          field
          message
        }
      }
    }
QUERY;

        $variables = [];

        $cart = $obj->cartCreate($query, $variables);
        $this->assertNotNull($cart);
        $this->assertEquals(['cartCreate'], array_keys($cart));
        $this->assertEquals(['cart'], array_keys($cart['cartCreate']));
        $this->assertNotNull($cart['cartCreate']['cart']['id']);
        $this->assertEquals(ShopifyConstants::DEFAULT_MARKET, $cart['cartCreate']['cart']['buyerIdentity']['countryCode']);
    }
}
