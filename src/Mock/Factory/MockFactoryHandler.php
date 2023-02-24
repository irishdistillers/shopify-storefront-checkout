<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Factory;

use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockShopify;

class MockFactoryHandler
{
    /** @var callable|array */
    protected $callback;

    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    /**
     * Handle factory.
     *
     * @param MockShopify $shopify
     * @param array ...$params
     * @return mixed|null
     */
    public function handle(MockShopify $shopify, array ...$params)
    {
        if (is_callable($this->callback)) {
            return call_user_func_array($this->callback, array_merge([$shopify], ...$params));
        }

        return null;
    }
}
