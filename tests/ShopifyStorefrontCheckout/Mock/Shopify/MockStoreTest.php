<?php

namespace Tests\ShopifyStorefrontCheckout\Mock\Shopify;

use Irishdistillers\ShopifyStorefrontCheckout\Mock\Shopify\MockStore;
use PHPUnit\Framework\TestCase;

class MockStoreTest extends TestCase
{
    protected function getRandomId(): string
    {
        return md5(uniqid());
    }

    public function test_set_item_in_mock_store_and_retrieve_it()
    {
        $obj = new MockStore();

        $id1 = $this->getRandomId();
        $id2 = $this->getRandomId();
        $id3 = $this->getRandomId();

        $value1 = ['hello'];
        $value2 = ['a' => 'b'];
        $value3 = null;

        $obj->set('test', $id1, $value1);
        $obj->set('test', $id2, $value2);
        $obj->set('test', $id3, $value3);

        $this->assertEquals($value1, $obj->get('test', $id1));
        $this->assertEquals($value2, $obj->get('test', $id2));
        $this->assertEquals($value3, $obj->get('test', $id3));

        $this->assertNull($obj->get('invalid', $id1));
        $this->assertNull($obj->get('test', $this->getRandomId()));
    }
}
