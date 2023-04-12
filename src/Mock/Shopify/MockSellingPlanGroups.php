<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify;

use Exception;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\Exceptions\MockGraphqlValidationException;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockShopify;
use Irishdistillers\ShopifyStorefrontCheckout\Traits\ShopifyUtilsTrait;

class MockSellingPlanGroups
{
    use ShopifyUtilsTrait;

    protected MockShopify $shopify;

    public const SELLING_PLAN_GROUP_PREFIX = 'gid://shopify/SellingPlanGroup/';

    protected const CONNECTION_SELLING_PLANS = 'sellingPlans';

    protected const CONNECTION_PRODUCTS = 'products';

    protected const CONNECTION_VARIANTS = 'variants';

    public function __construct(MockShopify $shopify)
    {
        $this->shopify = $shopify;
    }

    /**
     * @throws MockGraphqlValidationException
     */
    protected function validateCreate(array $entity)
    {
        // Validate mandatory fields
        $validationFailed = [];
        foreach (['name', 'merchantCode'] as $requiredField) {
            if (! $entity[$requiredField]) {
                $validationFailed[] = $requiredField;
            }
        }

        if (count($validationFailed)) {
            throw new MockGraphqlValidationException($validationFailed);
        }
    }

    /**
     * @param string $entityId
     * @return string|null
     * @throws MockGraphqlValidationException
     */
    protected function getEntityId(string $entityId): ?string
    {
        $entityId = $this->decode($entityId);

        if (! $entityId || ! $this->shopify->store()->has(self::SELLING_PLAN_GROUP_PREFIX, $entityId)) {
            throw new MockGraphqlValidationException([
                [
                    'field' => 'id',
                    'message' => 'Non existing',
                ],
            ]);
        }

        return $entityId;
    }

    /**
     * @throws Exception
     */
    protected function injectConnections(string $entityId, array $entity): array
    {
        // Add connections
        $entity['sellingPlans'] = [];
        foreach ($this->shopify->connections()->getConnections($entityId, self::CONNECTION_SELLING_PLANS) as $sellingPlanId) {
            $entity['sellingPlans'][] = $this->shopify->sellingPlans()->get($sellingPlanId);
        }

        $entity['products'] = [];
        foreach ($this->shopify->connections()->getConnections($entityId, self::CONNECTION_PRODUCTS) as $productId) {
            $entity['products'][] = $this->shopify->products()->getProduct($productId);
        }
        $entity['productCount'] = count($entity['products']);

        $entity['productVariants'] = [];
        foreach ($this->shopify->connections()->getConnections($entityId, self::CONNECTION_VARIANTS) as $variantId) {
            $entity['productVariants'][] = $this->shopify->products()->getVariant($variantId);
        }
        $entity['productVariantCount'] = count($entity['productVariants']);

        return $entity;
    }

