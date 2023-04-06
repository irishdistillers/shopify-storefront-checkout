<?php

namespace Tests\ShopifyStorefrontCheckout;

use Irishdistillers\ShopifyStorefrontCheckout\SellingPlanGroupService;
use PHPUnit\Framework\TestCase;
use Tests\ShopifyStorefrontCheckout\Traits\MockCartTrait;

class SellingPlanGroupServiceTest extends TestCase
{
    use MockCartTrait;

    /**
     * @group shopify_cart
     */
    public function test_create_new_service_plan_group_service()
    {
        $context = $this->getContext();
        $service = new SellingPlanGroupService($context);
        $this->assertNotNull($service);
        $this->assertEmpty($service->errors());
    }
}
