<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify;

use Exception;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockShopify;
use Irishdistillers\ShopifyStorefrontCheckout\Traits\ShopifyUtilsTrait;

class MockCart
{
    use ShopifyUtilsTrait;

    protected MockShopify $shopify;

    protected const CART_PREFIX = 'gid://shopify/Cart/';

    protected const CART_LINE_PREFIX = 'gid://shopify/CartLine/';

    public function __construct(MockShopify $shopify)
    {
        $this->shopify = $shopify;
    }

    /**
     * Format price as string, according to Shopify way.
     *
     * @param float $price
     * @param string $currency
     * @return array
     */
    protected function formatPrice(float $price, string $currency): array
    {
        return [
            // If price is 0, Shopify formats as "0.0"
            'amount' => ! $price ? sprintf('%1.1f', $price) : sprintf('%1.2f', $price),
            'currencyCode' => $currency,
        ];
    }

    /**
     * Update cart prices by market.
     *
     * @param string $cartId
     * @param string $countryCode
     * @return array|null
     * @throws Exception
     */
    protected function updateCartPricesByMarket(string $cartId, string $countryCode): ?array
    {
        // Get cart
        $cart = $this->shopify->store()->get(self::CART_PREFIX, $cartId);

        if (! $cart) {
            return null;
        }

        $netAmount = 0.0;
        $currency = $this->shopify->market()->getCurrency($countryCode);

        // Update buyer identity
        $cart['buyerIdentity']['countryCode'] = $countryCode;

        // Loop line items
        foreach ($cart['lines']['edges'] as $index => $line) {
            $node = $line['node'] ?? null;
            if ($node) {
                // Get random product
                $product = $this->shopify->products()->getProductByVariantId($node['id']);

                // Calculate price for default market
                $price = $this->shopify->market()->getPrice($product['price'], $countryCode);

                // Get line item quantity
                $quantity = $node['quantity'];

                // Update net amount
                $netAmount += $price * $quantity;

                // Update product prices
                $node['estimatedCost']['totalAmount'] = $node['estimatedCost']['subtotalAmount'] = $this->formatPrice($price * $quantity, $currency)['amount'];
                $node['merchandise']['priceV2'] = $this->formatPrice($price, $currency);

                // Update cart lines
                $cart['lines']['edges'][$index]['node'] = $node;
            }
        }

        // Recalculate totals
        $totalTaxAmount = $netAmount * $this->shopify->market()->getVat($countryCode);
        $totalAmount = $netAmount + $totalTaxAmount;
        $cart['estimatedCost']['totalAmount'] = $this->formatPrice($totalAmount, $currency);
        $cart['estimatedCost']['subtotalAmount'] = $this->formatPrice($netAmount, $currency);
        $cart['estimatedCost']['totalTaxAmount'] = $this->formatPrice($totalTaxAmount, $currency);

        // Store updated cart
        $this->shopify->store()->set(self::CART_PREFIX, $cartId, $cart);

        return $cart;
    }

