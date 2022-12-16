# Shopify Storefront Checkout

![build-test](coverage.svg)

## Description

Library that embeds Shopify Storefront Checkout

Details:

- Storefront API version: `2022-04`

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

Pros:
- cart service allows to handle multiple carts at the same time

Cons:
- storing and passing cart ID and country code (market) to methods can be an overhead 

### Cart object

- cart object is a wrapper for the cart service
- cart ID and country code (market) are stored in the object itself

Pros:
- it's not necessary to pass cart ID and country code (market) to each function

Cons:
- cart object allows to handle a unique cart per instance

### Sample code

#### Cart service

```php
use Irishdistillers\ShopifyStorefrontCheckout\CartService;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;

// Set StoreFront access token
$storeFrontAccessToken = 'b3f1f61693cae*******************';

// Create context
$context = new Context('my_shop.shopify.com', '2022-04', $storeFrontAccessToken);

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
$context = new Context('my_shop.shopify.com', '2022-04', $storeFrontAccessToken);

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

