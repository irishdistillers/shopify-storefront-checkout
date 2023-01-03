<?php

namespace Tests\ShopifyStorefrontCheckout;

use Irishdistillers\ShopifyStorefrontCheckout\Cart;
use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\ShopifyConstants;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockGraphql;
use PHPUnit\Framework\TestCase;
use Tests\ShopifyStorefrontCheckout\Traits\AssertCartTrait;
use Tests\ShopifyStorefrontCheckout\Traits\MockCartTrait;

class CartTest extends TestCase
{
    use MockCartTrait, AssertCartTrait;

    protected function getCart(): Cart
    {
        $context = $this->getContext();
        $mock = new MockGraphql($context);

        return new Cart(
            $context,
            null,
            $mock->getEndpoints()
        );
    }

    /**
     * @group shopify_cart
     */
    public function test_get_new_cart_object_for_default_market()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
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
        $this->cartAssertLineItemCount($cart, 0);
        $this->assertNotEmpty($cart['estimatedCost']);
        $this->assertEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
    }

    /**
     * @group shopify_cart
     */
    public function test_get_new_cart_object_for_different_market()
    {
        $cartObj = $this->getCart();

        // Set market
        $cartObj->setCountryCode('GB');

        // Create new cart
        $cart = $cartObj->getNewCart();
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
        $this->cartAssertLineItemCount($cart, 0);
        $this->assertNotEmpty($cart['estimatedCost']);
        $this->assertEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_get_new_cart_object_with_invalid_market()
    {
        $cartObj = $this->getCart();

        // Set market
        $cartObj->setCountryCode('INVALID_MARKET');

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNull($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_get_not_existing_cart_object()
    {
        $cartObj = $this->getCart();

        // Get invalid cart
        $cart = $cartObj->getCart();
        $this->assertNull($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_get_cart_id_for_valid_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);

        $this->assertNotNull($cartObj->getCartId());
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_get_cart_id_for_invalid_cart_object()
    {
        $cartObj = $this->getCart();

        $this->assertNull($cartObj->getCartId());
    }

    /**
     * @group shopify_cart
     */
    public function test_get_country_code_for_valid_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);

        $this->assertEquals(ShopifyConstants::DEFAULT_MARKET, $cartObj->getCountryCode());
    }

    /**
     * @group shopify_cart
     */
    public function test_get_country_code_for_invalid_cart_object()
    {
        $cartObj = $this->getCart();

        $this->assertEquals(ShopifyConstants::DEFAULT_MARKET, $cartObj->getCountryCode());
    }

    /**
     * @group shopify_cart
     */
    public function test_add_single_line_to_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 0);
        $this->cartAssertTotalIsEmpty($cart);

        // Add item to the cart
        $ret = $cartObj->addLine($this->getNewVariantId(), 1);
        $this->assertTrue($ret);

        // Assert that the item was added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 1);
        $this->cartAssertLineItem($cart, 0, 1);
        $this->cartAssertTotalIsSet($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_single_line_with_attributes_to_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 0);
        $this->cartAssertTotalIsEmpty($cart);

        // Add item to the cart
        $ret = $cartObj->addLine($this->getNewVariantId(), 1, ['mvr' => 1]);
        $this->assertTrue($ret);

        // Assert that the item was added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 1);
        $this->cartAssertLineItem($cart, 0, 1, ['mvr' => 1]);
        $this->cartAssertTotalIsSet($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_single_line_to_cart_object_using_variant_already_in_lines()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 0);
        $this->cartAssertTotalIsEmpty($cart);

        $variantId = $this->getNewVariantId();

        // Add item to the cart
        $ret = $cartObj->addLine($variantId, 1);
        $this->assertTrue($ret);

        // Assert that the item was added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 1);
        $this->cartAssertLineItem($cart, 0, 1);
        $this->cartAssertTotalIsSet($cart);

        // Add again the same variant
        $ret = $cartObj->addLine($variantId, 2);
        $this->assertTrue($ret);

        // Assert that the item was updated
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 1);
        $this->cartAssertLineItem($cart, 0, 3);
        $this->cartAssertTotalIsSet($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_add_single_line_to_cart_object_with_invalid_quantity()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 0);
        $this->cartAssertTotalIsEmpty($cart);

        // Add item with invalid quantity to the cart
        $ret = $cartObj->addLine($this->getNewVariantId(), 0);
        $this->assertFalse($ret);

        // Assert that the item was not added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 0);
        $this->cartAssertTotalIsEmpty($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_multiple_lines_to_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 0);
        $this->cartAssertTotalIsEmpty($cart);

        // Add two items to the cart
        $ret = $cartObj->addLines([
            [$this->getNewVariantId() => 1],
            [$this->getNewVariantId() => 2],
        ]);
        $this->assertTrue($ret);

        // Assert that the items were added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 1);
        $this->cartAssertLineItem($cart, 1, 2);
        $this->cartAssertTotalIsSet($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_multiple_lines_with_attributes_to_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 0);
        $this->cartAssertTotalIsEmpty($cart);

        // Add two items to the cart
        $ret = $cartObj->addLines([
            [$this->getNewVariantId() => ['quantity' => 1, 'attributes' => ['mvr' => 1]]],
            [$this->getNewVariantId() => 2],
        ]);
        $this->assertTrue($ret);

        // Assert that the items were added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 1, ['mvr' => 1]);
        $this->cartAssertLineItem($cart, 1, 2);
        $this->cartAssertTotalIsSet($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_update_single_line_in_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 0);
        $this->cartAssertTotalIsEmpty($cart);

        $variantId1 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartObj->addLines([
            [$variantId1 => 1],
            [$this->getNewVariantId() => 2],
        ]);
        $this->assertTrue($ret);

        // Assert that the items were added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 1);
        $this->cartAssertLineItem($cart, 1, 2);
        $this->cartAssertTotalIsSet($cart);

        // Get first line item
        $lineItemId = $cart['lines']['edges'][0]['node']['id'];

        // Update first line item
        $ret = $cartObj->updateLine($lineItemId, 1);
        $this->assertTrue($ret);

        // Assert that the first line item was updated
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 2);
        $this->cartAssertLineItem($cart, 1, 2);
        $this->cartAssertTotalIsSet($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_update_single_line_with_attributes_in_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 0);
        $this->cartAssertTotalIsEmpty($cart);

        $variantId1 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartObj->addLines([
            [$variantId1 => ['quantity' => 1, 'attributes' => ['mvr' => 0]]],
            [$this->getNewVariantId() => 2],
        ]);
        $this->assertTrue($ret);

        // Assert that the items were added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 1, ['mvr' => 0]);
        $this->cartAssertLineItem($cart, 1, 2);
        $this->cartAssertTotalIsSet($cart);

        // Get first line item
        $lineItemId = $cart['lines']['edges'][0]['node']['id'];

        // Update first line item
        $ret = $cartObj->updateLine($lineItemId, 1, ['mvr' => 1]);
        $this->assertTrue($ret);

        // Assert that the first line item was updated
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 2, ['mvr' => 1]);
        $this->cartAssertLineItem($cart, 1, 2);
        $this->cartAssertTotalIsSet($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_update_single_line_in_cart_object_if_not_existing()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 0);
        $this->cartAssertTotalIsEmpty($cart);

        $variantId1 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartObj->addLines([
            [$variantId1 => 1],
            [$this->getNewVariantId() => 2],
        ]);
        $this->assertTrue($ret);

        // Assert that the items were added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 1);
        $this->cartAssertLineItem($cart, 1, 2);
        $this->cartAssertTotalIsSet($cart);

        // Update non-existing line item
        $ret = $cartObj->updateLine($this->getNewLineItemId(), 1);
        $this->assertTrue($ret);

        // Assert that the first line item was not updated
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 1);
        $this->cartAssertLineItem($cart, 1, 2);
        $this->cartAssertTotalIsSet($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_update_multiple_lines_in_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 0);
        $this->cartAssertTotalIsEmpty($cart);

        $variantId1 = $this->getNewVariantId();
        $variantId2 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartObj->addLines([
            [$variantId1 => 1],
            [$variantId2 => 2],
        ]);
        $this->assertTrue($ret);

        // Assert that the items were added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 1);
        $this->cartAssertLineItem($cart, 1, 2);
        $this->cartAssertTotalIsSet($cart);

        // Get line items
        $lineItemId1 = $cartObj->cartService()->decode($cart['lines']['edges'][0]['node']['id']);
        $lineItemId2 = $cartObj->cartService()->decode($cart['lines']['edges'][1]['node']['id']);

        // Update line items
        $ret = $cartObj->updateLines([
            [$lineItemId1 => 1],
            [$lineItemId2 => 2],
        ]);
        $this->assertTrue($ret);

        // Assert that the first line item was updated
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 2);
        $this->cartAssertLineItem($cart, 1, 4);
        $this->cartAssertTotalIsSet($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_update_multiple_lines_with_attributes_in_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 0);
        $this->cartAssertTotalIsEmpty($cart);

        $variantId1 = $this->getNewVariantId();
        $variantId2 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartObj->addLines([
            [$variantId1 => ['quantity' => 1, 'attributes' => ['mvr' => 0]]],
            [$variantId2 => 2],
        ]);
        $this->assertTrue($ret);

        // Assert that the items were added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 1, ['mvr' => 0]);
        $this->cartAssertLineItem($cart, 1, 2);
        $this->cartAssertTotalIsSet($cart);

        // Get line items
        $lineItemId1 = $cartObj->cartService()->decode($cart['lines']['edges'][0]['node']['id']);
        $lineItemId2 = $cartObj->cartService()->decode($cart['lines']['edges'][1]['node']['id']);

        // Update line items
        $ret = $cartObj->updateLines([
            [$lineItemId1 => ['quantity' => 1, 'attributes' => ['mvr' => 1]]],
            [$lineItemId2 => 2],
        ]);
        $this->assertTrue($ret);

        // Assert that the first line item was updated
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 2, ['mvr' => 1]);
        $this->cartAssertLineItem($cart, 1, 4);
        $this->cartAssertTotalIsSet($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_update_multiple_lines_in_the_cart_object_with_invalid_quantity()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 0);
        $this->cartAssertTotalIsEmpty($cart);

        $variantId1 = $this->getNewVariantId();
        $variantId2 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartObj->addLines([
            [$variantId1 => 1],
            [$variantId2 => 2],
        ]);
        $this->assertTrue($ret);

        // Assert that the items were added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 1);
        $this->cartAssertLineItem($cart, 1, 2);
        $this->cartAssertTotalIsSet($cart);

        // Get line item
        $lineItemId1 = $cartObj->cartService()->decode($cart['lines']['edges'][0]['node']['id']);

        // Update line items
        $ret = $cartObj->updateLines([
            [$lineItemId1 => 0], // This won't be updated
        ]);
        $this->assertFalse($ret);

        // Assert that the first line item was not updated
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 1);
        $this->cartAssertLineItem($cart, 1, 2);
        $this->cartAssertTotalIsSet($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_remove_lines_from_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 0);
        $this->cartAssertTotalIsEmpty($cart);

        $variantId1 = $this->getNewVariantId();
        $variantId2 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartObj->addLines([
            [$variantId1 => 1],
            [$variantId2 => 2],
        ]);
        $this->assertTrue($ret);

        // Assert that the items were added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 1);
        $this->cartAssertLineItem($cart, 1, 2);
        $this->cartAssertTotalIsSet($cart);

        // Get line items
        $lineItemId1 = $cartObj->cartService()->decode($cart['lines']['edges'][0]['node']['id']);
        $lineItemId2 = $cartObj->cartService()->decode($cart['lines']['edges'][1]['node']['id']);

        // Remove first line item
        $ret = $cartObj->removeLines([
            $lineItemId1,
        ]);
        $this->assertNotNull($ret);

        // Assert that the first line item was removed
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 1);
        $this->cartAssertLineItem($cart, 0, 2);
        $this->assertEquals($lineItemId2, $cartObj->cartService()->decode($cart['lines']['edges'][0]['node']['id'])); // This was previously second line item
        $this->cartAssertTotalIsSet($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_remove_lines_from_cart_object_if_not_existing()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 0);
        $this->cartAssertTotalIsEmpty($cart);

        $variantId1 = $this->getNewVariantId();
        $variantId2 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartObj->addLines([
            [$variantId1 => 1],
            [$variantId2 => 2],
        ]);
        $this->assertTrue($ret);

        // Assert that the items were added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 1);
        $this->cartAssertLineItem($cart, 1, 2);
        $this->cartAssertTotalIsSet($cart);

        // Remove non-existing line items
        $ret = $cartObj->removeLines([
            $this->getNewLineItemId(),
        ]);
        $this->assertNotNull($ret);

        // Assert that the non-existing line item wasn't removed
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 1);
        $this->cartAssertLineItem($cart, 1, 2);
        $this->cartAssertTotalIsSet($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_empty_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 0);
        $this->cartAssertTotalIsEmpty($cart);

        $variantId1 = $this->getNewVariantId();
        $variantId2 = $this->getNewVariantId();

        // Add two items to the cart
        $ret = $cartObj->addLines([
            [$variantId1 => 1],
            [$variantId2 => 2],
        ]);
        $this->assertTrue($ret);

        // Assert that the items were added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 2);
        $this->cartAssertLineItem($cart, 0, 1);
        $this->cartAssertLineItem($cart, 1, 2);
        $this->cartAssertTotalIsSet($cart);

        // Empty cart
        $ret = $cartObj->emptyCart();
        $this->assertTrue($ret);

        // Assert that all items were removed
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->cartAssertLineItemCount($cart, 0);
        $this->cartAssertTotalIsEmpty($cart);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_note_to_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->assertEmpty($cart['note']);

        // Add note
        $note = 'This is a test';
        $ret = $cartObj->updateNote($note);
        $this->assertTrue($ret);

        // Assert that the note was added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->assertEquals($note, $cart['note']);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_empty_note_to_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->assertEmpty($cart['note']);

        // Add empty note
        $note = '';
        $ret = $cartObj->updateNote($note);
        $this->assertNotNull($ret);

        // Assert that the empty note was added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->assertEquals($note, $cart['note']);
    }

    /**
     * @group shopify_cart
     */
    public function test_update_note_in_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->assertEmpty($cart['note']);

        // Add note
        $note = 'This is a test';
        $ret = $cartObj->updateNote($note);
        $this->assertTrue($ret);

        // Assert that the note was added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->assertEquals($note, $cart['note']);

        // Update note
        $note2 = 'Another test';

        $ret = $cartObj->updateNote($note2);
        $this->assertTrue($ret);

        // Assert that the note was updated
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->assertEquals($note2, $cart['note']);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_attributes_to_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['attributes']);

        // Add attributes
        $key1 = 'test';
        $value1 = 'Hello world';
        $ret = $cartObj->updateAttributes($key1, $value1);
        $this->assertTrue($ret);

        // Assert that the attributes were added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart['attributes']);
        $this->assertEquals(['key' => $key1, 'value' => $value1], $cart['attributes'][0]);

        // Add other attributes
        $key2 = 'hello_world';
        $value2 = 'I am a test';
        $ret = $cartObj->updateAttributes($key2, $value2);
        $this->assertTrue($ret);

        // Assert that the attributes were added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart['attributes']);
        $this->assertEquals(['key' => $key1, 'value' => $value1], $cart['attributes'][0]);
        $this->assertEquals(['key' => $key2, 'value' => $value2], $cart['attributes'][1]);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_valid_discount_code_to_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['discountCodes']);

        // Add valid discount code
        $discountCode = 'FOC';
        $ret = $cartObj->updateDiscountCodes([$discountCode]);
        $this->assertTrue($ret);

        // Assert that the discount code was added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart['discountCodes']);
        $this->assertEquals(['code' => $discountCode, 'applicable' => true], $cart['discountCodes'][0]);
    }

    /**
     * @group shopify_cart
     */
    public function test_add_invalid_discount_code_to_cart()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['discountCodes']);

        // Add invalid discount code
        $discountCode = 'NOT_EXISTING_INVALID';
        $ret = $cartObj->updateDiscountCodes([$discountCode]);
        $this->assertTrue($ret);

        // Assert that the discount code was added
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart['discountCodes']);
        $this->assertEquals(['code' => $discountCode, 'applicable' => false], $cart['discountCodes'][0]);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_add_multiple_discount_codes_to_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);
        $this->assertCount(0, $cart['discountCodes']);

        // Add two discount codes
        $discountCodes = ['FOC', 'NOT_EXISTING_INVALID'];
        $ret = $cartObj->updateDiscountCodes($discountCodes);
        $this->assertNotNull($ret);

        // Assert that only first discount code was added. This is the way Shopify works.
        $cart = $cartObj->getCart();
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart['discountCodes']);
        $this->assertEquals(['code' => $discountCodes[0], 'applicable' => true], $cart['discountCodes'][0]);
    }

    /**
     * @group shopify_cart
     */
    public function test_get_checkout_url_from_the_cart()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);

        // Get checkout URL
        $checkoutUrl = $cartObj->getCheckoutUrl();
        $this->assertNotNull($checkoutUrl);
        $this->assertMatchesRegularExpression('/https:\/\/[a-z.]*\/cart\/c\/[0-9a-f]{16}/', $checkoutUrl);
    }

    /**
     * @group shopify_cart
     */
    public function test_do_not_get_checkout_url_from_invalid_cart()
    {
        $cartObj = $this->getCart();

        // Do not get checkout URL
        $checkoutUrl = $cartObj->getCheckoutUrl();
        $this->assertNull($checkoutUrl);
    }

    /**
     * @group shopify_cart
     */
    public function test_get_last_error_for_valid_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);

        $this->assertNull($cartObj->getLastError());
    }

    /**
     * @group shopify_cart
     */
    public function test_get_last_response_for_valid_cart_object()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);

        $this->assertNull($cartObj->getLastResponse());
    }

    /**
     * @group shopify_cart
     */
    public function test_get_beautifier_for_cart_object()
    {
        $cartObj = $this->getCart();

        $beautifier = $cartObj->beautifier();
        $this->assertNull($beautifier->getCartId());
    }
}
