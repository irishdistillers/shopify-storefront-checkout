<?php

declare(strict_types=1);

namespace Tests\ShopifyStorefrontCheckout;

use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\ShopifyConstants;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockFactory;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;
use Tests\ShopifyStorefrontCheckout\Traits\MockCartTrait;

class CartServiceTest extends TestCase
{
    use MockCartTrait;

    /**
     * @group shopify_cart
     */
    public function test_get_new_cart_with_valid_market()
    {
        $cartService = $this->getCartService();
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_get_new_cart_with_invalid_market()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart('INVALID_MARKET');
        $this->assertNull($cartId);
    }

    /**
     * @group shopify_cart
     */
    public function test_get_cart_for_default_market()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Get newly created cart
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);

        // Assert that cart contains all properties and that is empty
        $this->assertNotEmpty($cart['id']);
        $this->assertNotEmpty($cart['createdAt']);
        $this->assertNotEmpty($cart['updatedAt']);
        $this->assertEquals(['countryCode' => ShopifyConstants::DEFAULT_MARKET], $cart['buyerIdentity']);
        $this->assertNotEmpty($cart['checkoutUrl']);
        $this->assertMatchesRegularExpression('/https:\/\/[a-z.]*\/cart\/c\/[0-9a-f]{16}/', $cart['checkoutUrl']);
        $this->assertCount(0, $cart['attributes']);
        $this->assertCount(0, $cart['discountCodes']);
        $this->assertEmpty($cart['note']);
        $this->assertCount(0, $cart['lines']['edges']);
        $this->assertNotEmpty($cart['estimatedCost']);
        $this->assertEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
    }

    /**
     * @group shopify_cart
     */
    public function test_get_cart_for_different_market()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Get newly created cart
        $cart = $cartService->getCart($cartId, 'GB');
        $this->assertNotNull($cart);

        // Assert that cart contains all properties and that is empty
        $this->assertNotEmpty($cart['id']);
        $this->assertNotEmpty($cart['createdAt']);
        $this->assertNotEmpty($cart['updatedAt']);
        $this->assertEquals(['countryCode' => 'GB'], $cart['buyerIdentity']);
        $this->assertNotEmpty($cart['checkoutUrl']);
        $this->assertMatchesRegularExpression('/https:\/\/[a-z.]*\/cart\/c\/[0-9a-f]{16}/', $cart['checkoutUrl']);
        $this->assertCount(0, $cart['attributes']);
        $this->assertCount(0, $cart['discountCodes']);
        $this->assertEmpty($cart['note']);
        $this->assertCount(0, $cart['lines']['edges']);
        $this->assertNotEmpty($cart['estimatedCost']);
        $this->assertEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_get_non_existing_cart()
    {
        $cartService = $this->getCartService();

        // Get invalid cart
        $cart = $cartService->getCart($this->getRandomCartId(), ShopifyConstants::DEFAULT_MARKET, false);
        $this->assertNull($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_single_line_to_cart()
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

        // Add item to the cart
        $ret = $cartService->addLine($cart['id'], $this->getNewVariantId(), 1);
        $this->assertNotNull($ret);

        // Assert that the item was added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
        $this->assertCount(0, $cart['lines']['edges'][0]['node']['attributes']);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_single_line_with_attributes_to_cart()
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

        // Prepare attributes
        $attributes = ['test' => '123'];

        // Add item to the cart
        $ret = $cartService->addLine($cart['id'], $this->getNewVariantId(), 1, $attributes);
        $this->assertNotNull($ret);

        // Assert that the item was added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
        $this->assertCount(1, $cart['lines']['edges'][0]['node']['attributes']);
        $this->assertEquals([['key' => 'test', 'value' => 123]], $cart['lines']['edges'][0]['node']['attributes']);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_single_line_with_attributes_and_selling_plan_to_cart()
    {
        $cartService = $this->getCartService();

        $sellingPlanId = $this->getRandomSellingCartId();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Get newly created cart
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['lines']['edges']);
        $this->assertEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);

        // Prepare attributes
        $attributes = ['test' => '123'];

        // Add item to the cart
        $ret = $cartService->addLine($cart['id'], $this->getNewVariantId(), 1, $attributes, $sellingPlanId);
        $this->assertNotNull($ret);

        // Assert that the item was added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
        $this->assertCount(1, $cart['lines']['edges'][0]['node']['attributes']);
        $this->assertEquals([['key' => 'test', 'value' => 123]], $cart['lines']['edges'][0]['node']['attributes']);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_single_line_to_cart_using_variant_already_in_lines()
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

        $variantId = $this->getNewVariantId();

        // Add item to the cart
        $ret = $cartService->addLine($cart['id'], $variantId, 1);
        $this->assertNotNull($ret);

        // Assert that the item was added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);

        // Add again the same variant
        $ret = $cartService->addLine($cart['id'], $variantId, 2);
        $this->assertNotNull($ret);

        // Assert that the item was updated
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(3, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_add_single_line_to_cart_with_invalid_quantity()
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

        // Add item with invalid quantity to the cart
        $ret = $cartService->addLine($cart['id'], $this->getNewVariantId(), 0);
        $this->assertNull($ret);

        // Assert that the item was not added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['lines']['edges']);
        $this->assertEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_add_single_line_to_non_existing_cart()
    {
        $cartService = $this->getCartService();

        // Add item to the cart
        $ret = $cartService->addLine($this->getRandomCartId(), $this->getNewVariantId(), 1);
        $this->assertNull($ret);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_multiple_lines_to_cart()
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

        // Assert that the items were added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertCount(0, $cart['lines']['edges'][0]['node']['attributes']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
        $this->assertCount(0, $cart['lines']['edges'][1]['node']['attributes']);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_multiple_lines_to_cart_with_attributes()
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
            [
                $this->getNewVariantId() => 1,
            ],
            [
                $this->getNewVariantId() => [
                    'quantity' => 2,
                    'attributes' => [
                        'a' => 'b',
                    ],
                ],
            ],
        ]);
        $this->assertNotNull($ret);

        // Assert that the items were added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertCount(0, $cart['lines']['edges'][0]['node']['attributes']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
        $this->assertCount(1, $cart['lines']['edges'][1]['node']['attributes']);
        $this->assertEquals([['key' => 'a', 'value' => 'b']], $cart['lines']['edges'][1]['node']['attributes']);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_add_multiple_lines_to_non_existing_cart()
    {
        $cartService = $this->getCartService();

        $ret = $cartService->addLines($this->getRandomCartId(), [
            [$this->getNewVariantId() => 1],
            [$this->getNewVariantId() => 2],
        ]);
        $this->assertNull($ret);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_add_multiple_lines_to_invalid_cart()
    {
        $cartService = $this->getCartService();

        $ret = $cartService->addLines(null, [
            [$this->getNewVariantId() => 1],
            [$this->getNewVariantId() => 2],
        ]);
        $this->assertNull($ret);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_add_multiple_empty_lines_to_cart()
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
        $ret = $cartService->addLines($cart['id'], []);
        $this->assertNull($ret);

        // Assert that the items were added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['lines']['edges']);
    }

    /**
     * @group shopify_cart
     */
    public function test_update_single_line_in_the_cart()
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

        $variantId1 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartService->addLines($cart['id'], [
            [$variantId1 => 1],
            [$this->getNewVariantId() => 2],
        ]);
        $this->assertNotNull($ret);

        // Assert that the items were added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);

        // Get first line item
        $lineItemId = $cart['lines']['edges'][0]['node']['id'];

        // Update first line item
        $ret = $cartService->updateLine($cart['id'], $lineItemId, 1);
        $this->assertNotNull($ret);

        // Assert that the first line item was updated
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
    }

    /**
     * @group shopify_cart
     */
    public function test_update_single_line_in_the_cart_with_attributes()
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

        $variantId1 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartService->addLines($cart['id'], [
            [
                $variantId1 => [
                    'quantity' => 1,
                    'attributes' => [
                        'a' => 'b',
                    ],
                ],
            ],
            [
                $this->getNewVariantId() => 2,
            ],
        ]);
        $this->assertNotNull($ret);

        // Assert that the items were added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertCount(1, $cart['lines']['edges'][0]['node']['attributes']);
        $this->assertEquals([['key' => 'a', 'value' => 'b']], $cart['lines']['edges'][0]['node']['attributes']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
        $this->assertCount(0, $cart['lines']['edges'][1]['node']['attributes']);

        // Get first line item
        $lineItemId = $cart['lines']['edges'][0]['node']['id'];

        // Update first line item
        $ret = $cartService->updateLine(
            $cart['id'],
            $lineItemId,
            1,
            ['a' => 'c']
        );
        $this->assertNotNull($ret);

        // Assert that the first line item was updated
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
        $this->assertCount(1, $cart['lines']['edges'][0]['node']['attributes']);
        $this->assertEquals([['key' => 'a', 'value' => 'c']], $cart['lines']['edges'][0]['node']['attributes']);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_update_single_line_in_the_cart_if_not_existing()
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

        $variantId1 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartService->addLines($cart['id'], [
            [$variantId1 => 1],
            [$this->getNewVariantId() => 2],
        ]);
        $this->assertNotNull($ret);

        // Assert that the items were added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);

        // Update non-existing line item
        $ret = $cartService->updateLine($cart['id'], $this->getNewLineItemId(), 1);
        $this->assertNotNull($ret);

        // Assert that the first line item wasn't updated
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
    }

    /**
     * @group shopify_cart
     */
    public function test_update_multiple_lines_in_the_cart()
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

        $variantId1 = $this->getNewVariantId();
        $variantId2 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartService->addLines($cart['id'], [
            [$variantId1 => 1],
            [$variantId2 => 2],
        ]);
        $this->assertNotNull($ret);

        // Assert that the items were added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);

        // Get line items
        $lineItemId1 = $cart['lines']['edges'][0]['node']['id'];
        $lineItemId2 = $cart['lines']['edges'][1]['node']['id'];

        // Update line items
        $ret = $cartService->updateLines($cart['id'], [
            [$lineItemId1 => 1],
            [$lineItemId2 => 2],
        ]);
        $this->assertNotNull($ret);

        // Assert that the first line item was updated
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(4, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
    }

    /**
     * @group shopify_cart
     */
    public function test_update_multiple_lines_in_the_cart_with_attributes()
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

        $variantId1 = $this->getNewVariantId();
        $variantId2 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartService->addLines($cart['id'], [
            [
                $variantId1 => [
                    'quantity' => 1,
                    'attributes' => [
                        'a' => 'b',
                    ],
                ],
            ],
            [
                $this->getNewVariantId() => 2,
            ],
        ]);
        $this->assertNotNull($ret);

        // Assert that the items were added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertCount(1, $cart['lines']['edges'][0]['node']['attributes']);
        $this->assertEquals([['key' => 'a', 'value' => 'b']], $cart['lines']['edges'][0]['node']['attributes']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
        $this->assertCount(0, $cart['lines']['edges'][1]['node']['attributes']);

        // Get line items
        $lineItemId1 = $cart['lines']['edges'][0]['node']['id'];
        $lineItemId2 = $cart['lines']['edges'][1]['node']['id'];

        // Update line items
        $ret = $cartService->updateLines($cart['id'], [
            [
                $lineItemId1 => [
                    'quantity' => 1,
                    'attributes' => ['a' => 'c'],
                ],
            ],
            [
                $lineItemId2 => 2,
            ],
        ]);
        $this->assertNotNull($ret);

        // Assert that the first line item was updated
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(4, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
        $this->assertCount(1, $cart['lines']['edges'][0]['node']['attributes']);
        $this->assertEquals([['key' => 'a', 'value' => 'c']], $cart['lines']['edges'][0]['node']['attributes']);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_update_multiple_lines_in_the_cart_with_invalid_quantity()
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

        $variantId1 = $this->getNewVariantId();
        $variantId2 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartService->addLines($cart['id'], [
            [$variantId1 => 1],
            [$variantId2 => 2],
        ]);
        $this->assertNotNull($ret);

        // Assert that the items were added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);

        // Get line item
        $lineItemId1 = $cart['lines']['edges'][0]['node']['id'];

        // Update line items
        $ret = $cartService->updateLines($cart['id'], [
            [$lineItemId1 => 0], // This won't be updated
        ]);
        $this->assertNull($ret);

        // Assert that the first line item was updated
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_update_multiple_empty_lines_in_the_cart()
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

        $variantId1 = $this->getNewVariantId();
        $variantId2 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartService->addLines($cart['id'], [
            [$variantId1 => 1],
            [$variantId2 => 2],
        ]);
        $this->assertNotNull($ret);

        // Assert that the items were added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);

        // Update line items
        $ret = $cartService->updateLines($cart['id'], []);
        $this->assertNull($ret);

        // Assert that the line items weren't updated
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_update_multiple_lines_in_non_existing_cart()
    {
        $cartService = $this->getCartService();

        // Add two items to the cart
        $ret = $cartService->updateLines(null, [
            [$this->getNewLineItemId() => 1],
            [$this->getNewLineItemId() => 2],
        ]);
        $this->assertNull($ret);
    }

    /**
     * @group shopify_cart
     */
    public function test_remove_lines_from_cart()
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

        $variantId1 = $this->getNewVariantId();
        $variantId2 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartService->addLines($cart['id'], [
            [$variantId1 => 1],
            [$variantId2 => 2],
        ]);
        $this->assertNotNull($ret);

        // Assert that the items were added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);

        // Get line items
        $lineItemId1 = $cart['lines']['edges'][0]['node']['id'];
        $lineItemId2 = $cart['lines']['edges'][1]['node']['id'];

        // Remove first line item
        $ret = $cartService->removeLines($cart['id'], [
            $lineItemId1,
        ]);
        $this->assertNotNull($ret);

        // Assert that the first line item was removed
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertEquals($lineItemId2, $cart['lines']['edges'][0]['node']['id']); // This was previously second line item
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_remove_lines_from_cart_if_not_existing()
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

        $variantId1 = $this->getNewVariantId();
        $variantId2 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartService->addLines($cart['id'], [
            [$variantId1 => 1],
            [$variantId2 => 2],
        ]);
        $this->assertNotNull($ret);

        // Assert that the items were added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);

        // Remove non-existing line items
        $ret = $cartService->removeLines($cart['id'], [
            $this->getNewLineItemId(),
        ]);
        $this->assertNotNull($ret);

        // Assert that the non-existing line item wasn't removed
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_remove_lines_from_non_existing_cart()
    {
        $cartService = $this->getCartService();

        $ret = $cartService->removeLines($this->getRandomCartId(), [
            $this->getNewLineItemId(),
        ]);
        $this->assertNull($ret);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_remove_lines_from_invalid_cart()
    {
        $cartService = $this->getCartService();

        $ret = $cartService->removeLines(null, [
            $this->getNewLineItemId(),
        ]);
        $this->assertNull($ret);
    }

    public function test_do_no_remove_invalid_lines()
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

        $variantId1 = $this->getNewVariantId();
        $variantId2 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartService->addLines($cart['id'], [
            [$variantId1 => 1],
            [$variantId2 => 2],
        ]);
        $this->assertNotNull($ret);

        // Assert that the items were added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);

        // Remove non-existing line items
        $ret = $cartService->removeLines($cart['id'], [
            null,
        ]);
        $this->assertNull($ret);
    }

    /**
     * @group shopify_cart
     */
    public function test_empty_cart()
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

        $variantId1 = $this->getNewVariantId();
        $variantId2 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartService->addLines($cart['id'], [
            [$variantId1 => 1],
            [$variantId2 => 2],
        ]);
        $this->assertNotNull($ret);

        // Assert that the items were added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['lines']['edges']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][0]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(1, $cart['lines']['edges'][0]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['lines']['edges'][1]['node']['merchandise']['priceV2']['amount']);
        $this->assertEquals(2, $cart['lines']['edges'][1]['node']['quantity']);
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);

        // Empty cart
        $ret = $cartService->emptyCart($cart['id'], ShopifyConstants::DEFAULT_MARKET);
        $this->assertTrue($ret);

        // Assert that all items were removed
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['lines']['edges']);
        $this->assertEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_note_to_cart()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Get newly created cart
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertEmpty($cart['note']);

        // Add note
        $note = 'This is a test';
        $ret = $cartService->updateNote($cart['id'], $note);
        $this->assertNotNull($ret);

        // Assert that the note was added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertEquals($note, $cart['note']);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_empty_note_to_cart()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Get newly created cart
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertEmpty($cart['note']);

        // Add empty note
        $note = '';
        $ret = $cartService->updateNote($cart['id'], $note);
        $this->assertNotNull($ret);

        // Assert that the empty note was added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertEquals($note, $cart['note']);
    }

    /**
     * @group shopify_cart
     */
    public function test_update_note_in_the_cart()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Get newly created cart
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertEmpty($cart['note']);

        // Add note
        $note = 'This is a test';
        $ret = $cartService->updateNote($cart['id'], $note);
        $this->assertNotNull($ret);

        // Assert that the note was added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertEquals($note, $cart['note']);

        // Update note
        $note2 = 'Another test';

        $ret = $cartService->updateNote($cart['id'], $note2);
        $this->assertNotNull($ret);

        // Assert that the note was updated
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertEquals($note2, $cart['note']);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_update_note_in_non_existing_cart()
    {
        $cartService = $this->getCartService();

        $note = 'This is a test';
        $ret = $cartService->updateNote($this->getRandomCartId(), $note);
        $this->assertNull($ret);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_attributes_to_cart()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Get newly created cart
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['attributes']);

        // Add attributes
        $key1 = 'test';
        $value1 = 'Hello world';
        $ret = $cartService->updateAttributes($cart['id'], $key1, $value1);
        $this->assertNotNull($ret);

        // Assert that the attributes were added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart['attributes']);
        $this->assertEquals(['key' => $key1, 'value' => $value1], $cart['attributes'][0]);

        // Add other attributes
        $key2 = 'hello_world';
        $value2 = 'I am a test';
        $ret = $cartService->updateAttributes($cart['id'], $key2, $value2);
        $this->assertNotNull($ret);

        // Assert that the attributes were added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['attributes']);
        $this->assertEquals(['key' => $key1, 'value' => $value1], $cart['attributes'][0]);
        $this->assertEquals(['key' => $key2, 'value' => $value2], $cart['attributes'][1]);
    }

    /**
     * @group shopify_cart
     */
    public function test_update_attributes_in_the_cart()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Get newly created cart
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['attributes']);

        // Add attributes
        $key1 = 'test';
        $value1 = 'Hello world';
        $ret = $cartService->updateAttributes($cart['id'], $key1, $value1);
        $this->assertNotNull($ret);

        // Assert that the attributes were added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart['attributes']);
        $this->assertEquals(['key' => $key1, 'value' => $value1], $cart['attributes'][0]);

        // Update attributes. Shopify is not case-sensitive, so this will update previously added attributes.
        $key2 = 'Test';
        $value2 = 'I am a test';
        $ret = $cartService->updateAttributes($cart['id'], $key2, $value2);
        $this->assertNotNull($ret);

        // Assert that the attributes were updated
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart['attributes']);
        $this->assertEquals(['key' => $key2, 'value' => $value2], $cart['attributes'][0]);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_add_attributes_to_non_existing_cart()
    {
        $cartService = $this->getCartService();

        // Add attributes
        $key1 = 'test';
        $value1 = 'Hello world';
        $ret = $cartService->updateAttributes($this->getRandomCartId(), $key1, $value1);
        $this->assertNull($ret);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_add_invalid_attributes_to_cart()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Get newly created cart
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['attributes']);

        // Add attributes
        $key1 = '';
        $value1 = 'Hello world';
        $ret = $cartService->updateAttributes($cart['id'], $key1, $value1);
        $this->assertNull($ret);

        // Assert that the attributes were not added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['attributes']);

        // Add again attributes
        $key2 = 'test';
        $value2 = '';
        $ret = $cartService->updateAttributes($cart['id'], $key2, $value2);
        $this->assertNull($ret);

        // Assert that the attributes were not added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['attributes']);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_valid_discount_code_to_cart()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Get newly created cart
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['discountCodes']);

        // Add valid discount code
        $discountCode = 'FOC';
        $ret = $cartService->updateDiscountCodes($cart['id'], [$discountCode]);
        $this->assertNotNull($ret);

        // Assert that the discount code was added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart['discountCodes']);
        $this->assertEquals(['code' => $discountCode, 'applicable' => true], $cart['discountCodes'][0]);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_invalid_discount_code_to_cart()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Get newly created cart
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['discountCodes']);

        // Add invalid discount code
        $discountCode = 'NOT_EXISTING_INVALID';
        $ret = $cartService->updateDiscountCodes($cart['id'], [$discountCode]);
        $this->assertNotNull($ret);

        // Assert that the discount code was added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart['discountCodes']);
        $this->assertEquals(['code' => $discountCode, 'applicable' => false], $cart['discountCodes'][0]);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_add_multiple_discount_codes_to_cart()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Get newly created cart
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['discountCodes']);

        // Add two discount codes
        $discountCodes = ['FOC', 'NOT_EXISTING_INVALID'];
        $ret = $cartService->updateDiscountCodes($cart['id'], $discountCodes);
        $this->assertNotNull($ret);

        // Assert that only first discount code was added. This is the way Shopify works.
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart['discountCodes']);
        $this->assertEquals(['code' => $discountCodes[0], 'applicable' => true], $cart['discountCodes'][0]);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_add_valid_discount_code_to_non_existing_cart()
    {
        $cartService = $this->getCartService();

        // Add valid discount code
        $discountCode = 'FOC';
        $ret = $cartService->updateDiscountCodes($this->getRandomCartId(), [$discountCode]);
        $this->assertNull($ret);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_add_empty_discount_code_to_cart()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Get newly created cart
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['discountCodes']);

        // Add empty discount code
        $ret = $cartService->updateDiscountCodes($cart['id'], []);
        $this->assertNull($ret);

        // Assert that the discount code was not added
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['discountCodes']);
    }

    /**
     * @group shopify_cart
     */
    public function test_get_checkout_url_from_the_cart()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Get newly created cart
        $cart = $cartService->getCart($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cart);

        // Get checkout URL
        $checkoutUrl = $cartService->getCheckoutUrl($cartId, ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($checkoutUrl);
        $this->assertMatchesRegularExpression('/https:\/\/[a-z.]*\/cart\/c\/[0-9a-f]{16}/', $checkoutUrl);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_get_checkout_url_from_invalid_cart()
    {
        $cartService = $this->getCartService();

        // Do not get checkout URL
        $checkoutUrl = $cartService->getCheckoutUrl($this->getRandomCartId(), ShopifyConstants::DEFAULT_MARKET);
        $this->assertNull($checkoutUrl);
    }

    /**
     * @group shopify_cart
     */
    public function test_get_checkout_url_from_the_cart_with_mock_enabled()
    {
        $cartService = $this->getCartService(new MockFactory());

        $checkoutUrl = $cartService->getCheckoutUrl($this->getRandomCartId(), ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($checkoutUrl);
    }

    /**
     * @group shopify_cart
     */
    public function test_check_if_cart_exists()
    {
        $cartService = $this->getCartService();

        // Create cart
        $cartId = $cartService->getNewCart(ShopifyConstants::DEFAULT_MARKET);
        $this->assertNotNull($cartId);

        // Assert that valid cart exists
        $this->assertTrue($cartService->cartExists($cartId, ShopifyConstants::DEFAULT_MARKET));

        // Assert that invalid cart does not exist
        $this->assertFalse($cartService->cartExists($this->getRandomCartId(), ShopifyConstants::DEFAULT_MARKET));
    }

    /**
     * @group shopify_cart
     */
    public function test_get_cart_context()
    {
        $cartService = $this->getCartService();

        $context = $cartService->getContext();
        $this->assertInstanceOf(Context::class, $context);
    }
}
