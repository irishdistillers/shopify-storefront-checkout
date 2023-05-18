<?php

namespace Irishdistillers\ShopifyStorefrontCheckout;

use Exception;
use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\BaseService;
use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\LogLevelConstants;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;
use Irishdistillers\ShopifyStorefrontCheckout\Traits\ShopifyUtilsTrait;
use Irishdistillers\ShopifyStorefrontCheckout\Utils\AttributeFormatter;
use Irishdistillers\ShopifyStorefrontCheckout\Utils\Beautifier;
use Monolog\Logger;

/**
 * Storefront cart API.
 */
class CartService extends BaseService
{
    use ShopifyUtilsTrait;

    /** @var array|string|null */
    protected $lastError;

    /**
     * @param Context $context
     * @param Logger|null $logger
     * @param null|array $mock
     * @param int $logLevel
     */
    public function __construct(Context $context, ?Logger $logger = null, ?array $mock = null, int $logLevel = LogLevelConstants::LOG_LEVEL_NORMAL)
    {
        $this->lastError = null;
        parent::__construct($context, $logger, $mock, $logLevel);
    }

    protected function useStoreFrontApi(): bool
    {
        return true;
    }

    /**
     * Set last error
     * - Graphql error
     * - Otherwise, data error.
     *
     * @param string $endpoint
     * @param null $error
     * @return void
     */
    protected function setLastError(string $endpoint, $error = null)
    {
        $this->lastError = $this->graphql->getLastError() ?? $error;
        if ($this->lastError) {
            $this->errorMessages[] = $this->lastError;
        }
        if ($this->lastError && $this->logger) {
            $this->logger->warning('Shopify Cart error', ['endpoint' => $endpoint, 'error' => $this->lastError]);
        }
    }

    /**
     * Create new cart.
     *
     * @param string|null $countryCode
     * @return string|null
     */
    public function getNewCart(?string $countryCode): ?string
    {
        $query = <<<'QUERY'
 mutation cartCreate($input: CartInput) {
      cartCreate(input: $input) {
        cart {
          id
        }
        userErrors {
          field
          message
        }
      }
    }
QUERY;

        $variables = [
            'buyerIdentity' => [
                'countryCode' => $countryCode,
            ],
        ];

        $data = $this->graphql->query($query, $variables);

        // Check errors
        $this->setLastError('mutation cartCreate', $data['cartCreate']['userErrors'] ?? null);

        $cartId = $data['cartCreate']['cart']['id'] ?? null;

        return $this->decode($cartId);
    }

    /**
     * Set buyer identity
     * This is needed in order to get the cart with the correct market prices
     * Docs: https://shopify.dev/api/storefront/2022-10/mutations/cartBuyerIdentityUpdate.
     *
     * $this->getCart() will set automatically buyer identify
     *
     * @param string|null $cartId
     * @param string|null $countryCode
     * @param array $otherData
     * @return string|null
     */
    public function setBuyerIdentity(?string $cartId, ?string $countryCode, array $otherData = []): ?string
    {
        $query = <<<'QUERY'
 mutation cartBuyerIdentityUpdate($cartId: ID! $buyerIdentity: CartBuyerIdentityInput!) {
    cartBuyerIdentityUpdate(cartId: $cartId, buyerIdentity: $buyerIdentity) {
      cart {
        id
        buyerIdentity {
          countryCode
        }
        estimatedCost {
          totalTaxAmount {
            amount
            currencyCode
          }
        }
      }
      userErrors {
        field
        message
      }
    }
  }
QUERY;

        $variables = [
            'cartId' => $cartId,
            'buyerIdentity' => array_merge($otherData, ['countryCode' => $countryCode]),
        ];

        $data = $this->graphql->query($query, $variables);

        // Check errors
        $this->setLastError('mutation cartBuyerIdentityUpdate', $data['cartBuyerIdentityUpdate']['userErrors'] ?? null);

        $cartId = $data['cartBuyerIdentityUpdate']['cart']['id'] ?? null;

        return $cartId ? $this->decode($cartId) : null;
    }

    /**
     * Get cart.
     *
     * @param string|null $cartId Cart ID with gid:// prefix
     * @param string|null $countryCode Market, e.g. IE or GB
     * @param bool $setAutomaticallyBuyerIdentity
     * @param bool $includeSellingPlanAllocation Include selling plan allocation in cart line items. It requires unauthenticated_read_selling_plans access scope.
     * @return array|null
     */
    public function getCart(?string $cartId, ?string $countryCode, bool $setAutomaticallyBuyerIdentity = true, bool $includeSellingPlanAllocation = false): ?array
    {
        // Set automatically buyer identity
        if ($setAutomaticallyBuyerIdentity) {
            if (! $this->setBuyerIdentity($cartId, $countryCode)) {
                return null;
            }
        }

        // Selling plan allocation query
        $sellingPlanAllocationQuery = $includeSellingPlanAllocation ? 'sellingPlanAllocation {
                sellingPlan {
                  id
                }
              }' : '';

