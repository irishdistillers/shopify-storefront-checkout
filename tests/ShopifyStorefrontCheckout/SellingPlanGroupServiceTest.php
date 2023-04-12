<?php

namespace Tests\ShopifyStorefrontCheckout;

use Irishdistillers\ShopifyStorefrontCheckout\Mock\MockShopify;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify\MockProducts;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify\MockSellingPlanGroups;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify\MockStore;
use Irishdistillers\ShopifyStorefrontCheckout\SellingPlanGroupService;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Tests\ShopifyStorefrontCheckout\Traits\MockCartTrait;

class SellingPlanGroupServiceTest extends TestCase
{
    use MockCartTrait;

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clear store
        MockStore::clear();
    }

    protected function getValidSellingPlanGroupOptions(array $productIds = [], array $productVariantIds = []): array
    {
        return [
            'name' => 'Test'.rand(11111, 55555),
            'merchantCode' => 'idl',
            'deposit' => 0.0,
            'remainingBalanceChargeTime' => date('Y-m-d', mktime(12, 0, 0, date('m') + 2, date('d'), date('Y'))),
            'remainingBalanceChargeTrigger' => '',
            'fulfillmentTrigger' => 'UNKNOWN',
            'inventoryReserve' => 'ON_FULFILLMENT',
            'productIds' => $productIds,
            'productVariantIds' => $productVariantIds,
        ];
    }

    /**
     * @group shopify_cart
     */
    public function test_create_service_plan_group_service()
    {
        // Create service
        $context = $this->getContext();
        $service = new SellingPlanGroupService($context);

        $this->assertNotNull($service);
        $this->assertEmpty($service->errors());
    }

    public function test_create_service_plan_group_service_with_logger()
    {
        // Create logger with test handler
        $handler = new TestHandler();
        $logger = new Logger('logger', [$handler]);
        $this->assertFalse($handler->hasErrorRecords());

        // Create service
        $context = $this->getContext();
        $service = new SellingPlanGroupService($context, $logger);
        $this->assertNotNull($service);
        $this->assertEmpty($service->errors());

        // Trigger error
        $service->remove('dummy');

        $this->assertNotEmpty($service->errors());
        $this->assertEquals(['Empty response'], $service->errors());
        $this->assertTrue($handler->hasErrorRecords());
    }

    public function test_create_service_plan_group_with_valid_data()
    {
        // Create service
        $service = $this->getSellingPlanGroupService();
        $shopify = new MockShopify($service->getContext());

        $this->assertNotNull($service);
        $this->assertEmpty($service->errors());

        $productId = $shopify->ids()->createRandomId(MockProducts::PRODUCT_PREFIX);
        $options = $this->getValidSellingPlanGroupOptions([$productId]);

        $sellingPlanGroupId = $service->create($options);
        $this->assertNotFalse($sellingPlanGroupId);

        // Retrieve created group
        $sellingPlanGroup = $service->get($sellingPlanGroupId);
        $this->assertNotEmpty($sellingPlanGroup);
        $this->assertEquals($sellingPlanGroupId, $sellingPlanGroup['id']);
        $this->assertEquals($options['name'], $sellingPlanGroup['name']);
        $this->assertEquals($options['merchantCode'], $sellingPlanGroup['merchantCode']);
        $this->assertEquals(1, $sellingPlanGroup['productCount']);
        $this->assertCount(1, $sellingPlanGroup['products']);
        $this->assertCount(1, $sellingPlanGroup['sellingPlans']);

        $this->assertEmpty($service->errors());
    }

    public function test_create_service_plan_group_with_valid_data_and_optional_data()
    {
        // Create service
        $service = $this->getSellingPlanGroupService();
        $shopify = new MockShopify($service->getContext());

        $this->assertNotNull($service);
        $this->assertEmpty($service->errors());

        $productId = $shopify->ids()->createRandomId(MockProducts::PRODUCT_PREFIX);
        $options = $this->getValidSellingPlanGroupOptions([$productId]);
        $options['description'] = 'This is a test';
        $options['position'] = 10;

        $sellingPlanGroupId = $service->create($options);
        $this->assertNotFalse($sellingPlanGroupId);

        // Retrieve created group
        $sellingPlanGroup = $service->get($sellingPlanGroupId);
        $this->assertNotEmpty($sellingPlanGroup);
        $this->assertEquals($sellingPlanGroupId, $sellingPlanGroup['id']);
        $this->assertEquals($options['name'], $sellingPlanGroup['name']);
        $this->assertEquals($options['description'], $sellingPlanGroup['description']);
        $this->assertEquals($options['merchantCode'], $sellingPlanGroup['merchantCode']);
        $this->assertEquals($options['position'], $sellingPlanGroup['position']);
        $this->assertEquals(1, $sellingPlanGroup['productCount']);
        $this->assertCount(1, $sellingPlanGroup['products']);
        $this->assertCount(1, $sellingPlanGroup['sellingPlans']);

        $this->assertEmpty($service->errors());
    }

    public function test_do_not_create_service_plan_group_with_missing_name()
    {
        // Create service
        $service = $this->getSellingPlanGroupService();
        $shopify = new MockShopify($service->getContext());

        $this->assertNotNull($service);
        $this->assertEmpty($service->errors());

        $productId = $shopify->ids()->createRandomId(MockProducts::PRODUCT_PREFIX);
        $options = $this->getValidSellingPlanGroupOptions([$productId]);
        unset($options['name']);

        $sellingPlanGroupId = $service->create($options);
        $this->assertFalse($sellingPlanGroupId);

        $errors = $service->errors();
        $this->assertNotEmpty($errors);
        $this->assertCount(1, $errors);
        $this->assertEquals('name', $errors[0]['field']);
    }

    public function test_do_not_create_service_plan_group_with_missing_merchant_code()
    {
        // Create service
        $service = $this->getSellingPlanGroupService();
        $shopify = new MockShopify($service->getContext());

        $this->assertNotNull($service);
        $this->assertEmpty($service->errors());

        $productId = $shopify->ids()->createRandomId(MockProducts::PRODUCT_PREFIX);
        $options = $this->getValidSellingPlanGroupOptions([$productId]);
        unset($options['merchantCode']);

        $sellingPlanGroupId = $service->create($options);
        $this->assertFalse($sellingPlanGroupId);

        $errors = $service->errors();
        $this->assertNotEmpty($errors);
        $this->assertCount(1, $errors);
        $this->assertEquals('merchantCode', $errors[0]['field']);
    }

    public function test_get_existing_selling_plan_group()
    {
        // Create service
        $service = $this->getSellingPlanGroupService();

        // Create selling plan group
        $sellingPlanGroupId = $service->create($this->getValidSellingPlanGroupOptions());
        $this->assertNotFalse($sellingPlanGroupId);

        // Retrieve created group
        $sellingPlanGroup = $service->get($sellingPlanGroupId);
        $this->assertNotEmpty($sellingPlanGroup);
        $this->assertEquals($sellingPlanGroupId, $sellingPlanGroup['id']);
    }

    public function test_do_not_get_non_existing_selling_plan_group()
    {
        // Create service
        $service = $this->getSellingPlanGroupService();
        $shopify = new MockShopify($service->getContext());

        $sellingPlanGroupId = $shopify->ids()->createRandomId(MockSellingPlanGroups::SELLING_PLAN_GROUP_PREFIX);

        // Do not retrieve non-existing group
        $this->assertNull($service->get($sellingPlanGroupId));
        $this->assertNotEmpty($service->errors());
    }

    public function test_add_product_ids_to_existing_service_plan_group()
    {
        // Create service
        $service = $this->getSellingPlanGroupService();
        $shopify = new MockShopify($service->getContext());

        $this->assertNotNull($service);
        $this->assertEmpty($service->errors());

        $options = $this->getValidSellingPlanGroupOptions();

        $sellingPlanGroupId = $service->create($options);
        $this->assertNotFalse($sellingPlanGroupId);

        // Retrieve created group
        $sellingPlanGroup = $service->get($sellingPlanGroupId);
        $this->assertNotEmpty($sellingPlanGroup);
        $this->assertEquals($sellingPlanGroupId, $sellingPlanGroup['id']);
        $this->assertEquals(0, $sellingPlanGroup['productCount']);
        $this->assertCount(0, $sellingPlanGroup['products']);

        // Add products to selling plan group
        $productId1 = $shopify->ids()->createRandomId(MockProducts::PRODUCT_PREFIX);
        $productId2 = $shopify->ids()->createRandomId(MockProducts::PRODUCT_PREFIX);

        $this->assertTrue($service->addProducts($sellingPlanGroupId, [$productId1, $productId2]));
        $this->assertEmpty($service->errors());

        // Verify that the products were added to selling plan group
        $sellingPlanGroup = $service->get($sellingPlanGroupId);
        $this->assertNotEmpty($sellingPlanGroup);
        $this->assertEquals($sellingPlanGroupId, $sellingPlanGroup['id']);
        $this->assertEquals(2, $sellingPlanGroup['productCount']);
        $this->assertCount(2, $sellingPlanGroup['products']);
    }

    public function test_do_not_add_product_ids_to_invalid_service_plan_group()
    {
        // Create service
        $service = $this->getSellingPlanGroupService();
        $shopify = new MockShopify($service->getContext());

        // Add products to selling plan group
        $productId1 = $shopify->ids()->createRandomId(MockProducts::PRODUCT_PREFIX);
        $productId2 = $shopify->ids()->createRandomId(MockProducts::PRODUCT_PREFIX);

        $sellingPlanGroupId = $shopify->ids()->createRandomId(MockSellingPlanGroups::SELLING_PLAN_GROUP_PREFIX);
        $this->assertFalse($service->addProducts($sellingPlanGroupId, [$productId1, $productId2]));

        $errors = $service->errors();
        $this->assertNotEmpty($errors);
        $this->assertCount(1, $errors);
        $this->assertEquals('id', $errors[0]['field']);
    }

    public function test_add_product_variant_ids_to_existing_service_plan_group()
    {
        // Create service
        $service = $this->getSellingPlanGroupService();
        $shopify = new MockShopify($service->getContext());

        $this->assertNotNull($service);
        $this->assertEmpty($service->errors());

        $options = $this->getValidSellingPlanGroupOptions();

        $sellingPlanGroupId = $service->create($options);
        $this->assertNotFalse($sellingPlanGroupId);

        // Retrieve created group
        $sellingPlanGroup = $service->get($sellingPlanGroupId);
        $this->assertNotEmpty($sellingPlanGroup);
        $this->assertEquals($sellingPlanGroupId, $sellingPlanGroup['id']);
        $this->assertEquals(0, $sellingPlanGroup['productVariantCount']);
        $this->assertCount(0, $sellingPlanGroup['productVariants']);

        // Add product variants to selling plan group
        $variantId1 = $shopify->ids()->createRandomId(MockProducts::VARIANT_PREFIX);
        $variantId2 = $shopify->ids()->createRandomId(MockProducts::VARIANT_PREFIX);

        $this->assertTrue($service->addProductVariants($sellingPlanGroupId, [$variantId1, $variantId2]));
        $this->assertEmpty($service->errors());

        // Verify that the product variants were added to selling plan group
        $sellingPlanGroup = $service->get($sellingPlanGroupId);
        $this->assertNotEmpty($sellingPlanGroup);
        $this->assertEquals($sellingPlanGroupId, $sellingPlanGroup['id']);
        $this->assertEquals(2, $sellingPlanGroup['productVariantCount']);
        $this->assertCount(2, $sellingPlanGroup['productVariants']);
    }

    public function test_do_not_add_product_variant_ids_to_invalid_service_plan_group()
    {
        // Create service
        $service = $this->getSellingPlanGroupService();
        $shopify = new MockShopify($service->getContext());

        // Add product variants to selling plan group
        $variantId1 = $shopify->ids()->createRandomId(MockProducts::VARIANT_PREFIX);
        $variantId2 = $shopify->ids()->createRandomId(MockProducts::VARIANT_PREFIX);

        $sellingPlanGroupId = $shopify->ids()->createRandomId(MockSellingPlanGroups::SELLING_PLAN_GROUP_PREFIX);
        $this->assertFalse($service->addProductVariants($sellingPlanGroupId, [$variantId1, $variantId2]));

        $errors = $service->errors();
        $this->assertNotEmpty($errors);
        $this->assertCount(1, $errors);
        $this->assertEquals('id', $errors[0]['field']);
    }

    public function test_delete_existing_service_plan_group()
    {
        // Create service
        $service = $this->getSellingPlanGroupService();

        // Create selling plan group
        $sellingPlanGroupId = $service->create($this->getValidSellingPlanGroupOptions());
        $this->assertNotFalse($sellingPlanGroupId);

        // Retrieve created group
        $sellingPlanGroup = $service->get($sellingPlanGroupId);
        $this->assertNotEmpty($sellingPlanGroup);
        $this->assertEquals($sellingPlanGroupId, $sellingPlanGroup['id']);

        // Delete group
        $this->assertEquals($sellingPlanGroupId, $service->remove($sellingPlanGroupId));
        $this->assertEmpty($service->errors());

        // Do not retrieve deleted group
        $sellingPlanGroup = $service->get($sellingPlanGroupId);
        $this->assertEmpty($sellingPlanGroup);
    }

    public function test_do_not_delete_non_existing_service_plan_group()
    {
        // Create service
        $service = $this->getSellingPlanGroupService();
        $shopify = new MockShopify($service->getContext());

        $sellingPlanGroupId = $shopify->ids()->createRandomId(MockSellingPlanGroups::SELLING_PLAN_GROUP_PREFIX);

        // Do not delete non-existing group
        $this->assertFalse($service->remove($sellingPlanGroupId));
    }

    public function test_list_existing_service_plan_groups()
    {
        // Create service
        $service = $this->getSellingPlanGroupService();
        $shopify = new MockShopify($service->getContext());

        $optionsGroup1 = $this->getValidSellingPlanGroupOptions();
        $optionsGroup2 = $this->getValidSellingPlanGroupOptions();

        $sellingPlanGroupId1 = $service->create($optionsGroup1);
        $this->assertNotFalse($sellingPlanGroupId1);

        $sellingPlanGroupId2 = $service->create($optionsGroup2);
        $this->assertNotFalse($sellingPlanGroupId2);

        $list = $service->list();
        $this->assertNotEmpty($list);
        $this->assertCount(2, $list);
        $this->assertEquals($sellingPlanGroupId1, $list[0]['id']);
        $this->assertEquals($sellingPlanGroupId2, $list[1]['id']);
    }

    public function test_do_not_list_non_existing_service_plan_groups()
    {
        // Create service
        $service = $this->getSellingPlanGroupService();

        $list = $service->list();
        $this->assertEmpty($list);
    }
}
