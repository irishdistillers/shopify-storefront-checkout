<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Utils;

use Exception;

class AttributeFormatter
{
    /**
     * @throws Exception
     */
    public static function format(array $attributes): array
    {
        $ret = [];

        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                // We assume that it's already in the correct format
                if (! count(array_diff(['key', 'value'], array_keys($value)))) {
                    $ret[] = $value;
                } else {
                    throw new Exception('Invalid attribute: '.json_encode([$key => $value]));
                }
            } else {
                $ret[] = [
                    'key' => $key,
                    'value' => $value,
                ];
            }
        }

        return $ret;
    }
}
