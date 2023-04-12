<?php

namespace Tests\ShopifyStorefrontCheckout\Utils;

use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\ShopifyConstants;
use Tests\ShopifyStorefrontCheckout\TestCase;
use Tests\ShopifyStorefrontCheckout\Traits\MockCartTrait;

class BeautifierTest extends TestCase
{
    use MockCartTrait;

    protected function validateLineItem(array $lineItem)
    {
        $this->assertEquals(['id', 'title', 'product_id', 'variant_id', 'quantity', 'price', 'image'], array_keys($lineItem));
        $this->assertStringStartsWith('gid://shopify/CartLine/', $lineItem['id']);
        $this->assertNotEmpty($lineItem['title']);
        $this->assertStringStartsWith('gid://shopify/Product/', $lineItem['product_id']);
        $this->assertStringStartsWith('gid://shopify/ProductVariant/', $lineItem['variant_id']);
        $this->assertGreaterThan(0, $lineItem['quantity']);
        $this->assertMatchesRegularExpression('/EUR [0-9]*\.[0-9]{2}/', $lineItem['price']);
        $this->assertStringStartsWith('https://cdn.shopify.com/', $lineItem['image']);
    }

    public function test_get_beautifier_for_valid_cart()
    {
        $cartObj = $this->getCart();

        // Create new cart
        $cart = $cartObj->getNewCart();
        $this->assertNotNull($cart);

        // Add two items to the cart
        $ret = $cartObj->addLines([
            [$this->getNewVariantId() => 1],
            [$this->getNewVariantId() => 2],
        ]);
        $this->assertTrue($ret);

        // Add attributes
        $key1 = 'test';
        $value1 = 'Hello world';
        $ret = $cartObj->updateAttributes($key1, $value1);
        $this->assertTrue($ret);

        // Add note
        $note = 'This is a test';
        $ret = $cartObj->updateNote($note);
        $this->assertTrue($ret);

        // Add valid discount code
        $discountCode = 'FOC';
        $ret = $cartObj->updateDiscountCodes([$discountCode]);
        $this->assertTrue($ret);

        // Create beautifier
        $beautifier = $cartObj->beautifier();

        $cartId = $beautifier->getCartId();
        $this->assertNotNull($cartId);
        $this->assertStringStartsWith(ShopifyConstants::GID_CART_PREFIX, $cartId);

        $cartIdWithoutPrefix = $beautifier->getCartIdWithoutPrefix();
        $this->assertNotNull($cartIdWithoutPrefix);
        $this->assertStringStartsNotWith(ShopifyConstants::GID_CART_PREFIX, $cartIdWithoutPrefix);

        $this->assertEquals(ShopifyConstants::DEFAULT_MARKET, $beautifier->getCountryCode());

        $this->assertMatchesRegularExpression('/[A-Z][a-z]{2} [0-9]{1,2}, [0-9]{4} at [0-9]{1,2}:[0-9]{1,2}[apm]{2}/', $beautifier->getCreatedAt());
        $this->assertMatchesRegularExpression('/[A-Z][a-z]{2} [0-9]{1,2}, [0-9]{4} at [0-9]{1,2}:[0-9]{1,2}[apm]{2}/', $beautifier->getUpdatedAt());

        $this->assertMatchesRegularExpression('/https:\/\/[a-z.]*\/cart\/c\/[0-9a-f]{16}/', $beautifier->getCheckoutUrl());

        $this->assertEquals($note, $beautifier->getNote());

        $estimatedCosts = $beautifier->getEstimatedCosts();
        $this->assertNotNull($estimatedCosts);
        $this->assertCount(3, $estimatedCosts);
        $this->assertMatchesRegularExpression('/EUR [0-9]*\.[0-9]{2}/', $estimatedCosts['net']);
        $this->assertMatchesRegularExpression('/EUR [0-9]*\.[0-9]{2}/', $estimatedCosts['tax']);
        $this->assertMatchesRegularExpression('/EUR [0-9]*\.[0-9]{2}/', $estimatedCosts['total']);

        $lineItems = $beautifier->getLineItems(true);
        $this->assertNotNull($lineItems);
        $this->assertCount(2, $lineItems);
        foreach ($lineItems as $lineItem) {
            $this->validateLineItem($lineItem);
        }

        $lineItem = $beautifier->getLineItem($lineItems[0]['id'], true);
        $this->assertNotNull($lineItem);
        $this->validateLineItem($lineItem);

        $attributes = $beautifier->getAttributes();
        $this->assertNotEmpty($attributes);
        $this->assertCount(1, $attributes);
        $this->assertEquals(['key' => $key1, 'value' => $value1], $attributes[0]);

        $discountCodes = $beautifier->getDiscountCodes();
        $this->assertNotEmpty($discountCodes);
        $this->assertCount(1, $discountCodes);
        $this->assertEquals(['code' => $discountCode, 'applicable' => true], $discountCodes[0]);

        $json = $beautifier->json();
        $this->assertNotEmpty($json);
        $this->assertEquals(json_encode($cartObj->getCart(), JSON_PRETTY_PRINT), $json);
    }

    public function test_get_beautifier_for_invalid_cart()
    {
        $cartObj = $this->getCart();

        // Create beautifier
        $beautifier = $cartObj->beautifier();

        $cartId = $beautifier->getCartId();
        $this->assertNull($cartId);

        $cartIdWithoutPrefix = $beautifier->getCartIdWithoutPrefix();
        $this->assertEquals('', $cartIdWithoutPrefix);

        $this->assertEquals('', $beautifier->getCountryCode());

        $this->assertEquals('', $beautifier->getCreatedAt());
        $this->assertEquals('', $beautifier->getUpdatedAt());

        $this->assertEquals('', $beautifier->getCheckoutUrl());

        $this->assertEmpty($beautifier->getNote());

        $estimatedCosts = $beautifier->getEstimatedCosts();
        $this->assertNotNull($estimatedCosts);
        $this->assertCount(3, $estimatedCosts);
        $this->assertEquals('N/A', $estimatedCosts['net']);
        $this->assertEquals('N/A', $estimatedCosts['tax']);
        $this->assertEquals('N/A', $estimatedCosts['total']);

        $lineItems = $beautifier->getLineItems(true);
        $this->assertNotNull($lineItems);
        $this->assertCount(0, $lineItems);

        $lineItem = $beautifier->getLineItem('NOT_EXISTING');
        $this->assertNull($lineItem);

        $attributes = $beautifier->getAttributes();
        $this->assertEmpty($attributes);

        $discountCodes = $beautifier->getDiscountCodes();
        $this->assertEmpty($discountCodes);

        $json = $beautifier->json();
        $this->assertNotEmpty($json);
        $this->assertEquals(json_encode([], JSON_PRETTY_PRINT), $json);
    }
}
