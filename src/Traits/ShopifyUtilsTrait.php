<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Traits;

trait ShopifyUtilsTrait
{
    public function decode(?string $id): ?string
    {
        if ($id && substr($id, 0, 4) !== 'gid:') {
            $id = base64_decode($id);
        }

        return $id;
    }

    public function encode(?string $id): ?string
    {
        if ($id && substr($id, 0, 4) === 'gid:') {
            $id = base64_encode($id);
        }

        return $id;
    }
}
