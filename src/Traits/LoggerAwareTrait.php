<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Traits;

use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Models\QueryModel;
use Monolog\Logger;

trait LoggerAwareTrait
{
    protected ?Logger $logger;

    protected ?string $logContext = null;

    protected function setLogger(?Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Add a log.
     *
     * @param string $logMessage
     * @param string $logLevel
     * @param QueryModel $queryModel
     * @param array $data
     * @return void
     */
    protected function log(string $logMessage, string $logLevel, QueryModel $queryModel, array $data = [])
    {
        if ($this->logger) {
            // Validate method
            $method = method_exists($this->logger, $logLevel) ? $logLevel : 'debug';

            // Log
            $this->logger->$method(
                ($this->logContext ? $this->logContext.' ' : '').$logMessage,
                array_merge(
                    $queryModel->toArray(),
                    $data
                )
            );
        }
    }

    protected function logError(string $logMessage, QueryModel $queryModel, array $data = [])
    {
        $this->log($logMessage, 'error', $queryModel, $data);
    }

    protected function logDebug(string $logMessage, QueryModel $queryModel, array $data = [])
    {
        $this->log($logMessage, 'debug', $queryModel, $data);
    }

    protected function setLogContext(?string $logContext)
    {
        $this->logContext = $logContext;
    }
}
