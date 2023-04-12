<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify;

use Exception;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockShopify;
use Irishdistillers\ShopifyStorefrontCheckout\Traits\ShopifyUtilsTrait;

class MockProducts
{
    use ShopifyUtilsTrait;

    const PRODUCT_PREFIX = 'gid://shopify/Product/';

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
     * @param string $productId
     * @param string|null $variantId
     * @return array
     * @throws Exception
     */
    protected function createVariant(string $productId, ?string $variantId = null): array
    {
        return [
            'id' => $variantId ?? $this->shopify->ids()->createRandomId(self::VARIANT_PREFIX),
            'title' => $this->getRandomProductName(),
            'product_id' => $productId,
            'price' => rand(1000, 20000) / 100,
            'currency' => 'EUR',
            'images' => [
                [
                    'id' => $this->shopify->ids()->createRandomId('gid://shopify/ProductImage/'),
                    'src' => 'https://cdn.shopify.com/s/files/'.rand(1, 9).'/'.rand(1111, 9999).'/'.rand(1111, 9999).'/'.rand(1111, 9999).'/products/'.substr(md5(uniqid()), 0, 10).'.png?v='.time(),
                    'altText' => null,
                ],
            ],
        ];
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
        if (! $this->shopify->store()->has(self::VARIANT_PREFIX, $variantId) && $createIfNotExists) {
            $productId = $this->shopify->ids()->createRandomId(self::PRODUCT_PREFIX);
            $this->shopify->store()->set(
                self::VARIANT_PREFIX,
                $variantId,
                $this->createVariant($productId, $variantId)
//                [
//                    'variant_id' => $variantId,
//                    'product_id' => $this->shopify->ids()->createRandomId(self::PRODUCT_PREFIX),
//                    'title' => $this->getRandomProductName(),
//                    'price' => rand(1000, 20000) / 100,
//                    'currency' => 'EUR',
//                    'images' => [
//                        [
//                            'id' => $this->shopify->ids()->createRandomId('gid://shopify/ProductImage/'),
//                            'src' => 'https://cdn.shopify.com/s/files/' . rand(1, 9) . '/' . rand(1111, 9999) . '/' . rand(1111, 9999) . '/' . rand(1111, 9999) . '/products/' . substr(md5(uniqid()), 0, 10) . '.png?v=' . time(),
//                            'altText' => null,
//                        ],
//                    ],
//                ]
            );
        }

        return $this->shopify->store()->get(self::VARIANT_PREFIX, $variantId);
    }

    /**
     * Get product.
     *
     * @param string $entityId
     * @param bool $createIfNotExists
     * @return array|null
     * @throws Exception
     */
    public function getProduct(string $entityId, bool $createIfNotExists = true): ?array
    {
        if (! $this->shopify->store()->has(self::PRODUCT_PREFIX, $entityId) && $createIfNotExists) {
            $productId = $this->shopify->ids()->createRandomId(self::PRODUCT_PREFIX);
            $this->shopify->store()->set(
                self::PRODUCT_PREFIX,
                $entityId,
                [
                    'id' => $productId,
                    'title' => $this->getRandomProductName(),
                    'variants' => [
                        $this->createVariant($productId),
//                        [
//                            'id' => $this->shopify->ids()->createRandomId(self::VARIANT_PREFIX),
//                            'product_id' => $productId,
//                            'price' => rand(1000, 20000) / 100,
//                            'currency' => 'EUR',
//                            'images' => [
//                                [
//                                    'id' => $this->shopify->ids()->createRandomId('gid://shopify/ProductImage/'),
//                                    'src' => 'https://cdn.shopify.com/s/files/' . rand(1, 9) . '/' . rand(1111, 9999) . '/' . rand(1111, 9999) . '/' . rand(1111, 9999) . '/products/' . substr(md5(uniqid()), 0, 10) . '.png?v=' . time(),
//                                    'altText' => null,
//                                ],
//                            ],
//                        ]
                    ],
                ]
            );
        }

        return $this->shopify->store()->get(self::PRODUCT_PREFIX, $entityId);
    }

    /**
     * @param string $variantId
     * @param bool $createIfNotExists
     * @return array|null
     * @throws Exception
     */
    public function getVariant(string $variantId, bool $createIfNotExists = true): ?array
    {
        if (! $this->shopify->store()->has(self::VARIANT_PREFIX, $variantId) && $createIfNotExists) {
            $productId = $this->shopify->ids()->createRandomId(self::PRODUCT_PREFIX);

            return $this->createVariant($productId, $variantId);
        }

        return $this->shopify->store()->get(self::VARIANT_PREFIX, $variantId);
    }
}
