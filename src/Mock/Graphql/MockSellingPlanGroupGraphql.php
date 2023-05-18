<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Graphql;

use Exception;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\Exceptions\MockGraphqlValidationException;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\Graphql\Query\MockGraphqlQuery;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\Traits\MockGraphqlUserErrorAwareTrait;
use Irishdistillers\ShopifyStorefrontCheckout\Traits\ShopifyUtilsTrait;

class MockSellingPlanGroupGraphql extends MockBaseGraphql
{
    use ShopifyUtilsTrait, MockGraphqlUserErrorAwareTrait;

    /**
     * @param string|null $query
     * @param array|null $variables
     * @return array|null
     * @throws Exception
     */
    public function sellingPlanGroupGet(?string $query, ?array $variables): ?array
    {
        // Parse graphql query
        $graphqlQuery = new MockGraphqlQuery($query, $variables);

        // Prepare variables
        $sellingPlanGroupId = $this->decode($variables['sellingPlanGroupId']) ?? '';

        try {
            // Get selling plan group
            $sellingPlanGroup = $this->mockShopify->sellingPlanGroups()->get($sellingPlanGroupId);

            return $this->response(
                null,
                $graphqlQuery,
                [
                    'sellingPlanGroup' => $sellingPlanGroup,
                ]
            );
        } catch (Exception $e) {
            return $this->response(
                null,
                $graphqlQuery,
                [
                    'sellingPlanGroup' => [
                        'userErrors' => $this->prepareErrors($e),
                    ],
                ]
            );
        }
    }

    /**
     * @param string|null $query
     * @param array|null $variables
     * @return array|null
     * @throws Exception
     */
    public function sellingPlanGroupsList(?string $query, ?array $variables): ?array
    {
        // Parse graphql query
        $graphqlQuery = new MockGraphqlQuery($query, $variables);

        // Get selling plan groups
        $entities = $this->mockShopify->sellingPlanGroups()->list();

        // @todo Improve nested fields
        $fields = $graphqlQuery->getFields();
        $asEdges = isset($fields['edges']);

        if ($asEdges) {
            $cursor = rand(1111111, 22222222);
            $response = [
                'sellingPlanGroups' => [
                    'edges' => array_map(function ($entity) use ($cursor) {
                        return [
                            'cursor' => $cursor,
                            'node' => $entity,
                        ];
                    }, $entities),
                ],
            ];
        } else {
            $response = [
                'sellingPlanGroups' => $entities,
            ];
        }

        return $this->response(
            null,
            $graphqlQuery,
            $response
        );
    }

    /**
     * Create selling plan group.
     *
     * @param string|null $query
     * @param array|null $variables
     * @return array|null
     * @throws Exception
     */
    public function sellingPlanGroupCreate(?string $query, ?array $variables): ?array
    {
        // Parse graphql query
        $graphqlQuery = new MockGraphqlQuery($query, $variables);

        $input = $graphqlQuery->getVariables()['input'] ?? [];

        try {
            // Create selling group
            $sellingPlanGroup = $this->mockShopify->sellingPlanGroups()->create($input);

            return $this->response(
                null,
                $graphqlQuery,
                [
                    'sellingPlanGroupCreate' => [
                        'sellingPlanGroup' => $sellingPlanGroup,
                    ],
                ]
            );
        } catch (Exception $e) {
            return $this->response(
                null,
                $graphqlQuery,
                [
                    'sellingPlanGroupCreate' => [
                        'userErrors' => $this->prepareErrors($e),
                    ],
                ]
            );
        }
    }

    /**
     * @param string|null $query
     * @param array|null $variables
     * @return array|null
     * @throws Exception
     */
    public function sellingPlanGroupAddProducts(?string $query, ?array $variables): ?array
    {
        // Parse graphql query
        $graphqlQuery = new MockGraphqlQuery($query, $variables);

        // Prepare variables
        $sellingPlanGroupId = $this->decode($variables['id']) ?? '';
        $productIds = $variables['productIds'] ?? [];

        try {
            // Add products
            $sellingPlanGroup = $this->mockShopify->sellingPlanGroups()->addProducts($sellingPlanGroupId, $productIds);

            return $this->response(
                null,
                $graphqlQuery,
                [
                    'SellingPlanGroupAddProductsPayload' => [
                        'sellingPlanGroup' => $sellingPlanGroup,
                    ],
                ]
            );
        } catch (Exception $e) {
            return $this->response(
                null,
                $graphqlQuery,
                [
                    'SellingPlanGroupAddProductsPayload' => [
                        'userErrors' => $this->prepareErrors($e),
                    ],
                ]
            );
        }
    }

    /**
     * @throws Exception
     */
    public function sellingPlanGroupAddProductVariants(?string $query, ?array $variables): ?array
    {
        // Parse graphql query
        $graphqlQuery = new MockGraphqlQuery($query, $variables);

        // Prepare variables
        $sellingPlanGroupId = $this->decode($variables['id']) ?? '';
        $productVariantIds = $variables['productVariantIds'] ?? [];

        try {
            // Add product variants
            $sellingPlanGroup = $this->mockShopify->sellingPlanGroups()->addProductVariants($sellingPlanGroupId, $productVariantIds);

            return $this->response(
                null,
                $graphqlQuery,
                [
                    'SellingPlanGroupAddProductVariantsPayload' => [
                        'sellingPlanGroup' => $sellingPlanGroup,
                    ],
                ]
            );
        } catch (Exception $e) {
            return $this->response(
                null,
                $graphqlQuery,
                [
                    'SellingPlanGroupAddProductVariantsPayload' => [
                        'userErrors' => $this->prepareErrors($e),
                    ],
                ]
            );
        }
    }

    /**
     * @throws Exception
     */
    public function sellingPlanGroupDelete(?string $query, ?array $variables): ?array
    {
        // Parse graphql query
        $graphqlQuery = new MockGraphqlQuery($query, $variables);

        // Prepare variables
        $sellingPlanGroupId = $this->decode($variables['id']) ?? '';

        try {
            if ($this->mockShopify->sellingPlanGroups()->delete($sellingPlanGroupId)) {
                return $this->response(
                    null,
                    $graphqlQuery,
                    [
                        'SellingPlanGroupDeletePayload' => [
                            'deletedSellingPlanGroupId' => $variables['id'],
                        ],
                    ]
                );
            }
            throw new MockGraphqlValidationException(['id']);
        } catch (Exception $e) {
            return $this->response(
                null,
                $graphqlQuery,
                [
                    'SellingPlanGroupDeletePayload' => [
                        'userErrors' => $this->prepareErrors($e),
                    ],
                ]
            );
        }
    }

    public function getEndpoints(): array
    {
        // Add here other Graphql endpoints
        return [
            'query SellingPlanGroup' => [$this, 'sellingPlanGroupGet'],
            'query SellingPlanGroupsList' => [$this, 'sellingPlanGroupsList'],
            'mutation sellingPlanGroupCreate' => [$this, 'sellingPlanGroupCreate'],
            'mutation sellingPlanGroupAddProducts' => [$this, 'sellingPlanGroupAddProducts'],
            'mutation sellingPlanGroupAddProductVariants' => [$this, 'sellingPlanGroupAddProductVariants'],
            'mutation sellingPlanGroupDelete' => [$this, 'sellingPlanGroupDelete'],
        ];
    }
}
