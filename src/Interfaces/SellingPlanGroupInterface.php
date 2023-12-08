<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Interfaces;

interface SellingPlanGroupInterface
{
    public const DEPOSIT = 'deposit';

    public const DEPOSIT_AMOUNT = 'depositAmount';

    public const DESCRIPTION = 'description';

    public const FULFILLMENT_TRIGGER = 'fulfillmentTrigger';

    public const INVENTORY_RESERVE = 'inventoryReserve';

    public const MERCHANT_CODE = 'merchantCode';

    public const NAME = 'name';

    public const POSITION = 'position';

    public const PRICING_POLICIES = 'pricingPolicies';

    public const PRODUCT_IDS = 'productIds';

    public const PRODUCT_VARIANT_IDS = 'productVariantIds';

    public const REMAINING_BALANCE_CHARGE_TIME = 'remainingBalanceChargeTime';

    public const REMAINING_BALANCE_CHARGE_TRIGGER = 'remainingBalanceChargeTrigger';
}
