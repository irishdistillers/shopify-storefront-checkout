<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Laravel\Helpers;

use Illuminate\Support\Facades\App;
use Irishdistillers\ShopifyStorefrontCheckout\Cart;
use Irishdistillers\ShopifyStorefrontCheckout\CartService;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockGraphql;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;

/**
 * @codeCoverageIgnore
 */
class ShopifyCartHelper
{
    protected static ?MockGraphql $mock = null;

    public static function getContext(): Context
    {
        return new Context(
            config('shopify.shop.shop_base_url'),
            config('shopify.shop.api_version'),
            config('shopify.credentials.store_front_access_token'),
            config('shopify.credentials.app_signature')
        );
    }

    protected static function getMock(Context $context): ?array
    {
        // Automatically use mock Graphql if running unit tests or set SHOPIFY_MOCK=1 in configuration
        $isMock = App::runningUnitTests() || config('shopify.mock') || 'dusk' === env('APP_ENV');
        if ($isMock) {
            // Mock is singleton, in order to store temporarily graphql values in unit tests
            if (! self::$mock) {
                self::$mock = new MockGraphql($context);
            }

            return self::$mock->getEndpoints();
        }

        return null;
    }

    public static function getNewCartService(): CartService
    {
        $context = self::getContext();

        return new CartService(
            $context,
            app('log')->driver()->getLogger(),
            self::getMock($context)
        );
    }

    public static function getNewCart(): Cart
    {
        $context = self::getContext();

        return new Cart(
            $context,
            app('log')->driver()->getLogger(),
            self::getMock($context)
        );
    }
}
