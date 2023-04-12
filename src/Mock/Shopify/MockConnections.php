<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify;

use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockShopify;

class MockConnections
{
    protected MockShopify $mockShopify;

    protected const CONNECTION_PREFIX = 'connection@';

    public function __construct(MockShopify $mockShopify)
    {
        $this->mockShopify = $mockShopify;
    }

    protected function get(string $mainEntityId, string $connectionId): array
    {
        return $this->mockShopify->store()->get(
            self::CONNECTION_PREFIX.$mainEntityId,
            $connectionId
        ) ?? [];
    }

    protected function store(string $mainEntityId, string $connectionId, array $connections)
    {
        $this->mockShopify->store()->set(self::CONNECTION_PREFIX.$mainEntityId, $connectionId, $connections);
    }

    public function connect(string $mainEntityId, string $entityId, string $connectionId): array
    {
        // Get connections
        $connections = $this->get($mainEntityId, $connectionId);

        // Add / update connection
        $connections[$entityId] = $entityId;

        // Store
        $this->store($mainEntityId, $connectionId, $connections);

        return $connections;
    }

    public function disconnect(string $mainEntityId, string $entityId, string $connectionId): array
    {
        // Get connections
        $connections = $this->get($mainEntityId, $connectionId);

        // Remove connection
        unset($connections[$entityId]);

        // Store
        $this->store($mainEntityId, $connectionId, $connections);

        return $connections;
    }

    public function getConnections(string $mainEntityId, string $connectionId): array
    {
        return $this->get($mainEntityId, $connectionId);
    }
}
