<?php

namespace Tests\ShopifyStorefrontCheckout\Mock\Shopify;

use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockShopify;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify\MockProducts;
use Tests\ShopifyStorefrontCheckout\TestCase;
use Tests\ShopifyStorefrontCheckout\Traits\MockCartTrait;

class MockProductsTest extends TestCase
{
    use MockCartTrait;

    public function test_get_product_by_variant_id_and_creates_if_not_exists()
    {
        $shopify = new MockShopify($this->getContext());
        $variantId = MockProducts::VARIANT_PREFIX.rand(1000000, 500000);

        $obj = new MockProducts($shopify);

        $product = $obj->getProductByVariantId($variantId);
        $this->assertNotNull($product);
        $this->assertIsArray($product);
        $this->assertEquals([
            'id',
            'title',
            'product_id',
            'price',
            'currency',
            'images',
        ], array_keys($product));
    }

    public function test_get_existing_product_by_variant_id()
    {
        $shopify = new MockShopify($this->getContext());
        $variantId = MockProducts::VARIANT_PREFIX.rand(1000000, 500000);

        $obj = new MockProducts($shopify);

        $this->assertNull($obj->getProductByVariantId($variantId, false));

        // Create product
        $product = $obj->getProductByVariantId($variantId);
        $this->assertNotNull($product);

        // Retrieve existing product
        $product2 = $obj->getProductByVariantId($variantId);
        $this->assertNotNull($product2);
        $this->assertEquals($product2, $product);
    }
}
