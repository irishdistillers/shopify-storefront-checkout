<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify;

use Exception;

class MockIds
{
    protected const MAX_ATTEMPTS = 20;

    protected static array $randomIds = [];

    /**
     * Generate random ID and store it, to avoid clashes.
     *
     * @param string $prefix Shopify prefix, e.g. 'gid://shopify/ProductImage/'
     * @return string
     * @throws Exception
     */
    public function createRandomId(string $prefix): string
    {
        if (! $prefix) {
            throw new Exception('Unable to generate random ID: prefix is empty');
        }

        // Add slash suffix
        if (substr($prefix, -1, 1) !== '/') {
            $prefix .= '/';
        }

        // Initialise prefix store
        if (! isset(self::$randomIds[$prefix])) {
            self::$randomIds[$prefix] = [];
        }

        // Generate unique ID, with max attempts to avoid never-ending loop.
        $attempts = 0;
        while (true) {
            $id = md5(uniqid());
            if (! in_array($id, self::$randomIds)) {
                self::$randomIds[] = $id;

                return $prefix.$id;
            }

            $attempts++;
            if ($attempts > self::MAX_ATTEMPTS) {
                throw new Exception('Unable to generate a random ID: reached max attempts.');
            }
        }
    }
}
