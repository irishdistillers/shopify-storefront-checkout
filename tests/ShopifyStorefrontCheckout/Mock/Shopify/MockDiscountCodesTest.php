<?php

namespace Tests\ShopifyStorefrontCheckout\Mock\Shopify;

use Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify\MockDiscountCodes;
use Monolog\Test\TestCase;

class MockDiscountCodesTest extends TestCase
{
    public function test_get_all_mock_discount_codes()
    {
        $expectedDiscountCodes = [
            'FOC',
            'TENPERCENT',
        ];

        $this->assertEquals($expectedDiscountCodes, (new MockDiscountCodes())->all());
    }

    public function test_has_mock_discount_code()
    {
        $obj = new MockDiscountCodes();

        $this->assertTrue($obj->has('FOC'));
        $this->assertTrue($obj->has('TENPERCENT'));

        $this->assertFalse($obj->has('DUMMY'.rand(10000, 99999)));
        $this->assertFalse($obj->has(''));
    }
}