        $query = <<<QUERY
 query cart(\$cartId: ID!, \$countryCode: CountryCode!)
    @inContext(country: \$countryCode) {
      cart( id: \$cartId ) {
        id
        createdAt
        updatedAt
        checkoutUrl
        buyerIdentity {
          countryCode
          customer {
           id
          }
          email
        }
        attributes {
          key
          value
        }
        discountCodes {
          code
          applicable
        }
        note
        lines(first: 25, reverse: true) {
          edges {
            node {
              id
              attributes{
                key
                value
              }
              quantity

              {$sellingPlanAllocationQuery}

              discountAllocations {
                discountedAmount{
                  amount
                  currencyCode
                }
              }
              estimatedCost{
                subtotalAmount{
                  amount
                }
                totalAmount{
                  amount
                }
              }
              merchandise {
                ... on ProductVariant {
                  id
                  title
                  priceV2 {
                    amount
                    currencyCode
                  }
                  product {
                    id
                    availableForSale
                    variants(first: 6) {
                      edges {
                        node {
                          id
                        }
                      }
                    }
                    title
                    images(first: 1) {
                      edges {
                        node {
                          id
                          src
                          altText
                        }
                      }
                    }
                  }
                }
              }
              quantity
            }
          }
        }
        estimatedCost {
          totalAmount {
            amount
            currencyCode
          }
          subtotalAmount {
            amount
            currencyCode
          }
          totalTaxAmount {
            amount
            currencyCode
          }
          totalDutyAmount {
            amount
            currencyCode
          }
        }
      }
    }
QUERY;

        $variables = [
            'cartId' => $cartId,
            'countryCode' => $countryCode,
        ];

        $data = $this->graphql->query($query, $variables);

        $cart = $data ? ($data['cart'] ?? null) : null;

        if (! $cart) {
            $this->setLastError('query cart', 'The specified cart does not exist.');
        }

