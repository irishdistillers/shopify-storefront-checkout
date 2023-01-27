<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Interfaces;

interface ShopifyAccountInterface
{
    public const SHOPIFY_BASE_URL = 'shopify_base_url';

    public const API_VERSION = 'api_version';

    public const STOREFRONT_ACCESS_TOKEN = 'storefront_access_token';

    public const APP_SIGNATURE = 'app_signature';

    public const DEFAULT_API_VERSION = '2023-01';
}
