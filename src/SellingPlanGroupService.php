<?php

namespace Irishdistillers\ShopifyStorefrontCheckout;

use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\BaseService;
use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\SellingPlanGroupInterface;
use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\ShopifyConstants;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Models\QueryModel;

class SellingPlanGroupService extends BaseService
{
    protected function useStoreFrontApi(): bool
    {
        return false;
    }

    protected function addShopifyGidPrefix(string $entityId, string $prefix): string
    {
        if (! str_starts_with($entityId, 'gid:')) {
            $entityId = $prefix.$entityId;
        }

        return $entityId;
    }

    protected function prepareShopifyIds(?array $entityIds, string $prefix): array
    {
        return array_map(function ($entityId) use ($prefix) {
            return $this->addShopifyGidPrefix($entityId, $prefix);
        }, $entityIds ?? []);
    }

    /**
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * Create selling plan group.
     *
     * @param array $options
     * @return string|bool
     */
    public function create(array $options)
    {
        $name = $options[SellingPlanGroupInterface::NAME] ?? null;
        $description = $options[SellingPlanGroupInterface::DESCRIPTION] ?? null;
        $merchantCode = $options[SellingPlanGroupInterface::MERCHANT_CODE] ?? null;
        $deposit = $options[SellingPlanGroupInterface::DEPOSIT] ?? null;
        $depositAmount = $options[SellingPlanGroupInterface::DEPOSIT_AMOUNT] ?? null;

        $remainingBalanceChargeTime = $options[SellingPlanGroupInterface::REMAINING_BALANCE_CHARGE_TIME] ?? null;
        $remainingBalanceChargeTrigger = $options[SellingPlanGroupInterface::REMAINING_BALANCE_CHARGE_TRIGGER] ?? null;
        $fulfillmentTrigger = $options[SellingPlanGroupInterface::FULFILLMENT_TRIGGER] ?? null;
        $inventoryReserve = $options[SellingPlanGroupInterface::INVENTORY_RESERVE] ?? null;
        $position = $options[SellingPlanGroupInterface::POSITION] ?? false;

        // Set pricing policy for discounts
        $discountType = $options[SellingPlanGroupInterface::DISCOUNT_TYPE] ?? null;
        $discount = $options[SellingPlanGroupInterface::DISCOUNT] ?? [];
        $pricingPolicies = [];

        if ($depositAmount) {
            $checkoutCharge = [
                'type' => 'PRICE',
                'value' => [
                    'amount' => $depositAmount,
                ],
            ];
        } else {
            $checkoutCharge = [
                'type' => 'PERCENTAGE',
                'value' => [
                    'percentage' => $deposit,
                ],
            ];
        }

        if ($discountType && $discount) {
            $pricingPolicies[] = [
                'fixed' => [
                    'adjustmentType' => $discountType,
                    'adjustmentValue' => $discountType === 'FIXED_AMOUNT' ?
                        [
                            'amount' => $depositAmount,
                        ] :
                        [
                            'percentage' => $deposit,
                        ],
                ],
            ];
        }

        $productIds = $this->prepareShopifyIds($options[SellingPlanGroupInterface::PRODUCT_IDS] ?? [], ShopifyConstants::GID_PRODUCT_PREFIX);
        $productVariantIds = $this->prepareShopifyIds($options[SellingPlanGroupInterface::PRODUCT_VARIANT_IDS] ?? [], ShopifyConstants::GID_PRODUCT_VARIANT_PREFIX);

        // Graphql
        $query = <<<'QUERY'
mutation sellingPlanGroupCreate($input: SellingPlanGroupInput!) {
  sellingPlanGroupCreate(input: $input) {
    sellingPlanGroup {
      id
    }
    userErrors {
      field
      message
    }
  }
}
QUERY;

        $variables = [
            'input' => [
                'name' => $name,
                'merchantCode' => $merchantCode,
                'options' => [
                    ucfirst($merchantCode),
                ],
                'sellingPlansToCreate' => [
                    'name' => $name,
                    'category' => 'PRE_ORDER',
                    'options' => 'Purchase Options with deposit',
                    'billingPolicy' => [
                        'fixed' => [
                            'checkoutCharge' => $checkoutCharge,
                            'remainingBalanceChargeExactTime' => $remainingBalanceChargeTime,
                            'remainingBalanceChargeTrigger' => $remainingBalanceChargeTrigger,
                        ],
                    ],
                    'deliveryPolicy' => [
                        'fixed' => [
                            'fulfillmentTrigger' => $fulfillmentTrigger,
                        ],
                    ],
                    'inventoryPolicy' => [
                        'reserve' => $inventoryReserve,
                    ],
                    'pricingPolicies' => $pricingPolicies,
                ],
            ],
            'resources' => [],
        ];

        if ($description) {
            $variables['input']['description'] = $description;
        }

        if ($position !== false) {
            $variables['input']['position'] = (int) $position;
        }

        // Query
        $data = $this->query(
            new QueryModel($query, $variables),
            'sellingPlanGroupCreate'
        );

        // Check result
        if ($data) {
            $sellingPlanGroupId = $data['sellingPlanGroup']['id'] ?? false;

            if ($sellingPlanGroupId) {
                // Add product IDs or product variant IDs
                if (! empty($productIds)) {
                    $this->addProducts($sellingPlanGroupId, $productIds);
                }

                if (! empty($productVariantIds)) {
                    $this->addProductVariants($sellingPlanGroupId, $productVariantIds);
                }

                return $sellingPlanGroupId;
            }
        }

        return false;
    }