    /**
     * Add line item. If already existing, update it.
     *
     * @param array|null $cart
     * @param string $currency
     * @param string $variantId
     * @param int $quantity
     * @return array|null
     * @throws Exception
     */
    protected function addOrUpdateLineItem(?array $cart, string $currency, string $variantId, int $quantity, array $attributes): ?array
    {
        if (! $cart) {
            return $cart;
        }

        // If variant ID is already in the cart, update it
        $variantIsAlreadyInCartLines = false;
        if (count($cart['lines']['edges'])) {
            foreach ($cart['lines']['edges'] as $index => $edge) {
                $node = $edge['node'] ?? null;
                if ($node) {
                    if ($this->decode($node['merchandise']['id']) === $variantId) {
                        // Update quantity
                        $cart['lines']['edges'][$index]['node']['quantity'] += $quantity;
                        // Update attributes
                        $cart['lines']['edges'][$index]['node']['attributes'] = array_merge(
                            $cart['lines']['edges'][$index]['node']['attributes'],
                            $attributes
                        );
                        $variantIsAlreadyInCartLines = true;
                        break;
                    }
                }
            }
        }

        // If the variant ID was already in the cart, return it
        if ($variantIsAlreadyInCartLines) {
            return $cart;
        }

        // Get random product
        $product = $this->shopify->products()->getProductByVariantId($variantId);

        // Prepare product images
        $productImages = $product['images'][0];
        $productImages['id'] = $this->encode($productImages['id']);

        $cart['lines']['edges'][] = [
            'node' => [
                'id' => $this->encode($this->shopify->ids()->createRandomId(self::CART_LINE_PREFIX)),
                'attributes' => $attributes,
                'quantity' => $quantity,
                'discountAllocations' => [],
                'estimatedCost' => [
                    'subtotalAmount' => [
                        'amount' => '0.0', // Price is calculated later
                    ],
                    'totalAmount' => [
                        'amount' => '0.0', // Price is calculated later
                    ],
                ],
                'merchandise' => [
                    'id' => $this->encode($variantId),
                    'title' => 'Default Title',
                    'priceV2' => [
                        'amount' => '0.0', // Price is calculated later
                        'currency_code' => $currency,
                    ],
                    'product' => [
                        'id' => $this->encode($product['product_id']),
                        'availableForSale' => true,
                        'variants' => [
                            'edges' => [
                                [
                                    'node' => [
                                        'id' => $this->encode($variantId),
                                    ],
                                ],
                            ],
                        ],
                        'title' => $product['title'],
                        'images' => [
                            'edges' => [
                                [
                                    'node' => $product['images'][0],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $cart;
    }

    /**
     * Update line item.
     *
     * @param array|null $cart
     * @param string $lineItemId
     * @param int $quantity
     * @param array $attributes
     * @return array|null
     */
    protected function updateLineItem(?array $cart, string $lineItemId, int $quantity, array $attributes): ?array
    {
        if (! $cart) {
            return $cart;
        }

        if ($quantity > 0 && count($cart['lines']['edges'])) {
            $lineItemId = $this->decode($lineItemId);

            foreach ($cart['lines']['edges'] as $index => $edge) {
                $node = $edge['node'] ?? null;
                if ($node) {
                    if ($this->decode($node['id']) === $lineItemId) {
                        // Update quantity
                        $cart['lines']['edges'][$index]['node']['quantity'] += $quantity;
                        // Update attributes
                        $cart['lines']['edges'][$index]['node']['attributes'] = array_merge(
                            $cart['lines']['edges'][$index]['node']['attributes'],
                            $attributes
                        );
                        break;
                    }
                }
            }
        }

        return $cart;
    }

    /**
     * Remove line item.
     *
     * @param array|null $cart
     * @param string $lineItemId
     * @return array|null
     */
    protected function removeLineItem(?array $cart, string $lineItemId): ?array
    {
        if (! $cart) {
            return $cart;
        }

        if (count($cart['lines']['edges'])) {
            $lineItemId = $this->decode($lineItemId);

            foreach ($cart['lines']['edges'] as $index => $edge) {
                $node = $edge['node'] ?? null;
                if ($node) {
                    if ($this->decode($node['id']) === $lineItemId) {
                        // Remove line item
                        unset($cart['lines']['edges'][$index]);
                        // Normalise line edges indexes
                        $cart['lines']['edges'] = array_values($cart['lines']['edges']);
                        break;
                    }
                }
            }
        }

        return $cart;
    }

    /**
     * Create new cart.
     *
     * @param string $countryCode
     * @return array|null
     * @throws Exception
     */
    public function create(string $countryCode): ?array
    {
        // Validate market
        if (! $this->shopify->market()->has($countryCode)) {
            return null;
        }

        // Create random cart
        $cartId = $this->shopify->ids()->createRandomId(self::CART_PREFIX);
        $cart = [
            'id' => $this->encode($cartId),
            'createdAt' => date('Y-m-d\TH:i:s\Z'),
            'updatedAt' => date('Y-m-d\TH:i:s\Z'),
            'checkoutUrl' => 'https://'.$this->shopify->context()->getShopBaseUrl().'/cart/c/'.str_replace(self::CART_PREFIX, '', $cartId),
            'buyerIdentity' => [
                'countryCode' => $countryCode,
            ],
            'attributes' => [],
            'discountCodes' => [],
            'note' => '',
            'lines' => [
                'edges' => [],
            ],
            'estimatedCost' => [
                'totalAmount' => [
                    'amount' => '0.0',
                    'currencyCode' => $this->shopify->market()->getCurrency($countryCode),
                ],
                'subtotalAmount' => [
                    'amount' => '0.0',
                    'currencyCode' => $this->shopify->market()->getCurrency($countryCode),
                ],
                'totalTaxAmount' => null,
                'totalDutyAmount' => null,
            ],
        ];

        // Store cart
        $this->shopify->store()->set(self::CART_PREFIX, $cartId, $cart);

        return $cart;
    }

    /**
     * Get cart, if existing.
     *
     * @param string $cartId
     * @param string $countryCode
     * @return array|null
     * @throws Exception
     */
    public function get(string $cartId, string $countryCode): ?array
    {
        return $this->updateBuyerIdentity($cartId, $countryCode);
    }

    /**
     * Update buyer identity.
     *
     * @param string $cartId
     * @param string $countryCode
     * @return array|null
     * @throws Exception
     */
    public function updateBuyerIdentity(string $cartId, string $countryCode): ?array
    {
        // Get cart
        $cart = $this->shopify->store()->get(self::CART_PREFIX, $cartId);

        if (! $cart) {
            return null;
        }

        // Re-calculate prices
        return $this->updateCartPricesByMarket($cartId, $countryCode);
    }

    /**
     * Add lines to cart.
     *
     * @param string $cartId
     * @param string $countryCode
     * @param array $lines
     * @return array|null
     * @throws Exception
     */
    public function addLines(string $cartId, string $countryCode, array $lines): ?array
    {
        // Get cart
        $cart = $this->shopify->store()->get(self::CART_PREFIX, $cartId);

        if (! $cart || ! count($lines)) {
            // Invalid cart or empty lines
            return null;
        }

        // Get currency
        $currency = $this->shopify->market()->getCurrency($countryCode);

        // Add items
        foreach ($lines as $line) {
            $variantId = $line['merchandiseId'] ?? null;
            $quantity = (int) $line['quantity'] ?? 0;
            $attributes = $line['attributes'] ?? [];
            if ($variantId && $quantity > 0) {
                $cart = $this->addOrUpdateLineItem($cart, $currency, $variantId, $quantity, $attributes);
            } else {
                // Error: variant ID is null or quantity is below 1
                return null;
            }
        }

        // Store updated cart
        $this->shopify->store()->set(self::CART_PREFIX, $cartId, $cart);

        // Recalculate totals
        return $this->updateCartPricesByMarket($cartId, $countryCode);
    }

    /**
     * Update line items.
     *
     * @param string $cartId
     * @param string $countryCode
     * @param array $lines
     * @return array|null
     * @throws Exception
     */
    public function updateLines(string $cartId, string $countryCode, array $lines): ?array
    {
        // Get cart
        $cart = $this->shopify->store()->get(self::CART_PREFIX, $cartId);

        if (! $cart || ! count($lines)) {
            // Invalid cart or empty lines
            return null;
        }

        // Update items
        foreach ($lines as $line) {
            $lineItemId = $line['id'] ?? null;
            $quantity = (int) $line['quantity'] ?? 0;
            $attributes = $line['attributes'] ?? [];
            if ($lineItemId && $quantity > 0) {
                $cart = $this->updateLineItem($cart, $lineItemId, $quantity, $attributes);
            } else {
                // Error: line item ID is null or quantity is below 1
                return null;
            }
        }

        // Store updated cart
        $this->shopify->store()->set(self::CART_PREFIX, $cartId, $cart);

        // Recalculate totals
        return $this->updateCartPricesByMarket($cartId, $countryCode);
    }

    /**
     * Remove line items.
     *
     * @param string $cartId
     * @param string $countryCode
     * @param array $lines
     * @return array|null
     * @throws Exception
     */
    public function removeLines(string $cartId, string $countryCode, array $lines): ?array
    {
        // Get cart
        $cart = $this->shopify->store()->get(self::CART_PREFIX, $cartId);

        if (! $cart || ! count($lines)) {
            // Invalid cart or empty lines
            return null;
        }

        // Remove items
        foreach ($lines as $line) {
            $lineItemId = $line ?? null;
            if ($lineItemId) {
                $cart = $this->removeLineItem($cart, $lineItemId);
            } else {
                // Error
                return null;
            }
        }

        // Store updated cart
        $this->shopify->store()->set(self::CART_PREFIX, $cartId, $cart);

        // Recalculate totals
        return $this->updateCartPricesByMarket($cartId, $countryCode);
    }

    /**
     * Update note.
     *
     * @param string $cartId
     * @param string $countryCode
     * @param string $note
     * @return array|null
     * @throws Exception
     */
    public function updateNote(string $cartId, string $countryCode, string $note): ?array
    {
        // Get cart
        $cart = $this->shopify->store()->get(self::CART_PREFIX, $cartId);

        if (! $cart) {
            // Invalid cart
            return null;
        }

        // Add note
        $cart['note'] = $note;

        // Store updated cart
        $this->shopify->store()->set(self::CART_PREFIX, $cartId, $cart);

        // Recalculate totals
        return $this->updateCartPricesByMarket($cartId, $countryCode);
    }

    /**
     * Update attributes.
     *
     * @param string $cartId
     * @param string $countryCode
     * @param array $attributes
     * @return array|null
     * @throws Exception
     */
    public function updateAttributes(string $cartId, string $countryCode, array $attributes): ?array
    {
        // Get cart
        $cart = $this->shopify->store()->get(self::CART_PREFIX, $cartId);

        if (! $cart || empty($attributes)) {
            // Invalid cart or empty attributes
            return null;
        }

        $key = $attributes['key'] ?? null;
        $value = $attributes['value'] ?? null;
        if (! $key || ! $value) {
            // Invalid attributes
            return null;
        }

        // Check if attribute already exists.
        // Important: attributes are not case-sensitive in Shopify. Setting attribute key "Test" will replace existing key "test".
        $attributeAlreadyExists = false;
        foreach ($cart['attributes'] as $index => $row) {
            if (strtolower($row['key']) === strtolower($key)) {
                $cart['attributes'][$index] = [
                    'key' => $key,
                    'value' => $value,
                ];
                $attributeAlreadyExists = true;
                break;
            }
        }

        if (! $attributeAlreadyExists) {
            $cart['attributes'][] = [
                'key' => $key,
                'value' => $value,
            ];
        }

        // Store updated cart
        $this->shopify->store()->set(self::CART_PREFIX, $cartId, $cart);

        // Recalculate totals
        return $this->updateCartPricesByMarket($cartId, $countryCode);
    }

    /**
     * Update discount codes.
     *
     * @param string $cartId
     * @param string $countryCode
     * @param array $discountCodes
     * @return array|null
     * @throws Exception
     */
    public function updateDiscountCodes(string $cartId, string $countryCode, array $discountCodes): ?array
    {
        // Get cart
        $cart = $this->shopify->store()->get(self::CART_PREFIX, $cartId);

        if (! $cart || empty($discountCodes)) {
            // Invalid cart or empty discount codes
            return null;
        }

        // Add only first discount code. This is the way Shopify works.
        $discountCode = $discountCodes[0];

        // Set discount codes
        $cart['discountCodes'] = [
            [
                'code' => $discountCode,
                'applicable' => $this->shopify->discountCodes()->has($discountCode), // Check if applicable
            ],
        ];

        // Store updated cart
        $this->shopify->store()->set(self::CART_PREFIX, $cartId, $cart);

        // Recalculate totals
        return $this->updateCartPricesByMarket($cartId, $countryCode);
    }
}
