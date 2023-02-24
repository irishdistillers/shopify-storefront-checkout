<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify;

class MockStore
{
    protected static array $store = [];

    /**
     * Store object.
     *
     * @param string $prefix
     * @param string $id
     * @param array|null $data
     * @return void
     */
    public function set(string $prefix, string $id, ?array $data)
    {
        self::$store[$prefix][$id] = $data;
    }

    /**
     * Get object.
     *
     * @param string $prefix
     * @param string $id
     * @return array|null
     */
    public function get(string $prefix, string $id): ?array
    {
        return self::$store[$prefix][$id] ?? null;
    }

    /**
     * Check if object exists.
     *
     * @param string $prefix
     * @param string $id
     * @return bool
     */
    public function has(string $prefix, string $id): bool
    {
        return isset(self::$store[$prefix][$id]);
    }
}