    /**
     * Add products to selling plan group.
     *
     * @param $sellingPlanGroupId
     * @param array $productIds
     * @return bool
     */
    public function addProducts($sellingPlanGroupId, array $productIds): bool
    {
        $query = <<<'QUERY'
mutation sellingPlanGroupAddProducts($id: ID!, $productIds: [ID!]!) {
  sellingPlanGroupAddProducts(id: $id, productIds: $productIds) {
    sellingPlanGroup {
      id
    }
    userErrors {
      field
      message
    }
  }
}
QUERY;

        $variables = [
            'id' => $sellingPlanGroupId,
            'productIds' => $productIds,
        ];

        // Query
        $data = $this->query(
            new QueryModel($query, $variables),
            'SellingPlanGroupAddProductsPayload'
        );

        return $data['sellingPlanGroup']['id'] ?? false;
    }

    /**
     * Add product variants to selling plan group.
     *
     * @param $sellingPlanGroupId
     * @param array $productVariantIds
     * @return bool
     */
    public function addProductVariants($sellingPlanGroupId, array $productVariantIds): bool
    {
        $query = <<<'QUERY'
mutation sellingPlanGroupAddProductVariants($id: ID!, $productVariantIds: [ID!]!) {
  sellingPlanGroupAddProductVariants(id: $id, productVariantIds: $productVariantIds) {
    sellingPlanGroup {
      id
    }
    userErrors {
      field
      message
    }
  }
}
QUERY;

        $variables = [
            'id' => $sellingPlanGroupId,
            'productVariantIds' => $productVariantIds,
        ];

        // Query
        $data = $this->query(
            new QueryModel($query, $variables),
            'SellingPlanGroupAddProductVariantsPayload'
        );

        return $data['sellingPlanGroup']['id'] ?? false;
    }

    /**
     * Delete selling plan group.
     *
     * @param $sellingPlanGroupId
     * @return string|bool
     */
    public function remove($sellingPlanGroupId)
    {
        // Graphql
        $query = <<<'QUERY'
mutation sellingPlanGroupDelete($id: ID!) {
  sellingPlanGroupDelete(id: $id) {
    deletedSellingPlanGroupId
    userErrors {
      field
      message
    }
  }
}
QUERY;

        $variables = [
            'id' => $sellingPlanGroupId,
        ];

        // Query
        $data = $this->query(
            new QueryModel($query, $variables),
            'SellingPlanGroupDeletePayload'
        );

        // Check result
        if ($data) {
            return $data['deletedSellingPlanGroupId'] ?? false;
        }

        return false;
    }

