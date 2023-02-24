<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Graphql;

use Exception;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\Graphql\Query\MockGraphqlQuery;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockFactory;

class MockCartGraphql extends MockBaseGraphql
{
    /**
     * Query cart.
     *
     * @param string|null $query
     * @param array|null $variables
     * @return array
     * @throws Exception
     */
    public function cartCreate(?string $query, ?array $variables): array
    {
        // Parse graphql query
        $graphqlQuery = new MockGraphqlQuery($query, $variables);

        // Prepare variables
        $countryCode = $variables['buyerIdentity']['countryCode'] ?? 'IE';

        // Create cart
        $cart = $this->mockShopify->cart()->create($countryCode);

        return $this->response(
            'cartCreate',
            $graphqlQuery,
            [
                'cart' => $cart,
            ],
        );
    }

    /**
     * Mutation cartBuyerIdentityUpdate.
     *
     * @param string|null $query
     * @param array|null $variables
     * @return array|null
     * @throws Exception
     */
    public function cartBuyerIdentityUpdate(?string $query, ?array $variables): ?array
    {
        // Parse graphql query
        $graphqlQuery = new MockGraphqlQuery($query, $variables);

        // Prepare variables
        $cartId = $variables['cartId'] ?? '';
        $newCountryCode = $variables['buyerIdentity']['countryCode'] ?? 'IE';

        // Check if cart exists
        if (! $this->mockShopify->cart()->exist($cartId)) {
            // If cart doesn't exist and factory is created, create the cart on the fly
            if ($this->factory) {
                // Create cart
                $this->factory->handle(
                    MockFactory::FACTORY_CART_CREATE,
                    $this->mockShopify,
                    $cartId,
                    $newCountryCode
//                    ['cartId' => $cartId, 'newCountryCode' => $newCountryCode]
                );
            }
        }

        // Get cart with correct market
        $cart = $this->mockShopify->cart()->get($cartId, $newCountryCode);

        if ($cart) {
            return $this->response(
                'cartBuyerIdentityUpdate',
                $graphqlQuery,
                [
                    'cart' => $cart,
                ],
            );
        }

        return $this->response(
            'cartBuyerIdentityUpdate',
            $graphqlQuery,
            [
                    'cart' => null,
                    'userErrors' => [
                        [
                            'field' => [
                                'cartId',
                            ],
                            'message' => 'The specified cart does not exist.',
                        ],
                    ],
                ],
        );
    }

    public function cartGet(?string $query, ?array $variables): ?array
    {
        // Parse graphql query
        $graphqlQuery = new MockGraphqlQuery($query, $variables);

        // Prepare variables
        $cartId = $variables['cartId'] ?? '';
        $countryCode = $graphqlQuery->getContext('country') ?? 'IE';

        // Get cart with correct market
        $cart = $this->mockShopify->cart()->get($cartId, $countryCode);

        return $this->response(
            null,
            $graphqlQuery,
            [
                'cart' => $cart,
            ]
        );
    }

    /**
     * Mutation cartLinesAdd.
     *
     * @param string|null $query
     * @param array|null $variables
     * @return array|null
     * @throws Exception
     */
    public function cartLinesAdd(?string $query, ?array $variables): ?array
    {
        // Parse graphql query
        $graphqlQuery = new MockGraphqlQuery($query, $variables);

        // Prepare variables
        $cartId = $this->decode($variables['cartId'] ?? '');
        $countryCode = $graphqlQuery->getContext('country') ?? 'IE';

        // Check if cart is valid
        if (! $cartId) {
            return null;
        }

        // Add lines
        $cart = $this->mockShopify->cart()->addLines($cartId, $countryCode, $variables['lines']);

        if ($cart) {
            return $this->response(
                'cartLinesAdd',
                $graphqlQuery,
                [
                    'cart' => $cart,
                ]
            );
        }

        return null;
    }

    /**
     * Mutation cartLinesUpdate.
     *
     * @param string|null $query
     * @param array|null $variables
     * @return array|null
     * @throws Exception
     */
    public function cartLinesUpdate(?string $query, ?array $variables): ?array
    {
        // Parse graphql query
        $graphqlQuery = new MockGraphqlQuery($query, $variables);

        // Prepare variables
        $cartId = $this->decode($variables['cartId'] ?? '');
        $countryCode = $graphqlQuery->getContext('country') ?? 'IE';

        // Check if cart is valid
        if (! $cartId) {
            return null;
        }

        // Update lines
        $cart = $this->mockShopify->cart()->updateLines($cartId, $countryCode, $variables['lines']);

        if ($cart) {
            return $this->response(
                'cartLinesUpdate',
                $graphqlQuery,
                [
                    'cart' => $cart,
                ]
            );
        }

        return null;
    }

