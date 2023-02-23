<?php

namespace Tests\ShopifyStorefrontCheckout\Mock\Shopify;

use Exception;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify\MockIds;
use Monolog\Test\TestCase;

class MockIdsTest extends TestCase
{
    public function test_create_mock_random_id()
    {
        $obj = new MockIds();

        $prefix = 'TEST';

        $this->assertMatchesRegularExpression("/{$prefix}\/[0-9a-z]{16}/", $obj->createRandomId($prefix));
    }

    public function test_do_not_create_mock_random_id_with_empty_prefix()
    {
        $obj = new MockIds();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to generate random ID: prefix is empty');

        $obj->createRandomId('');
    }
}
