<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Shopify;

use ArrayObject;
use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\ShopifyAccountInterface;

/**
 * Context class for Shopify.
 */
class Context
{
    protected string $apiVersion;

    protected string $shopBaseUrl;

    protected ?string $shopifyStoreFrontAccessToken;

    protected ?string $shopifyAccessToken;

    /**
     * @param string $shopBaseUrl Shop base URL, e.g. whiskey-barrel-club.myshopify.com
     * @param string $apiVersion API version, e.g. 2021-04
     * @param string|null $shopifyStoreFrontAccessToken Storefront access token. Required to use Storefront Graphql API (e.g. cart).
     * @param string|null $shopifyAccessToken Admin access token. Required to use admin Graphql API.
     */
    public function __construct(string $shopBaseUrl, string $apiVersion, ?string $shopifyStoreFrontAccessToken = null, ?string $shopifyAccessToken = null)
    {
        $this->shopBaseUrl = $this->normaliseBaseUrl($shopBaseUrl);
        $this->apiVersion = $apiVersion;
        $this->shopifyStoreFrontAccessToken = $shopifyStoreFrontAccessToken;
        $this->shopifyAccessToken = $shopifyAccessToken;
    }

    private function normaliseBaseUrl(string $shopBaseUrl): string
    {
        return substr($shopBaseUrl, 0, 4) === 'http' ?
            (parse_url($shopBaseUrl, PHP_URL_HOST) ?: '') :
            $shopBaseUrl;
    }

    public function setShopifyStoreFrontAccessToken(?string $shopifyStoreFrontAccessToken): self
    {
        $this->shopifyStoreFrontAccessToken = $shopifyStoreFrontAccessToken;

        return $this;
    }

    public function setShopifyAccessToken(?string $shopifyAccessToken): self
    {
        $this->shopifyAccessToken = $shopifyAccessToken;

        return $this;
    }

    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }

    public function getShopBaseUrl(): string
    {
        return $this->shopBaseUrl;
    }

    public function getShopifyAccessToken(): ?string
    {
        return $this->shopifyAccessToken;
    }

    public function getShopifyStoreFrontAccessToken(): ?string
    {
        return $this->shopifyStoreFrontAccessToken;
    }

    /**
     * Create Context from config.
     *
     * @param ArrayObject $config
     * @param array $fallback
     * @return Context
     */
    public static function createFromConfig(ArrayObject $config, array $fallback = []): self
    {
        return new self(
            $config[ShopifyAccountInterface::SHOPIFY_BASE_URL] ?? $fallback[ShopifyAccountInterface::SHOPIFY_BASE_URL] ?? null,
            $config[ShopifyAccountInterface::API_VERSION] ?? $fallback[ShopifyAccountInterface::API_VERSION] ?? ShopifyAccountInterface::DEFAULT_API_VERSION,
            $config[ShopifyAccountInterface::STOREFRONT_ACCESS_TOKEN] ?? $fallback[ShopifyAccountInterface::STOREFRONT_ACCESS_TOKEN] ?? null,
            $config[ShopifyAccountInterface::APP_SIGNATURE] ?? $fallback[ShopifyAccountInterface::APP_SIGNATURE] ?? null,
        );
    }
}