    /**
     * Mutation cartLinesRemove.
     *
     * @param string|null $query
     * @param array|null $variables
     * @return array|null
     * @throws Exception
     */
    public function cartLinesRemove(?string $query, ?array $variables): ?array
    {
        // Parse graphql query
        $graphqlQuery = new MockGraphqlQuery($query, $variables);

        // Prepare variables
        $cartId = $this->decode($variables['cartId'] ?? '');
        $countryCode = $graphqlQuery->getContext('country') ?? 'IE';

        // Check if cart is valid
        if (! $cartId) {
            return null;
        }

        // Remove lines
        $cart = $this->mockShopify->cart()->removeLines($cartId, $countryCode, $variables['lineIds']);

        if ($cart) {
            return $this->response(
                'cartLinesRemove',
                $graphqlQuery,
                [
                    'cart' => $cart,
                ]
            );
        }

        return null;
    }

    /**
     * Mutation cartNoteUpdate.
     *
     * @param string|null $query
     * @param array|null $variables
     * @return array|null
     * @throws Exception
     */
    public function cartNoteUpdate(?string $query, ?array $variables): ?array
    {
        // Parse graphql query
        $graphqlQuery = new MockGraphqlQuery($query, $variables);

        // Prepare variables
        $cartId = $this->decode($variables['cartId'] ?? '');
        $countryCode = $graphqlQuery->getContext('country') ?? 'IE';
        $note = $variables['note'] ?? '';

        // Check if cart is valid
        if (! $cartId) {
            return null;
        }

        // Set note
        $cart = $this->mockShopify->cart()->updateNote($cartId, $countryCode, $note);

        if ($cart) {
            return $this->response(
                'cartNoteUpdate',
                $graphqlQuery,
                [
                    'cart' => $cart,
                ]
            );
        }

        return null;
    }

    /**
     * Mutation cartAttributesUpdate.
     *
     * @param string|null $query
     * @param array|null $variables
     * @return array|null
     * @throws Exception
     */
    public function cartAttributesUpdate(?string $query, ?array $variables): ?array
    {
        // Parse graphql query
        $graphqlQuery = new MockGraphqlQuery($query, $variables);

        // Prepare variables
        $cartId = $this->decode($variables['cartId'] ?? '');
        $countryCode = $graphqlQuery->getContext('country') ?? 'IE';
        $attributes = $variables['attributes'] ?? [];

        // Check if cart is valid
        if (! $cartId || empty($attributes)) {
            return null;
        }

        // Set note
        $cart = $this->mockShopify->cart()->updateAttributes($cartId, $countryCode, $attributes);

        if ($cart) {
            return $this->response(
                'cartAttributesUpdate',
                $graphqlQuery,
                [
                    'cart' => $cart,
                ]
            );
        }

        return null;
    }

    /**
     * Mutation cartDiscountCodesUpdate.
     *
     * @param string|null $query
     * @param array|null $variables
     * @return array|null
     * @throws Exception
     */
    public function cartDiscountCodesUpdate(?string $query, ?array $variables): ?array
    {
        // Parse graphql query
        $graphqlQuery = new MockGraphqlQuery($query, $variables);

        // Prepare variables
        $cartId = $this->decode($variables['cartId'] ?? '');
        $countryCode = $graphqlQuery->getContext('country') ?? 'IE';
        $discountCodes = $variables['discountCodes'] ?? [];

        // Check if cart is valid
        if (! $cartId) {
            return null;
        }

        // Set note
        $cart = $this->mockShopify->cart()->updateDiscountCodes($cartId, $countryCode, $discountCodes);

        if ($cart) {
            return $this->response(
                'cartDiscountCodesUpdate',
                $graphqlQuery,
                [
                    'cart' => $cart,
                ]
            );
        }

        return null;
    }

    public function getEndpoints(): array
    {
        // Add here other Graphql endpoints
        return [
            'query cart' => [$this, 'cartGet'],
            'mutation cartCreate' => [$this, 'cartCreate'],
            'mutation cartBuyerIdentityUpdate' => [$this, 'cartBuyerIdentityUpdate'],
            'mutation cartLinesAdd' => [$this, 'cartLinesAdd'],
            'mutation cartLinesUpdate' => [$this, 'cartLinesUpdate'],
            'mutation cartLinesRemove' => [$this, 'cartLinesRemove'],
            'mutation cartNoteUpdate' => [$this, 'cartNoteUpdate'],
            'mutation cartAttributesUpdate' => [$this, 'cartAttributesUpdate'],
            'mutation cartDiscountCodesUpdate' => [$this, 'cartDiscountCodesUpdate'],
        ];
    }
}
