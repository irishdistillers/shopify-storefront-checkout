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

        $this->assertTrue($obj->has('AU'));
        $this->assertTrue($obj->has('CH'));
        $this->assertTrue($obj->has('CN'));
        $this->assertTrue($obj->has('DE'));
        $this->assertTrue($obj->has('FR'));
        $this->assertTrue($obj->has('IE'));
        $this->assertTrue($obj->has('GB'));
        $this->assertTrue($obj->has('JP'));
        $this->assertTrue($obj->has('NZ'));
        $this->assertTrue($obj->has('SG'));
        $this->assertTrue($obj->has('ZA'));

        $this->assertFalse($obj->has('IT'));
        $this->assertFalse($obj->has('US'));
    }

    public function test_get_currency_by_market()
    {
        $obj = new MockMarkets();

        $this->assertEquals('AUD', $obj->getCurrency('AU'));
        $this->assertEquals('CHF', $obj->getCurrency('CH'));
        $this->assertEquals('CNY', $obj->getCurrency('CN'));
        $this->assertEquals('EUR', $obj->getCurrency('DE'));
        $this->assertEquals('EUR', $obj->getCurrency('FR'));
        $this->assertEquals('EUR', $obj->getCurrency('IE'));
        $this->assertEquals('GBP', $obj->getCurrency('GB'));
        $this->assertEquals('JPY', $obj->getCurrency('JP'));
        $this->assertEquals('NZD', $obj->getCurrency('NZ'));
        $this->assertEquals('SGD', $obj->getCurrency('SG'));
        $this->assertEquals('ZAR', $obj->getCurrency('ZA'));

        // Fallback (not existing markets)
        $this->assertEquals('EUR', $obj->getCurrency('IT'));
        $this->assertEquals('EUR', $obj->getCurrency('US'));
    }

    public function test_get_price_by_market()
    {
        $obj = new MockMarkets();

        $this->assertEquals(1.33 * 1.10, $obj->getPrice(1.33, 'AU'));
        $this->assertEquals(1.33 * 1.077, $obj->getPrice(1.33, 'CH'));
        $this->assertEquals(1.33 * 1.13, $obj->getPrice(1.33, 'CN'));
        $this->assertEquals(1.33 * 1.19, $obj->getPrice(1.33, 'DE'));
        $this->assertEquals(1.33 * 1.20, $obj->getPrice(1.33, 'FR'));
        $this->assertEquals(1.33, $obj->getPrice(1.33, 'IE'));
        $this->assertEquals(1.33 * 1.23, $obj->getPrice(1.33, 'GB'));
        $this->assertEquals(1.33 * 1.10, $obj->getPrice(1.33, 'JP'));
        $this->assertEquals(1.33 * 1.15, $obj->getPrice(1.33, 'NZ'));
        $this->assertEquals(1.33 * 1.07, $obj->getPrice(1.33, 'SG'));
        $this->assertEquals(1.33 * 1.15, $obj->getPrice(1.33, 'ZA'));

        // Fallback (not existing markets)
        $this->assertEquals(1.33, $obj->getPrice(1.33, 'IT'));
        $this->assertEquals(1.33, $obj->getPrice(1.33, 'US'));
    }

    public function test_get_vat()
    {
        $obj = new MockMarkets();

        $this->assertEquals(0.10, $obj->getVat('AU'));
        $this->assertEquals(0.077, $obj->getVat('CH'));
        $this->assertEquals(0.13, $obj->getVat('CN'));
        $this->assertEquals(0.19, $obj->getVat('DE'));
        $this->assertEquals(0.20, $obj->getVat('FR'));
        $this->assertEquals(0.22, $obj->getVat('IE'));
        $this->assertEquals(0.23, $obj->getVat('GB'));
        $this->assertEquals(0.10, $obj->getVat('JP'));
        $this->assertEquals(0.15, $obj->getVat('NZ'));
        $this->assertEquals(0.07, $obj->getVat('SG'));
        $this->assertEquals(0.15, $obj->getVat('ZA'));

        // Fallback (not existing markets)
        $this->assertEquals(0.22, $obj->getVat('IT'));
        $this->assertEquals(0.22, $obj->getVat('US'));
    }
}
