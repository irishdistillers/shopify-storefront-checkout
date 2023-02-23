<?php

namespace Tests\ShopifyStorefrontCheckout\Mock\Shopify;

use Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify\MockMarkets;
use Monolog\Test\TestCase;

class MockMarketsTest extends TestCase
{
    protected function get_expected_markets(): array
    {
        return [
            'FOC',
            'TENPERCENT',
        ];
    }

    public function test_has_market()
    {
        $obj = new MockMarkets();

        $this->assertTrue($obj->has('IE'));
        $this->assertTrue($obj->has('GB'));

        $this->assertFalse($obj->has('DE'));
        $this->assertFalse($obj->has('US'));
    }

    public function test_get_currency_by_market()
    {
        $obj = new MockMarkets();

        $this->assertEquals('EUR', $obj->getCurrency('IE'));
        $this->assertEquals('GBP', $obj->getCurrency('GB'));

        // Fallback (not existing markets)
        $this->assertEquals('EUR', $obj->getCurrency('DE'));
        $this->assertEquals('EUR', $obj->getCurrency('US'));
    }

    public function test_get_price_by_market()
    {
        $obj = new MockMarkets();

        $this->assertEquals(1.33, $obj->getPrice(1.33, 'IE'));
        $this->assertEquals(1.33 * 1.23, $obj->getPrice(1.33, 'GB'));

        // Fallback (not existing markets)
        $this->assertEquals(1.33, $obj->getPrice(1.33, 'DE'));
        $this->assertEquals(1.33, $obj->getPrice(1.33, 'US'));
    }

    public function test_get_vat()
    {
        $obj = new MockMarkets();

        $this->assertEquals(0.22, $obj->getVat('IE'));
        $this->assertEquals(0.23, $obj->getVat('GB'));

        // Fallback (not existing markets)
        $this->assertEquals(0.22, $obj->getVat('DE'));
        $this->assertEquals(0.22, $obj->getVat('US'));
    }
}
