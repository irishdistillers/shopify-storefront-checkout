<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Traits;

use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\LogLevelConstants;

trait LogLevelAwareTrait
{
    protected int $logLevel = LogLevelConstants::LOG_LEVEL_NORMAL;

    public function setLogLevel(int $logLevel): self
    {
        $this->logLevel = $logLevel;

        return $this;
    }

    /**
     * @return int
     */
    public function getLogLevel(): int
    {
        return $this->logLevel;
    }
}
