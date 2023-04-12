<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Exceptions;

use Exception;
use Throwable;

class MockGraphqlValidationException extends Exception
{
    protected array $failures = [];

    public function __construct(array $failures, $message = '', $code = 0, Throwable $previous = null)
    {
        $this->failures = $failures;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getFailures(): array
    {
        return $this->failures;
    }
}
