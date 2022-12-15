<?php

namespace Irishdistillers\ShopifyStorefrontCheckout;

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

    /**
     * @param Context $context
     * @param Logger|null $logger
     * @param null|array $mock
     */
    public function __construct(Context $context, ?Logger $logger = null, ?array $mock = null)
    {
        $this->cartService = new CartService($context, $logger, $mock);
        $this->cartId = null;
        $this->cart = null;
        $this->countryCode = ShopifyConstants::DEFAULT_MARKET;
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
     * Get cart service.
     *
     * @return CartService
     */
    public function cartService(): CartService
    {
        return $this->cartService;
    }

    /**
     * Get cart ID
     *
     * @return string|null
     */
    public function getCartId(): ?string
    {
        return $this->cartId;
    }

    /**
     * Get currenty country code
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
     * Get cart.
     *
     * @return array|null
     */
    public function getCart(): ?array
    {
        return $this->cartService->getCart($this->cartId, $this->countryCode);
    }

    /**
     * Add single line item.
     *
     * @param string $variantId Variant ID with gid:// prefix
     * @param int $quantity Quantity must be >= 1
     * @return bool
     */
    public function addLine(string $variantId, int $quantity): bool
    {
        return (bool) $this->cartService->addLine($this->cartId, $variantId, $quantity);
    }

    /**
     * Add line items.
     *
     * @param array $variantIdsWithQuantity Array with variant IDs and quantity, e.g. [ ['gid://.../variant_id' => quantity], ...]
     * @return bool
     */
    public function addLines(array $variantIdsWithQuantity): bool
    {
        return (bool) $this->cartService->addLines($this->cartId, $variantIdsWithQuantity);
    }

    /**
     * Update single line item.
     *
     * @param string|null $lineItemId Line item ID with gid:// prefix
     * @param int $quantity Quantity must be >= 1
     * @return bool
     */
    public function updateLine(?string $lineItemId, int $quantity): bool
    {
        return (bool) $this->cartService->updateLine($this->cartId, $lineItemId, $quantity);
    }

    /**
     * Update line items.
     *
     * @param array $lineItemIdsWithQuantity E.g. [ [ 'gid://.../line_item_id' => quantity ], [ 'gid://.../line_item_id' => quantity ], ... ]
     * @return bool
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
     * Get last error.
     *
     * @return array|string|null
     */
    public function getLastError()
    {
        return $this->cartService->getLastError();
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
