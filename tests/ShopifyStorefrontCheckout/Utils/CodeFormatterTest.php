<?php

namespace Tests\ShopifyStorefrontCheckout\Utils;

use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\ShopifyConstants;
use Irishdistillers\ShopifyStorefrontCheckout\Utils\CostFormatter;
use PHPUnit\Framework\TestCase;
use Tests\ShopifyStorefrontCheckout\Traits\MockCartTrait;

class CodeFormatterTest extends TestCase
{
    use MockCartTrait;

    public function test_get_cost_symbol_for_all_markets()
    {
        $costFormatter = new CostFormatter([]);

        // Currencies in the list
        $this->assertEquals('$', $costFormatter->symbol('AUD'));
        $this->assertEquals('CHF', $costFormatter->symbol('CHF'));
        $this->assertEquals('¥', $costFormatter->symbol('CNY'));
        $this->assertEquals('€', $costFormatter->symbol('EUR'));
        $this->assertEquals('¥', $costFormatter->symbol('JPY'));
        $this->assertEquals('£', $costFormatter->symbol('GBP'));
        $this->assertEquals('$', $costFormatter->symbol('NZD'));
        $this->assertEquals('$', $costFormatter->symbol('SGD'));
        $this->assertEquals('R', $costFormatter->symbol('ZAR'));
        $this->assertEquals('$', $costFormatter->symbol('USD'));

        // Not in the list
        $this->assertEquals('RUB', $costFormatter->symbol('RUB'));
    }

    public function test_format_cost_for_valid_cart()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Get newly created cart
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['lines']['edges']);
        $this->assertEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);

        // Add two items to the cart
        $ret = $cartService->addLines($cart['id'], [
            [$this->getNewVariantId() => 1],
            [$this->getNewVariantId() => 2],
        ]);
        $this->assertNotNull($ret);

        // Get formatted price in euros
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $cost = (new CostFormatter($cart))->estimatedCost();
        $this->assertMatchesRegularExpression('/€[0-9]*\.[0-9]{2}/', $cost);

        // Get formatted price in pounds
        $cart2 = $cartService->getCart($cartId, 'GB');
        $this->assertNotNull($cart2);
        $cost2 = (new CostFormatter($cart2))->estimatedCost();
        $this->assertMatchesRegularExpression('/£[0-9]*\.[0-9]{2}/', $cost2);
    }

    public function test_format_cost_for_valid_cart_without_line_items()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Get newly created cart
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['lines']['edges']);
        $this->assertEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);

        // Get formatted price in euros
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $cost = (new CostFormatter($cart))->estimatedCost();
        $this->assertEquals('€0.0', $cost);

        // Get formatted price in pounds
        $cart2 = $cartService->getCart($cartId, 'GB');
        $this->assertNotNull($cart2);
        $cost2 = (new CostFormatter($cart2))->estimatedCost();
        $this->assertEquals('£0.0', $cost2);
    }

    public function test_do_not_format_cost_for_invalid_cart()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Do not get cost for malformed cart
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        unset($cart['estimatedCost']);
        $cost = (new CostFormatter($cart))->estimatedCost();
        $this->assertEquals('', $cost);

        // Do not get cost for malformed cart
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        unset($cart['estimatedCost']['totalAmount']);
        $cost = (new CostFormatter($cart))->estimatedCost();
        $this->assertEquals('', $cost);

        // Empty cart
        $cost = (new CostFormatter([]))->estimatedCost();
        $this->assertEquals('', $cost);
    }
}