    public function get(string $entityId): ?array
    {
        // Graphql
        $query = <<<'QUERY'
query SellingPlanGroup ($sellingPlanGroupId: ID!) {
  sellingPlanGroup(id: $sellingPlanGroupId) {
    id
    createdAt
    merchantCode
    name
    description
    options
    position
    productCount
    summary
    products(first: 10) {
      edges {
        node {
          id
          title
        }
      }
    }
    productVariants(first: 10) {
      edges {
        node {
          id
          title
        }
      }
    }
    sellingPlans(first: 10) {
      edges {
        node {
          id
          billingPolicy {
            ... on SellingPlanFixedBillingPolicy {
              checkoutCharge {
                  type
                  value {
                      ... on SellingPlanCheckoutChargePercentageValue {
                          percentage
                      }
                  }
              }
              remainingBalanceChargeExactTime
              remainingBalanceChargeTimeAfterCheckout
              remainingBalanceChargeTrigger
            }
          }
          category
          createdAt
          deliveryPolicy {
            ... on SellingPlanFixedDeliveryPolicy {
              cutoff
              fulfillmentExactTime
              fulfillmentTrigger
              intent
              preAnchorBehavior
            }
          }
          inventoryPolicy {
              reserve
          }
          name
          options
          pricingPolicies {
            ... on SellingPlanFixedPricingPolicy {
              adjustmentType
              adjustmentValue {
                ... on SellingPlanPricingPolicyPercentageValue {
                  percentage
                }
              }
            }
          }
        }
      }
    }
  }
}
QUERY;

        // Query
        $data = $this->query(
            new QueryModel($query, ['sellingPlanGroupId' => $entityId]),
            'sellingPlanGroup'
        );

        // Check result
        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * List available selling plan groups.
     *
     * @return array|bool
     */
    public function list()
    {
        // Graphql
        $query = <<<'QUERY'
query SellingPlanGroupsList {
  sellingPlanGroups(first: 10) {
    edges {
      cursor
      node {
        id
        createdAt
        merchantCode
        name
        description
        options
        position
        productCount
        summary
        products(first: 10) {
          edges {
            node {
              id
              title
            }
          }
        }
        sellingPlans(first: 10) {
          edges {
            node {
              id
              billingPolicy {
                ... on SellingPlanFixedBillingPolicy {
                  checkoutCharge {
                      type
                      value {
                          ... on SellingPlanCheckoutChargePercentageValue {
                              percentage
                          }
                      }
                  }
                  remainingBalanceChargeExactTime
                  remainingBalanceChargeTimeAfterCheckout
                  remainingBalanceChargeTrigger
                }
              }
              category
              createdAt
              deliveryPolicy {
                ... on SellingPlanFixedDeliveryPolicy {
                  cutoff
                  fulfillmentExactTime
                  fulfillmentTrigger
                  intent
                  preAnchorBehavior
                }
              }
              inventoryPolicy {
                  reserve
              }
              name
              options
              pricingPolicies {
                ... on SellingPlanFixedPricingPolicy {
                  adjustmentType
                  adjustmentValue {
                    ... on SellingPlanPricingPolicyPercentageValue {
                      percentage
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}
QUERY;

        // Query
        $data = $this->query(
            new QueryModel($query),
            'sellingPlanGroups'
        );

        // Check result
        if ($data) {
            $edges = $data['edges'] ?? false;
            if ($edges) {
                return array_map(function ($node) {
                    return $node['node'];
                }, $edges);
            }
        }

        return false;
    }
}
