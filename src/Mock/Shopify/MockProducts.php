<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify;

use Exception;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockShopify;
use Irishdistillers\ShopifyStorefrontCheckout\Traits\ShopifyUtilsTrait;

class MockProducts
{
    use ShopifyUtilsTrait;

    const VARIANT_PREFIX = 'gid://shopify/ProductVariant/';

    protected MockShopify $shopify;

    public function __construct(MockShopify $shopify)
    {
        $this->shopify = $shopify;
    }

    /**
     * Get random product name.
     *
     * @return string
     */
    protected function getRandomProductName(): string
    {
        $pool = [
            ['dry', 'cold', 'icy', 'hot', 'strong', 'light', 'heavy', 'sweet', 'bitter', 'fresh'],
            ['red', 'yellow', 'white', 'pale', 'pink', 'golden', 'black', 'brown', ''],
            ['whiskey', 'gin', 'beer', 'cognac', 'rum', 'ale', 'brandy', 'vodka', 'tequila', 'aquavit'],
        ];

        return ucfirst(str_replace('  ', ' ', implode(' ', [
            $pool[0][array_rand($pool[0])],
            $pool[1][array_rand($pool[1])],
            $pool[2][array_rand($pool[2])],
        ])));
    }

    /**
     * Get product by variant ID.
     *
     * @param string $variantId Variant ID.
     * @param bool $createIfNotExists If true, create product if not existing. Default is true
     * @return array
     * @throws Exception
     */
    public function getProductByVariantId(string $variantId, bool $createIfNotExists = true): ?array
    {
        $product = $this->shopify->store()->get(self::VARIANT_PREFIX, $variantId);
        if (! $product && $createIfNotExists) {
            $this->shopify->store()->set(
                self::VARIANT_PREFIX,
                $variantId,
                [
                    'variant_id' => $variantId,
                    'product_id' => $this->shopify->ids()->createRandomId('gid://shopify/Product/'),
                    'title' => $this->getRandomProductName(),
                    'price' => rand(1000, 20000) / 100,
                    'currency' => 'EUR',
                    'images' => [
                        [
                            'id' => $this->shopify->ids()->createRandomId('gid://shopify/ProductImage/'),
                            'src' => 'https://cdn.shopify.com/s/files/'.rand(1, 9).'/'.rand(1111, 9999).'/'.rand(1111, 9999).'/'.rand(1111, 9999).'/products/'.substr(md5(uniqid()), 0, 10).'.png?v='.time(),
                            'altText' => null,
                        ],
                    ],
                ]
            );
        }

        return $this->shopify->store()->get(self::VARIANT_PREFIX, $variantId);
    }
}
