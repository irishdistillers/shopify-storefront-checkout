<?php

namespace Irishdistillers\ShopifyStorefrontCheckout;

use Exception;
use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\LogLevelConstants;
use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\ShopifyConstants;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;
use Irishdistillers\ShopifyStorefrontCheckout\Utils\Beautifier;
use Monolog\Logger;

/**
 * Storefront Cart API.
 */
class Cart
{
    protected CartService $cartService;

    protected ?string $cartId;

    protected ?array $cart;

    protected ?string $countryCode;

    protected bool $includeSellingPlanAllocation;

    /**
     * @param Context $context
     * @param Logger|null $logger
     * @param null|array $mock
     * @param int $logLevel
     */
    public function __construct(Context $context, ?Logger $logger = null, ?array $mock = null, int $logLevel = LogLevelConstants::LOG_LEVEL_NORMAL)
    {
        $this->cartService = new CartService($context, $logger, $mock, $logLevel);
        $this->cartId = null;
        $this->cart = null;
        $this->countryCode = ShopifyConstants::DEFAULT_MARKET;
        $this->includeSellingPlanAllocation = false;
    }

    /**
     * Set country code.
     *
     * @param string|null $countryCode
     * @return $this
     */
    public function setCountryCode(?string $countryCode) : self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * Set flag to include selling plan allocation in cart line items.
     * It requires unauthenticated_read_selling_plans access scope.
     *
     * @param bool $includeSellingPlanAllocation
     * @return Cart
     */
    public function setIncludeSellingPlanAllocation(bool $includeSellingPlanAllocation): self
    {
        $this->includeSellingPlanAllocation = $includeSellingPlanAllocation;

        return $this;
    }

    /**
     * @return bool
     */
    public function isIncludeSellingPlanAllocation(): bool
    {
        return $this->includeSellingPlanAllocation;
    }

    /**
     * Get cart service.
     *
     * @return CartService
     */
    public function cartService(): CartService
    {
        return $this->cartService;
    }

    /**
     * Get cart ID.
     *
     * @return string|null
     */
    public function getCartId(): ?string
    {
        return $this->cartId;
    }

    /**
     * Get current country code.
     *
     * @return string|null
     */
    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    /**
     * Create new cart.
     *
     * @return array|null
     */
    public function getNewCart(): ?array
    {
        $this->cartId = $this->cartService->getNewCart($this->countryCode);

        return $this->getCart();
    }

    /**
     * Set existing cart ID.
     *
     *
     * @param string $cartId
     * @return array|null
     */
    public function setCartId(string $cartId): ?array
    {
        $this->cartId = $cartId;

        return $this->getCart();
    }

    /**
     * Get cart.
     *
     * @return array|null
     */
    public function getCart(): ?array
    {
        return $this->cartService->getCart($this->cartId, $this->countryCode, true, $this->includeSellingPlanAllocation);
    }

    /**
     * Add single line item.
     *
     * @param string $variantId Variant ID with gid:// prefix
     * @param int $quantity Quantity must be >= 1
     * @param array $attributes
     * @param string|null $sellingPlanId
     * @return bool
     * @throws Exception
     */
    public function addLine(string $variantId, int $quantity, array $attributes = [], ?string $sellingPlanId = null): bool
    {
        return (bool) $this->cartService->addLine($this->cartId, $variantId, $quantity, $attributes, $sellingPlanId);
    }

    /**
     * Add line items.
     *
     * @param array $variantIdsWithQuantity Array with variant IDs and quantity, e.g. [ ['gid://.../variant_id' => quantity], ...]
     * @param string|null $sellingPlanId
     * @return bool
     * @throws Exception
     */
    public function addLines(array $variantIdsWithQuantity, ?string $sellingPlanId = null): bool
    {
        return (bool) $this->cartService->addLines($this->cartId, $variantIdsWithQuantity, $sellingPlanId);
    }

    /**
     * Update single line item.
     *
     * @param string|null $lineItemId Line item ID with gid:// prefix
     * @param int $quantity Quantity must be >= 1
     * @param array $attributes
     * @return bool
     * @throws Exception
     */
    public function updateLine(?string $lineItemId, int $quantity, array $attributes = []): bool
    {
        return (bool) $this->cartService->updateLine($this->cartId, $lineItemId, $quantity, $attributes);
    }

    /**
     * Update line items.
     *
     * @param array $lineItemIdsWithQuantity E.g. [ [ 'gid://.../line_item_id' => quantity ], [ 'gid://.../line_item_id' => quantity ], ... ]
     * @return bool
     * @throws Exception
     */
    public function updateLines(array $lineItemIdsWithQuantity): bool
    {
        return (bool) $this->cartService->updateLines($this->cartId, $lineItemIdsWithQuantity);
    }

    /**
     * Remove line items.
     *
     * @param array $lineItemIds Line item IDS with gid:// prefix, e.g. [ 'gid://.../line_item_id', 'gid://.../line_item_id', ... ]
     * @return bool
     */
    public function removeLines(array $lineItemIds): bool
    {
        return (bool) $this->cartService->removeLines($this->cartId, $lineItemIds);
    }

    /**
     * Empty cart.
     *
     * @return bool
     */
    public function emptyCart(): bool
    {
        return $this->cartService->emptyCart($this->cartId, $this->countryCode);
    }

    /**
     * Update note.
     *
     * A note can be an empty string.
     *
     * @param string $note
     * @return bool
     */
    public function updateNote(string $note): bool
    {
        return (bool) $this->cartService->updateNote($this->cartId, $note);
    }

    /**
     * Update attributes.
     *
     * Important: attributes are not case-sensitive in Shopify.
     * Setting attribute key as "Test" will replace existing key "test".
     *
     * @param string $key Attribute key
     * @param string $value Attribute value
     * @return bool
     */
    public function updateAttributes(string $key, string $value): bool
    {
        return (bool) $this->cartService->updateAttributes($this->cartId, $key, $value);
    }

    /**
     * Update discount codes.
     *
     * Important: even if passing multiple discount codes, Shopify will apply only the first one.
     *
     * @param array $discountCodes Array of discount codes, e.g. ['TENPERCENT', ...]
     * @return bool
     */
    public function updateDiscountCodes(array $discountCodes): bool
    {
        return (bool) $this->cartService->updateDiscountCodes($this->cartId, $discountCodes);
    }

    /**
     * Get checkout URL.
     *
     * @return string|null
     */
    public function getCheckoutUrl(): ?string
    {
        return $this->cartService->getCheckoutUrl($this->cartId, $this->countryCode);
    }

    /**
     * Get errors.
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->cartService->errors();
    }

    /**
     * Get Graphql last response.
     *
     * @return array|null
     */
    public function getLastResponse(): ?array
    {
        return $this->cartService->getLastResponse();
    }

    /**
     * Create beautifier object.
     *
     * @return Beautifier
     */
    public function beautifier(): Beautifier
    {
        return $this->cartService->beautifier($this->getCart());
    }
}
