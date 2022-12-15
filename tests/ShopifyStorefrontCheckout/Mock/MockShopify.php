<?php

namespace Tests\ShopifyStorefrontCheckout\Mock;

use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;
use Tests\ShopifyStorefrontCheckout\Mock\Shopify\MockCart;
use Tests\ShopifyStorefrontCheckout\Mock\Shopify\MockDiscountCodes;
use Tests\ShopifyStorefrontCheckout\Mock\Shopify\MockIds;
use Tests\ShopifyStorefrontCheckout\Mock\Shopify\MockMarkets;
use Tests\ShopifyStorefrontCheckout\Mock\Shopify\MockProducts;
use Tests\ShopifyStorefrontCheckout\Mock\Shopify\MockStore;

class MockShopify
{
    protected Context $context;

    protected MockCart $cart;

    protected MockDiscountCodes $discountCodes;

    protected MockIds $ids;

    protected MockMarkets $markets;

    protected MockProducts $products;

    protected MockStore $store;

    protected static array $randomIds = [];

    public function __construct(Context $context)
    {
        $this->context = $context;

        // Create Shopify providers
        $this->cart = new MockCart($this);
        $this->discountCodes = new MockDiscountCodes();
        $this->ids = new MockIds();
        $this->markets = new MockMarkets();
        $this->products = new MockProducts($this);
        $this->store = new MockStore();
    }

    /**
     * Get mocked cart provider.
     *
     * @return MockCart
     */
    public function cart(): MockCart
    {
        return $this->cart;
    }

    /**
     * Get context.
     *
     * @return Context
     */
    public function context(): Context
    {
        return $this->context;
    }

    /**
     * Get mocked Shopify discount codes provider.
     *
     * @return MockDiscountCodes
     */
    public function discountCodes(): MockDiscountCodes
    {
        return $this->discountCodes;
    }

    /**
     * Get mocked Shopify Ids provider.
     *
     * @return MockIds
     */
    public function ids(): MockIds
    {
        return $this->ids;
    }

    /**
     * Get mocked markets provider.
     *
     * @return MockMarkets
     */
    public function market(): MockMarkets
    {
        return $this->markets;
    }

    /**
     * Get mocked products provider.
     *
     * @return MockProducts
     */
    public function products(): MockProducts
    {
        return $this->products;
    }

    /**
     * Get mocked store provider.
     *
     * @return MockStore
     */
    public function store(): MockStore
    {
        return $this->store;
    }
}
