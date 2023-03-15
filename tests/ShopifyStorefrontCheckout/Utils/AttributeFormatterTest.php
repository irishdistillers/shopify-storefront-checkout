<?php

namespace Tests\ShopifyStorefrontCheckout\Utils;

use Irishdistillers\ShopifyStorefrontCheckout\Utils\AttributeFormatter;
use PHPUnit\Framework\TestCase;

class AttributeFormatterTest extends TestCase
{
    public function test_format_attribute_with_valid_parameters()
    {
        $data = [
            'a' => '1',
            'b' => 'hello',
        ];

        $result = AttributeFormatter::format($data);
        $this->assertCount(2, $result);
        $this->assertEquals(['key' => 'a', 'value' => '1'], $result[0]);
        $this->assertEquals(['key' => 'b', 'value' => 'hello'], $result[1]);
    }

    public function test_format_attribute_with_valid_parameters_2()
    {
        $data = [
            ['key' => 'a', 'value' => '1'],
            'b' => 'hello',
        ];

        $result = AttributeFormatter::format($data);
        $this->assertCount(2, $result);
        $this->assertEquals(['key' => 'a', 'value' => '1'], $result[0]);
        $this->assertEquals(['key' => 'b', 'value' => 'hello'], $result[1]);
    }

    public function test_format_attributes_with_empty_parameters()
    {
        $result = AttributeFormatter::format([]);
        $this->assertCount(0, $result);
    }

    public function test_do_not_format_attributes_with_invalid_parameters()
    {
        $data = [
            ['hello' => 'a', 'world' => '1'],
            'b' => 'hello',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid attribute: '.json_encode([$data[0]]));

        AttributeFormatter::format($data);
    }
}
