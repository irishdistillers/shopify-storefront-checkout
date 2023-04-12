<?php

namespace Irishdistillers\ShopifyStorefrontCheckout;

use Exception;
use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\ShopifyConstants;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockGraphql;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Graphql;
use Monolog\Logger;

class SellingPlanGroupService
{
    protected Context $context;

    protected Graphql $graphql;

    protected array $errorMessages = [];

    protected ?Logger $logger;

    public function __construct(Context $context, ?Logger $logger = null, ?array $mock = null)
    {
        $this->context = $context;
        $this->logger = $logger;
        $this->graphql = new Graphql(
            $context,
            false,
            $this->logger,
            $mock
        );
    }

    /**
     * @param string $query
     * @param array $variables
     * @param string $field
     * @return array|bool
     */
    protected function query(string $query, array $variables, string $field)
    {
        $this->errorMessages = [];
        try {
            // Run query
            $data = $this->graphql->query($query, $variables);

            // Check errors
            $errors = $data[$field]['userErrors'] ?? [];
            if (count($errors)) {
                $this->errorMessages = $errors;
            } else {
                if ($data) {
                    return $data[$field] ?? false;
                }
                $this->errorMessages[] = 'Empty response';
            }
        } catch (Exception $e) {
            $this->errorMessages[] = $e->getMessage();
        }

        if ($this->logger) {
            $this->logger->error('ShopifySellingPlan.query failed', [
                'query' => $query,
                'variables' => $variables,
                'field' => $field,
                'errors' => $this->errorMessages,
            ]);
        }

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

    public function errors(): array
    {
        return $this->errorMessages;
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
        $name = $options['name'] ?? null;
        $description = $options['description'] ?? null;
        $merchantCode = $options['merchantCode'] ?? null;
        $deposit = $options['deposit'] ?? null;
        $remainingBalanceChargeTime = $options['remainingBalanceChargeTime'] ?? null;
        $remainingBalanceChargeTrigger = $options['remainingBalanceChargeTrigger'] ?? null;
        $fulfillmentTrigger = $options['fulfillmentTrigger'] ?? null;
        $inventoryReserve = $options['inventoryReserve'] ?? null;
        $position = $options['position'] ?? false;

        $productIds = $this->prepareShopifyIds($options['productIds'] ?? [], ShopifyConstants::GID_PRODUCT_PREFIX);
        $productVariantIds = $this->prepareShopifyIds($options['productVariantIds'] ?? [], ShopifyConstants::GID_PRODUCT_VARIANT_PREFIX);

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
                            'checkoutCharge' => [
                                'type' => 'PERCENTAGE',
                                'value' => [
                                    'percentage' => $deposit,
                                ],
                            ],
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
            $query,
            $variables,
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
            $query,
            $variables,
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
            $query,
            $variables,
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
            $query,
            $variables,
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
query sellingPlanGroup($sellingPlanGroupId: ID!) {
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
QUERY;

        // Query
        $data = $this->query(
            $query,
            [
                'sellingPlanGroupId' => $entityId,
            ],
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
            $query,
            [],
            'SellingPlanGroupConnection'
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
