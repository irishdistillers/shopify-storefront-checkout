<?php

namespace Tests\ShopifyStorefrontCheckout\Traits;

trait AssertCartTrait
{
    protected function cartAssertLineItemCount(array $cart, int $expectedCount)
    {
        $this->assertCount($expectedCount, $cart['lines']['edges']);
    }

    protected function cartAssertLineItem(array $cart, int $lineItemIndex, int $expectedQuantity, array $expectedAttributes = [])
    {
        $lineItem = $cart['lines']['edges'][$lineItemIndex] ?? null;
        if ($lineItem) {
            $lineItemNode = $lineItem['node'];
            $this->assertNotEquals('0.0', $lineItemNode['merchandise']['priceV2']['amount']);
            $this->assertMatchesRegularExpression('/^gid:/', base64_decode($lineItemNode['merchandise']['product']['variants']['edges'][0]['node']['id']));
            $this->assertEquals($expectedQuantity, $lineItemNode['quantity']);
            if (count($expectedAttributes)) {
                $this->assertEquals($expectedAttributes, $lineItemNode['attributes']);
            } else {
                $this->assertEmpty($lineItemNode['attributes']);
            }
        }
    }

    protected function cartAssertTotalIsEmpty(array $cart)
    {
        $this->assertEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
    }

    protected function cartAssertTotalIsSet(array $cart)
    {
        $this->assertNotEquals('0.0', $cart['estimatedCost']['totalAmount']['amount']);
    }
}