    /**
     * @throws Exception
     */
    public function create(array $options, ?string $forceEntityId = null): ?array
    {
        // Create random selling plan group
        $entityId = $forceEntityId ?: $this->shopify->ids()->createRandomId(self::SELLING_PLAN_GROUP_PREFIX);
        $entity = [
            'id' => $this->encode($entityId),
            'createdAt' => date('Y-m-d\TH:i:s\Z'),
            'updatedAt' => date('Y-m-d\TH:i:s\Z'),
            'appId' => $options['appId'] ?? null,
            'appliesToProduct' => false,
            'appliesToProductVariant' => false,
            'appliesToProductVariants' => false,
            'description' => $options['description'] ?? null,
            'merchantCode' => $options['merchantCode'] ?? null,
            'name' => $options['name'] ?? null,
            'options' => $options['options'] ?? [],
            'position' => $options['position'] ?? null,
            'productCount' => 0,
            'productVariantCount' => 0,
            'summary' => $options['summary'] ?? null,
        ];

        // Validate mandatory fields
        $this->validateCreate($entity);

        // Store entity
        $this->shopify->store()->set(self::SELLING_PLAN_GROUP_PREFIX, $entityId, $entity);

        // Handle connections (selling plans to create / delete / update)
        $sellingPlansToCreate = $options['sellingPlansToCreate'] ?? [];
        $sellingPlansToDelete = $options['sellingPlansToDelete'] ?? [];
        $sellingPlansToUpdate = $options['sellingPlansToUpdate'] ?? [];

        if (! empty($sellingPlansToCreate)) {
            // Create selling plan
            $sellingPlan = $this->shopify->sellingPlans()->create($sellingPlansToCreate);

            // Create connection
            $this->shopify->connections()->connect(
                $entityId,
                $this->decode($sellingPlan['id']),
                self::CONNECTION_SELLING_PLANS
            );
        }

        if (! empty($sellingPlansToDelete)) {
            $sellingPlanId = $this->decode($sellingPlansToDelete['id']);

            // Delete selling plan
            $this->shopify->sellingPlans()->delete($sellingPlanId);

            // Remove connection
            $this->shopify->connections()->disconnect(
                $entityId,
                $sellingPlanId,
                self::CONNECTION_SELLING_PLANS
            );
        }

        if (! empty($sellingPlansToUpdate)) {
            $sellingPlanId = $this->decode($sellingPlansToUpdate['id']);

            // Update selling plan
            $this->shopify->sellingPlans()->update(
                $sellingPlanId,
                $sellingPlansToUpdate
            );

            // Update connection
            $this->shopify->connections()->connect(
                $entityId,
                $sellingPlanId,
                self::CONNECTION_SELLING_PLANS
            );
        }

        // Inject connections
        return $this->injectConnections(
            $entityId,
            $this->shopify->store()->get(self::SELLING_PLAN_GROUP_PREFIX, $entityId)
        );
    }

    /**
     * @throws MockGraphqlValidationException
     */
    public function delete(string $entityId): bool
    {
        $entityId = $this->getEntityId($entityId);

        return $this->shopify->store()->delete(self::SELLING_PLAN_GROUP_PREFIX, $entityId);
    }

    /**
     * @throws Exception|MockGraphqlValidationException
     */
    public function get(string $entityId): ?array
    {
        $entityId = $this->getEntityId($entityId);

        // Inject connections
        return $this->injectConnections(
            $entityId,
            $this->shopify->store()->get(self::SELLING_PLAN_GROUP_PREFIX, $entityId)
        );
    }

    /**
     * @throws Exception|MockGraphqlValidationException
     */
    public function addProducts(string $entityId, array $productIds): ?array
    {
        $entityId = $this->getEntityId($entityId);

        // Add connections
        foreach ($productIds as $productId) {
            $this->shopify->connections()->connect($entityId, $productId, self::CONNECTION_PRODUCTS);
        }

        // Inject connections
        return $this->injectConnections(
            $entityId,
            $this->shopify->store()->get(self::SELLING_PLAN_GROUP_PREFIX, $entityId)
        );
    }

    /**
     * @throws Exception|MockGraphqlValidationException
     */
    public function addProductVariants(string $entityId, array $productVariantIds): ?array
    {
        $entityId = $this->getEntityId($entityId);

        // Add connections
        foreach ($productVariantIds as $productVariantId) {
            $this->shopify->connections()->connect($entityId, $productVariantId, self::CONNECTION_VARIANTS);
        }

        // Inject connections
        return $this->injectConnections(
            $entityId,
            $this->shopify->store()->get(self::SELLING_PLAN_GROUP_PREFIX, $entityId)
        );
    }

    /**
     * @param int $start
     * @param int|null $limit
     * @return array
     * @throws Exception
     */
    public function list(int $start = 0, ?int $limit = null): array
    {
        $entities = array_values($this->shopify->store()->all(self::SELLING_PLAN_GROUP_PREFIX, $start, $limit));

        foreach ($entities as $key => $entity) {
            $entityId = $this->decode($entity['id']);
            $entities[$key] = $this->injectConnections(
                $entityId,
                $this->shopify->store()->get(self::SELLING_PLAN_GROUP_PREFIX, $entityId)
            );
        }

        return $entities;
    }
}
