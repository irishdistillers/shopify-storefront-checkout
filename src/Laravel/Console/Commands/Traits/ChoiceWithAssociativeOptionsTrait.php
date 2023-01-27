<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Laravel\Console\Commands\Traits;

trait ChoiceWithAssociativeOptionsTrait
{
    protected function askChoiceWithAssociatedOptions($label, $options, $default = null)
    {
        $ret = $this->choice($label, array_values($options), $default);

        return array_flip($options)[$ret];
    }
}
