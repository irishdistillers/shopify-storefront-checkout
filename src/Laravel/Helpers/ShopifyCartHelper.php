<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Laravel\Helpers;

use ArrayObject;
use Illuminate\Support\Facades\App;
use Irishdistillers\ShopifyStorefrontCheckout\Cart;
use Irishdistillers\ShopifyStorefrontCheckout\CartService;
use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\ShopifyAccountInterface;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockGraphql;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;

/**
 * @codeCoverageIgnore
 */
class ShopifyCartHelper
{
    protected static ?MockGraphql $mock = null;

    public static function getContext(?ArrayObject $config = null): Context
    {
        return Context::createFromConfig($config ?? new ArrayObject(), [
            config('storefront-checkout.shop_base_url'),
            config('storefront-checkout.api_version', ShopifyAccountInterface::DEFAULT_API_VERSION),
            config('storefront-checkout.store_front_access_token'),
            config('storefront-checkout.app_signature'),
        ]);
    }

    protected static function getMock(Context $context, bool $mock = false): ?array
    {
        // Automatically use mock Graphql if running unit tests or set SHOPIFY_MOCK=1 in configuration
        $isMock = $mock || App::runningUnitTests() || config('storefront-checkout.mock') || 'dusk' === env('APP_ENV');
        if ($isMock) {
            // Mock is singleton, in order to store temporarily graphql values in unit tests
            if (! self::$mock) {
                self::$mock = new MockGraphql($context);
            }

            return self::$mock->getEndpoints();
        }

        return null;
    }

    public static function getNewCartService(?ArrayObject $config = null, bool $mock = false): CartService
    {
        $context = self::getContext($config);

        return new CartService(
            $context,
            app('log')->driver()->getLogger(),
            self::getMock($context, $mock)
        );
    }

    public static function getNewCart(?ArrayObject $config = null, bool $mock = false): Cart
    {
        $context = self::getContext($config);

        return new Cart(
            $context,
            app('log')->driver()->getLogger(),
            self::getMock($context, $mock)
        );
    }
}
