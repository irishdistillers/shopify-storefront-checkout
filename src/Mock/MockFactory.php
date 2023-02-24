<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock;

use Irishdistillers\ShopifyStorefrontCheckout\Mock\Factory\MockFactoryHandler;

class MockFactory
{
    public const FACTORY_CART_CREATE = 'cartCreate';

    /** @var MockFactoryHandler[] */
    protected array $factories;

    /**
     * @param MockFactoryHandler[] $factories
     */
    public function __construct(array $factories = [])
    {
        $this->factories = array_merge($this->getDefaultFactories(), $factories);
    }

    /**
     * Get default factories.
     *
     * @return MockFactoryHandler[]
     */
    protected function getDefaultFactories(): array
    {
        return [
            // Create cart
            self::FACTORY_CART_CREATE => new MockFactoryHandler(function (MockShopify $shopify, string $cartId, ?string $newCountryCode) {
                // Create cart, if not existing
                if (! $shopify->cart()->get($cartId, $newCountryCode)) {
                    return $shopify->cart()->create($newCountryCode, $cartId);
                }

                return null;
            }),
        ];
    }

    /**
     * Register handler.
     *
     * @param $id
     * @param MockFactoryHandler $handler
     * @return $this
     */
    public function register($id, MockFactoryHandler $handler): self
    {
        $this->factories[$id] = $handler;

        return $this;
    }

    /**
     * Handle factory, if registered.
     *
     * @param $factory
     * @param MockShopify $shopify
     * @param ...$params
     * @return mixed|null
     */
    public function handle($factory, MockShopify $shopify, ...$params)
    {
        /** @var ?MockFactoryHandler $handler */
        $handler = $this->factories[$factory] ?? null;

        if ($handler) {
            return $handler->handle($shopify, $params);
        }

        return null;
    }
}
