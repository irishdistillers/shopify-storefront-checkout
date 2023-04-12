<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify;

use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockShopify;
use Irishdistillers\ShopifyStorefrontCheckout\Traits\ShopifyUtilsTrait;

class MockSellingPlans
{
    use ShopifyUtilsTrait;

    protected MockShopify $shopify;

    protected const SELLING_PLAN_PREFIX = 'gid://shopify/SellingPlan/';

    public function __construct(MockShopify $shopify)
    {
        $this->shopify = $shopify;
    }

    protected function getEntity(array $options, array $inject = []): array
    {
        return array_merge([
            'createdAt' => date('Y-m-d\TH:i:s\Z'),
            'updatedAt' => date('Y-m-d\TH:i:s\Z'),
            'billingPolicy' => $options['billingPolicy'] ?? [],
            'category' => $options['category'] ?? [],
            'deliveryPolicy' => $options['deliveryPolicy'] ?? [],
            'description' => $options['description'] ?? [],
            'inventoryPolicy' => $options['inventoryPolicy'] ?? [],
            'name' => $options['name'] ?? [],
            'options' => $options['options'] ?? [],
            'position' => $options['position'] ?? [],
            'pricingPolicies' => $options['pricingPolicies'] ?? [],
        ], $inject);
    }

    public function create(array $options, ?string $forceEntityId = null): ?array
    {
        // Create random selling plan group
        $entityId = $forceEntityId ?: $this->shopify->ids()->createRandomId(self::SELLING_PLAN_PREFIX);
        $entity = $this->getEntity($options, [
            'id' => $this->encode($entityId),
        ]);

        // Store updated cart
        $this->shopify->store()->set(self::SELLING_PLAN_PREFIX, $entityId, $entity);

        return $entity;
    }

    public function delete(string $entityId): bool
    {
        return $this->shopify->store()->delete(self::SELLING_PLAN_PREFIX, $entityId);
    }

    public function update(string $entityId, array $options): ?array
    {
        $entity = $this->shopify->store()->get(self::SELLING_PLAN_PREFIX, $entityId);
        if ($entity) {
            unset($options['id']);
            $entity = $this->getEntity($entity, $options);
            $this->shopify->store()->set(self::SELLING_PLAN_PREFIX, $entityId, $entity);

            return $entity;
        }

        return null;
    }

    public function get(string $entityId): ?array
    {
        return $this->shopify->store()->get(self::SELLING_PLAN_PREFIX, $entityId);
    }
}
