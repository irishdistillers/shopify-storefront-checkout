<?php

namespace Tests\ShopifyStorefrontCheckout;

use Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify\MockStore;
use PHPUnit\Framework\TestCase as PhpunitTestCase;

class TestCase extends PhpunitTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Clear store
        MockStore::clear();
    }
}
