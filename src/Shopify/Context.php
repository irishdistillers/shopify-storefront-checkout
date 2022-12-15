<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Shopify;

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
        $this->shopBaseUrl = $shopBaseUrl;
        $this->apiVersion = $apiVersion;
        $this->shopifyStoreFrontAccessToken = $shopifyStoreFrontAccessToken;
        $this->shopifyAccessToken = $shopifyAccessToken;
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
}