        return $cart;
    }

    /**
     * Add single line item.
     *
     * @param string|null $cartId
     * @param string $variantId Variant ID with gid:// prefix
     * @param int $quantity Quantity must be >= 1
     * @param array $attributes Attributes, e.g. ['mvr' => 1]
     * @param string|null $sellingPlanId Selling plan ID, when it applies (e.g. pre-order)
     * @return string|null
     * @throws Exception
     */
    public function addLine(?string $cartId, string $variantId, int $quantity, array $attributes = [], ?string $sellingPlanId = null): ?string
    {
        return $this->addLines(
            $cartId,
            [
                [$this->normaliseVariantId($variantId) => [
                    'quantity' => $quantity,
                    'attributes' => $attributes,
                ]],
            ],
            $sellingPlanId
        );
    }

    /**
     * Add line items.
     *
     * Line items are valid in two formats:
     * 1) without attributes
     *    [ variant_id => quantity ] e.g. [ ['gid://.../variant_id' => 1 ], ... ]
     * 2) with attributes
     *    [ variant_id => quantity => quantity, attributes => attributes ] e.g. [ ['gid://.../variant_id' => ['quantity' => 1, 'attributes' => [ ... ] ], ... ]
     *
     * @param string|null $cartId
     * @param array $variantIdsWithQuantityAndAttributes See above docs
     * @param string|null $sellingPlanId Selling plan ID, when it applies (e.g. pre-order)
     * @return string|null
     * @throws Exception
     */
    public function addLines(?string $cartId, array $variantIdsWithQuantityAndAttributes, ?string $sellingPlanId = null): ?string
    {
        $query = <<<'QUERY'
 mutation cartLinesAdd($lines: [CartLineInput!]!, $cartId: ID!) {
    cartLinesAdd( lines: $lines, cartId: $cartId ) {
      cart {
        id
      }
      userErrors {
        field
        message
      }
    }
  }
QUERY;

        // Prepare lines
        $lines = [];
        foreach ($variantIdsWithQuantityAndAttributes as $row) {
            foreach ($row as $variantId => $values) {
                $attributes = [];
                if (is_array($values)) {
                    $quantity = $values['quantity'] ?? 0;
                    $attributes = AttributeFormatter::format($values['attributes'] ?? []);
                } else {
                    $quantity = $values;
                }

                $line = [
                    'merchandiseId' => $this->normaliseVariantId($variantId),
                    'quantity' => $quantity,
                    'attributes' => $attributes,
                ];

                // Inject selling plan ID, if passed
                if ($sellingPlanId) {
                    $line['sellingPlanId'] = $sellingPlanId;
                }

                $lines[] = $line;
            }
        }

        $variables = [
            'cartId' => $cartId,
            'lines' => $lines,
        ];

        $data = $this->graphql->query($query, $variables);

        // Check errors
        $this->setLastError('mutation cartLinesAdd', $data['cartLinesAdd']['userErrors'] ?? null);

        $cartId = $data['cartLinesAdd']['cart']['id'] ?? null;

        return $this->decode($cartId);
    }

    /**
     * Update single line item.
     *
     * @param string|null $cartId
     * @param string|null $lineItemId Line item ID with gid:// prefix
     * @param int $quantity Quantity must be >= 1
     * @param array $attributes
     * @return string|null
     * @throws Exception
     */
    public function updateLine(?string $cartId, ?string $lineItemId, int $quantity, array $attributes = []): ?string
    {
        return $this->updateLines(
            $cartId,
            [
                [$lineItemId => [
                    'quantity' => $quantity,
                    'attributes' => $attributes,
                ]],
            ]
        );
    }

    /**
     * Update line items.
     *
     * Line items are valid in two formats:
     * 1) without attributes
     *    [ line_item_id => quantity ] e.g. [ ['gid://.../line_item_id' => 1 ], ... ]
     * 2) with attributes
     *    [ line_item_id => quantity => quantity, attributes => attributes ] e.g. [ ['gid://.../line_item_id' => ['quantity' => 1, 'attributes' => [ ... ] ], ... ]
     * @param string|null $cartId
     * @param array $lineItemIdsWithQuantityAndAttributes See above docs
     * @return string|null
     * @throws Exception
     */
    public function updateLines(?string $cartId, array $lineItemIdsWithQuantityAndAttributes): ?string
    {
        $query = <<<'QUERY'
 mutation cartLinesUpdate($cartId: ID!, $lines: [CartLineUpdateInput!]!) {
    cartLinesUpdate(cartId: $cartId, lines: $lines) {
      cart {
        id
      }
      userErrors {
        field
        message
      }
    }
  }
QUERY;

        // Prepare lines
        $lines = [];
        foreach ($lineItemIdsWithQuantityAndAttributes as $row) {
            foreach ($row as $id => $values) {
                $attributes = [];
                if (is_array($values)) {
                    $quantity = $values['quantity'];
                    $attributes = AttributeFormatter::format($values['attributes']);
                } else {
                    $quantity = $values;
                }

                $lines[] = [
                    'id' => $id,
                    'quantity' => $quantity,
                    'attributes' => $attributes,
                ];
            }
        }

        $variables = [
            'cartId' => $cartId,
            'lines' => $lines,
        ];

        $data = $this->graphql->query($query, $variables);

        // Check errors
        $this->setLastError('mutation cartLinesUpdate', $data['cartLinesUpdate']['userErrors'] ?? null);

        $cartId = $data['cartLinesUpdate']['cart']['id'] ?? null;

        return $this->decode($cartId);
    }

    /**
     * Remove line items.
     *
     * @param string|null $cartId
     * @param array $lineItemIds Line item IDS with gid:// prefix, e.g. [ 'gid://.../line_item_id', 'gid://.../line_item_id', ... ]
     * @return string|null
     */
    public function removeLines(?string $cartId, array $lineItemIds): ?string
    {
        $query = <<<'QUERY'
 mutation cartLinesRemove($cartId: ID!, $lineIds: [ID!]!) {
  cartLinesRemove(cartId: $cartId, lineIds: $lineIds) {
      cart {
        id
      }
      userErrors {
        field
        message
      }
    }
  }
QUERY;

        $variables = [
            'cartId' => $cartId,
            'lineIds' => $lineItemIds,
        ];

        $data = $this->graphql->query($query, $variables);

        // Check errors
        $this->setLastError('mutation cartLinesRemove', $data['cartLinesRemove']['userErrors'] ?? null);

        $cartId = $data['cartLinesRemove']['cart']['id'] ?? null;

        return $this->decode($cartId);
    }

    /**
     * Empty cart.
     *
     * @param string|null $cartId
     * @param string|null $countryCode
     * @return bool
     */
    public function emptyCart(?string $cartId, ?string $countryCode): bool
    {
        $result = false;

        // Get cart
        $cart = $this->getCart(
            $this->decode($cartId),
            $countryCode
        );

        if ($cart) {
            // Get all line items
            $lineItems = $cart['lines']['edges'] ?? [];

            // Remove them
            $lineItemIds = [];
            foreach ($lineItems as $lineItem) {
                $id = $lineItem['node']['id'] ?? null;
                if ($id) {
                    $lineItemIds[] = $this->decode($id);
                }
            }

            if (count($lineItemIds)) {
                $this->removeLines($cartId, $lineItemIds);

                $result = true;
            }
        }

        return $result;
    }

    /**
     * Update note.
     *
     * A note can be an empty string.
     *
     * @param string|null $cartId
     * @param string $note
     * @return string|null
     */
    public function updateNote(?string $cartId, string $note): ?string
    {
        $query = <<<'QUERY'
 mutation cartNoteUpdate($cartId: ID!, $note: String) {
  cartNoteUpdate(cartId: $cartId, note: $note) {
    cart {
      id
    }
    userErrors {
      field
      message
    }
  }
 }
QUERY;

        $variables = [
            'cartId' => $cartId,
            'note' => $note,
        ];

        $data = $this->graphql->query($query, $variables);

        // Check errors
        $this->setLastError('mutation cartNoteUpdate', $data['cartNoteUpdate']['userErrors'] ?? null);

        $cartId = $data['cartNoteUpdate']['cart']['id'] ?? null;

        return $this->decode($cartId);
    }

    /**
     * Update attributes.
     *
     * Important: attributes are not case-sensitive in Shopify.
     * Setting attribute key as "Test" will replace existing key "test".
     *
     * @param string|null $cartId
     * @param string $key Attribute key
     * @param string $value Attribute value
     * @return string|null
     */
    public function updateAttributes(?string $cartId, string $key, string $value): ?string
    {
        $query = <<<'QUERY'
 mutation cartAttributesUpdate($attributes: [AttributeInput!]!, $cartId: ID!) {
  cartAttributesUpdate(attributes: $attributes, cartId: $cartId) {
    cart {
      id
    }
    userErrors {
      field
      message
    }
  }
 }
QUERY;

        $variables = [
            'cartId' => $cartId,
            'attributes' => [
                'key' => $key,
                'value' => $value,
            ],
        ];

        $data = $this->graphql->query($query, $variables);

        // Check errors
        $this->setLastError('mutation cartAttributesUpdate', $data['cartAttributesUpdate']['userErrors'] ?? null);

        $cartId = $data['cartAttributesUpdate']['cart']['id'] ?? null;

        return $this->decode($cartId);
    }

    /**
     * Update discount codes.
     *
     * Important: even if passing multiple discount codes, Shopify will apply only the first one.
     *
     * @param string|null $cartId
     * @param array $discountCodes Array of discount codes, e.g. ['TENPERCENT', ...]
     * @return string|null
     */
    public function updateDiscountCodes(?string $cartId, array $discountCodes): ?string
    {
        $query = <<<'QUERY'
 mutation cartDiscountCodesUpdate($cartId: ID!, $discountCodes: [String!]!) {
  cartDiscountCodesUpdate(discountCodes: $discountCodes, cartId: $cartId) {
    cart {
      id
    }
    userErrors {
      field
      message
    }
  }
 }
QUERY;

        $variables = [
            'cartId' => $cartId,
            'discountCodes' => $discountCodes,
        ];

        $data = $this->graphql->query($query, $variables);

        // Check errors
        $this->setLastError('mutation cartDiscountCodesUpdate', $data['cartDiscountCodesUpdate']['userErrors'] ?? null);

        $cartId = $data['cartDiscountCodesUpdate']['cart']['id'] ?? null;

        return $this->decode($cartId);
    }

    /**
     * Check if cart ID exists.
     *
     * @param string $cartId
     * @param string|null $countryCode
     * @return bool
     */
    public function cartExists(string $cartId, ?string $countryCode): bool
    {
        $cart = $this->getCart($cartId, $countryCode);

        return ! is_null($cart);
    }

    /**
     * Get checkout URL.
     *
     * @param string|null $cartId
     * @param string|null $countryCode
     * @return string|null
     */
    public function getCheckoutUrl(?string $cartId, ?string $countryCode): ?string
    {
        $cart = $this->getCart($cartId, $countryCode);

        return $cart['checkoutUrl'] ?? null;
    }

    /**
     * Get last error.
     *
     * @return array|string|null
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Get Graphql last response.
     *
     * @return array|null
     */
    public function getLastResponse(): ?array
    {
        return $this->graphql->getLastResponse();
    }

    /**
     * Get context.
     *
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * Create beautifier object.
     *
     * @param array|null $cart
     * @return Beautifier
     */
    public function beautifier(?array $cart): Beautifier
    {
        return new Beautifier($cart);
    }
}
