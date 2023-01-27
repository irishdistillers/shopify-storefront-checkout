# Shopify Storefront Checkout

![build-test](coverage.svg)

## Description

Library that embeds Shopify Storefront Checkout

Details:

- Storefront API version: `2023-01` (latest)

## Install

### Add private repository to Composer

Edit `composer.json`

Add what follows in the `repository` section:

```
{
    "type": "vcs",
    "url":  "git@github.com:irishdistillers/shopify-storefront-checkout.git"
}
```

### Require package

```shell
composer require irishdistillers/shopify-storefront-checkout
```

## Develop

### Mocking Shopify and Graphql

A mocked version of Graphql is available in `/Mock`. It can be extended, if needed.

### Tests

```shell
# Run unit tests
composer test

# Run coverage
composer test-coverage

# Run coverage and update badge
composer test-badge
```

### Code formatting

```shell
composer php-cs-fixer
```

### Versioning

```shell
git add .
git commit -m "Version 1.2.3" # Message for the version
git tag -a v1.2.3 # Specify changes done in the new version
git push origin master --tags
```

## Usage

You can access cart and checkout in two ways:

- using the cart service
- using the cart object

### Cart service

- each operation will return a cart ID
- cart ID can be used to change cart items and attributes, and to get checkout URL

When to use it:

- you need to handle different carts at the same time

Pros:

- cart service allows to handle **multiple** carts at the same time

Cons:

- storing and passing cart ID and country code (market) to methods **can be an overhead **

### Cart object

- cart object is a wrapper for the cart service
- cart ID and country code (market) are stored in the object itself

When to use it:

- you need to handle a single cart

Pros:

- it's not necessary to pass cart ID and country code (market) to each function

Cons:

- cart object allows to handle **a unique cart** per instance

### Sample code

#### Cart service

```php
use Irishdistillers\ShopifyStorefrontCheckout\CartService;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;

// Set StoreFront access token
$storeFrontAccessToken = 'b3f1f61693cae*******************';

// Create context
$context = new Context('my_shop.shopify.com', '2023-01', $storeFrontAccessToken);

// Create cart service
$cartService = new CartService($context);

// Create new cart, for Ireland market
$cartId = $cartService->getNewCart('IE');

// Add products to the cart
$cartService->addLines($cartId, [
    ['gid://shopify/ProductVariant/1234567890' => 1],
]);

// Add notes to the cart
$cartService->updateNote($cartId, 'This is a note');

// Add attributes to the cart
$cartService->updateAttributes($cartId, 'key', 'This is a value');

// Add discount codes to the cart
$cartService->updateDiscountCodes($cartId, 'TENPERCENT');

// Get checkout URL for United Kingdom market
$checkoutUrl = $cartService->getCheckoutUrl($cartId, 'GB');

// Checkout URL is immutable. Once paid, the URL won't work anymore.
```

#### Cart object

```php
use Irishdistillers\ShopifyStorefrontCheckout\Cart;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;

// Set StoreFront access token
$storeFrontAccessToken = 'b3f1f61693cae*******************';

// Create context
$context = new Context('my_shop.shopify.com', '2023-01', $storeFrontAccessToken);

// Create cart object
$cartObj = new Cart($context);

// Set Ireland market (optional; IE market is set by default)
$cartObj->setCountryCode('IE');

// Create new cart
$cartObj->getNewCart();

// Add products to the cart
$cartObj->addLines([
    ['gid://shopify/ProductVariant/1234567890' => 1],
]);

// Add notes to the cart
$cartObj->updateNote('This is a note');

// Add attributes to the cart
$cartObj->updateAttributes('key', 'This is a value');

// Add discount codes to the cart
$cartObj->updateDiscountCodes('TENPERCENT');

// Change market to the United Kingdom and get checkout URL
$checkoutUrl = $cartObj->setCountryCode('GB')->getCheckoutUrl();

// Checkout URL is immutable. Once paid, the URL won't work anymore.
```

## Laravel integration

Laravel integration is not unit tested.

**Important:** Laravel is not required in composer, to allow this package to be generic and usable e.g. in WordPress.

### Configuration

**Important:** Configuration is optional. It can be overridden, when creating cart service or cart object.

`.env`

```dotenv
# API version
SHOPIFY_API_VERSION=2023-01

# Only for admin Graphql API. Not used by cart.
SHOPIFY_APP_SECRET=replace_me

# Storefront Access token. Required by cart
SHOPIFY_STORE_FRONT_ACCESS_TOKEN=replace_me

# Base URL of the shop
SHOPIFY_SHOP_BASE_URL="shop_name.myshopify.com"

# Mock Graphql. In Laravel unit tests, mock is automatically enabled. 
SHOPIFY_MOCK="0"
```

`config/storefront-checkout.php`

```php
<?php
return [

    // Only if using admin Graphql API
    'app_signature' => env('SHOPIFY_APP_SECRET'),
    
    // Storefront access token
    'store_front_access_token' => env('SHOPIFY_STORE_FRONT_ACCESS_TOKEN'),
    
    // Shop base URL, e.g. "my-shop-name.myshopify.com"
    'shop_base_url' => env('SHOPIFY_SHOP_BASE_URL'),
    
    // API version. Use 2023-01 or greater
    'api_version' => env('SHOPIFY_API_VERSION'),
    
    // This will enable mock mode
    'mock' => (bool) env('SHOPIFY_MOCK'),
    
];
```

### Usage

#### Helper

Use `ShopifyCartHelper` to create a cart service or a cart object.

**Scenario 1: load configuration from `.env`**

It will load configuration, as described above.

```php
use Irishdistillers\ShopifyStorefrontCheckout\Laravel\Helpers\ShopifyCartHelper;

// Create cart service, loading .env configuration
$cartService = ShopifyCartHelper::getNewCartService();

// Create cart object, loading .env configuration
$cartObject = ShopifyCartHelper::getNewCart();
```

**Scenario 2: assign dynamically configuration**

```php
use ArrayObject;
use Irishdistillers\ShopifyStorefrontCheckout\Laravel\Helpers\ShopifyCartHelper;

// Create cart service, assigning dynamically configuration
$cartService = ShopifyCartHelper::getNewCartService(new ArrayObject([
    ShopifyAccountInterface::SHOPIFY_BASE_URL => 'dummy.shopify.com',
    ShopifyAccountInterface::API_VERSION => '2022-01',
    ShopifyAccountInterface::STOREFRONT_ACCESS_TOKEN => 'dummy_store_front_token',
    ShopifyAccountInterface::APP_SIGNATURE => 'dummy_access_token',
]));

// Create cart object, assigning dynamically configuration
$cartObject = ShopifyCartHelper::getNewCart(new ArrayObject([
    ShopifyAccountInterface::SHOPIFY_BASE_URL => 'dummy.shopify.com',
    ShopifyAccountInterface::API_VERSION => '2022-01',
    ShopifyAccountInterface::STOREFRONT_ACCESS_TOKEN => 'dummy_store_front_token',
    ShopifyAccountInterface::APP_SIGNATURE => 'dummy_access_token',
]));
```

#### Console command

A Laravel command for the cart is already available.

Simply extend it in your project, assigning command signature and description.

```php
use Irishdistillers\ShopifyStorefrontCheckout\Laravel\Console\Commands\ShopifyCartCommand;

class ShopifyCart extends ShopifyCartCommand
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify:cart';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shopify cart create';
    
}
```

Run it

```shell
php artisan shopify:cart
```

