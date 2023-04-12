<?php

namespace Tests\ShopifyStorefrontCheckout\Mock\Graphql\Query;

use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\ShopifyConstants;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\Graphql\Query\MockGraphqlQuery;
use Tests\ShopifyStorefrontCheckout\TestCase;

class MockGraphqlQueryTest extends TestCase
{
    public function test_create_mock_graphql_query_without_context()
    {
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
                'countryCode' => ShopifyConstants::DEFAULT_MARKET,
            ],
        ];

        $obj = new MockGraphqlQuery($query, $variables);

        $this->assertEquals('mutation', $obj->getType());
        $this->assertEquals('cartCreate(input: $input)', $obj->getEndpoint());

        $this->assertEquals(['cart', 'userErrors'], array_keys($obj->getFields()));
        $this->assertEquals(null, $obj->getContext('country'));
        $this->assertEquals(['buyerIdentity'], array_keys($obj->getVariables()));
    }

    public function test_create_mock_graphql_query_with_context()
    {
        $query = <<<'QUERY'
 query cart($cartId: ID!, $countryCode: CountryCode!)
    @inContext(country: $countryCode) {
      cart( id: $cartId ) {
        id
        createdAt
        updatedAt
      }
    }
QUERY;

        $variables = [
            'buyerIdentity' => [
                'countryCode' => ShopifyConstants::DEFAULT_MARKET,
            ],
        ];

        $obj = new MockGraphqlQuery($query, $variables);

        $this->assertEquals('query', $obj->getType());
        $this->assertEquals('cart( id: $cartId )', $obj->getEndpoint());

        $this->assertEquals(['id', 'createdAt', 'updatedAt'], $obj->getFields());
        $this->assertEquals(null, $obj->getContext('country'));
        $this->assertEquals(['buyerIdentity'], array_keys($obj->getVariables()));
    }

    public function test_do_not_create_mock_graphql_query_with_invalid_query()
    {
        $query = <<<'QUERY'



QUERY;

        $variables = [
            'buyerIdentity' => [
                'countryCode' => ShopifyConstants::DEFAULT_MARKET,
            ],
        ];

        $obj = new MockGraphqlQuery($query, $variables);

        $this->assertNull($obj->getType());
        $this->assertNull($obj->getEndpoint());
        $this->assertEmpty($obj->getFields());
    }

    public function test_do_not_create_mock_graphql_query_with_empty_query()
    {
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

        new MockGraphqlQuery($query, $variables);
    }
}
