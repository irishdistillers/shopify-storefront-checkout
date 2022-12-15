<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Utils;

use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\ShopifyConstants;
use Irishdistillers\ShopifyStorefrontCheckout\Traits\ShopifyUtilsTrait;

/**
 * Beautifier
 * Utility class to render the cart human-friendly.
 */
class Beautifier
{
    use ShopifyUtilsTrait;

    protected array $cart;

    public function __construct(?array $cart)
    {
        $this->cart = $cart ?? [];
    }

    public function getCartId(): ?string
    {
        return $this->decode($this->cart['id'] ?? null);
    }

    public function getCartIdWithoutPrefix(): string
    {
        return str_replace(ShopifyConstants::GID_CART_PREFIX, '', $this->decode($this->cart['id'] ?? '') ?? '');
    }

    public function getCountryCode(): string
    {
        return $this->cart['buyerIdentity']['countryCode'] ?? '';
    }

    public function getCreatedAt(): string
    {
        return $this->cart ? date('M j, Y \a\t g:ia', strtotime($this->cart['createdAt'] ?? '')) : '';
    }

    public function getUpdatedAt(): string
    {
        return $this->cart ? date('M j, Y \a\t g:ia', strtotime($this->cart['updatedAt'] ?? '')) : '';
    }

    public function getCheckoutUrl(): string
    {
        return $this->cart['checkoutUrl'] ?? '';
    }

    public function getNote(): string
    {
        return $this->cart['note'] ?? '';
    }

    protected function formatPrice(?array $price): string
    {
        return $price ? ($price['currencyCode'] ?? '').' '.sprintf('%1.2f', $price['amount'] ?? '') : 'N/A';
    }

    public function getEstimatedCosts(): array
    {
        return [
            'net' => $this->formatPrice($this->cart['estimatedCost']['subtotalAmount'] ?? []),
            'tax' => $this->formatPrice($this->cart['estimatedCost']['totalTaxAmount'] ?? []),
            'total' => $this->formatPrice($this->cart['estimatedCost']['totalAmount'] ?? []),
        ];
    }

    protected function formatLineItem(array $lineItem, bool $moreDetails): ?array
    {
        $node = $lineItem['node'] ?? null;
        if ($node) {
            $item = [
                'id' => $this->decode($node['id'] ?? null),
                'title' => $node['merchandise']['product']['title'] ?? '',
                'product_id' => null,
                'variant_id' => null,
                'quantity' => $node['quantity'] ?? 0,
                'price' => $this->formatPrice($node['merchandise']['priceV2'] ?? []),
                'image' => null,
            ];

            if ($moreDetails) {
                $item['product_id'] = $this->decode($node['merchandise']['product']['id'] ?? null);

                $variants = $node['merchandise']['product']['variants']['edges'] ?? [];
                if (count($variants)) {
                    $item['variant_id'] = $this->decode($variants[0]['node']['id'] ?? null);
                }

                $images = $node['merchandise']['product']['images']['edges'] ?? [];
                if (count($images)) {
                    $item['image'] = $images[0]['node']['src'] ?? null;
                }
            }

            return $item;
        }

        return null;
    }

    public function getLineItems(bool $moreDetails = false): array
    {
        $lineItems = [];
        foreach ($this->cart['lines']['edges'] ?? [] as $lineItem) {
            $item = $this->formatLineItem($lineItem, $moreDetails);
            if ($item) {
                $lineItems[] = $item;
            }
        }

        return $lineItems;
    }

    public function getLineItem(string $lineItemId, bool $moreDetails = false): ?array
    {
        foreach ($this->cart['lines']['edges'] ?? [] as $lineItem) {
            $node = $lineItem['node'] ?? null;
            if ($node) {
                $id = $this->decode($node['id'] ?? null);
                if ($lineItemId === $id) {
                    return $this->formatLineItem($lineItem, $moreDetails);
                }
            }
        }

        return null;
    }

    public function getAttributes(): array
    {
        return $this->cart['attributes'] ?? [];
    }

    public function getDiscountCodes(): ?array
    {
        return $this->cart['discountCodes'] ?? [];
    }

    public function json(): string
    {
        return json_encode($this->cart, JSON_PRETTY_PRINT);
    }
}
