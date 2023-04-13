<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Laravel\Helpers;

use ArrayObject;
use Exception;
use Illuminate\Support\Facades\App;
use Irishdistillers\ShopifyStorefrontCheckout\Cart;
use Irishdistillers\ShopifyStorefrontCheckout\CartService;
use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\LogLevelConstants;
use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\ShopifyAccountInterface;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockFactory;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockGraphql;
use Irishdistillers\ShopifyStorefrontCheckout\SellingPlanGroupService;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;

/**
 * @codeCoverageIgnore
 */
class ShopifyHelper
{
    protected static ?MockGraphql $mock = null;

    /**
     * @throws Exception
     */
    public static function getContext(?ArrayObject $config = null): Context
    {
        return Context::createFromConfig($config ?? new ArrayObject(), [
            ShopifyAccountInterface::SHOPIFY_BASE_URL => config('storefront-checkout.shop_base_url'),
            ShopifyAccountInterface::API_VERSION => config('storefront-checkout.api_version', ShopifyAccountInterface::DEFAULT_API_VERSION),
            ShopifyAccountInterface::STOREFRONT_ACCESS_TOKEN => config('storefront-checkout.store_front_access_token'),
            ShopifyAccountInterface::APP_SIGNATURE => config('storefront-checkout.app_signature'),
        ]);
    }

    protected static function getMock(Context $context, bool $mock = false, bool $factory = false): ?array
    {
        // Automatically use mock Graphql if running unit tests or set SHOPIFY_MOCK=1 in configuration
        $isMock = $mock || App::runningUnitTests() || config('storefront-checkout.mock') || 'dusk' === env('APP_ENV');
        if ($isMock) {
            // Mock is singleton, in order to store temporarily graphql values in unit tests
            if (! self::$mock) {
                self::$mock = new MockGraphql($context, $factory ? new MockFactory() : null);
            }

            return self::$mock->getEndpoints();
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public static function getNewCartService(?ArrayObject $config = null, bool $mock = false, bool $factory = false, int $logLevel = LogLevelConstants::LOG_LEVEL_NORMAL): CartService
    {
        $context = self::getContext($config);

        return new CartService(
            $context,
            app('log')->driver()->getLogger(),
            self::getMock($context, $mock, $factory),
            $logLevel
        );
    }

    /**
     * @throws Exception
     */
    public static function getNewCart(?ArrayObject $config = null, bool $mock = false, bool $factory = false, int $logLevel = LogLevelConstants::LOG_LEVEL_NORMAL): Cart
    {
        $context = self::getContext($config);

        return new Cart(
            $context,
            app('log')->driver()->getLogger(),
            self::getMock($context, $mock, $factory),
            $logLevel
        );
    }

    /**
     * @throws Exception
     */
    public static function getNewSellingPlanGroupService(?ArrayObject $config = null, bool $mock = false, bool $factory = false, int $logLevel = LogLevelConstants::LOG_LEVEL_NORMAL): SellingPlanGroupService
    {
        $context = self::getContext($config);

        return new SellingPlanGroupService(
            $context,
            app('log')->driver()->getLogger(),
            self::getMock($context, $mock, $factory),
            $logLevel
        );
    }
}
