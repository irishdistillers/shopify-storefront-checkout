<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Interfaces;

use Exception;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Context;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Graphql;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Models\QueryModel;
use Irishdistillers\ShopifyStorefrontCheckout\Traits\LoggerAwareTrait;
use Monolog\Logger;

abstract class BaseService
{
    use LoggerAwareTrait;

    protected Context $context;

    protected Graphql $graphql;

    protected array $errorMessages;

    public function __construct(Context $context, ?Logger $logger = null, ?array $mock = null, int $logLevel = LogLevelConstants::LOG_LEVEL_NORMAL)
    {
        $this->context = $context;
        $this->setLogger($logger);
        $this->graphql = new Graphql(
            $context,
            $this->useStoreFrontApi(),
            $logger,
            $mock,
            $logLevel
        );
        $this->errorMessages = [];
    }

    abstract protected function useStoreFrontApi(): bool;

    /**
     * @param QueryModel $queryModel
     * @param string $field
     * @return array|bool
     */
    protected function query(QueryModel $queryModel, string $field)
    {
        // Set log context
        $this->setLogContext(get_class());

        // Reset error messages
        $this->errorMessages = [];

        try {
            // Run query
            $data = $this->graphql->query($queryModel->getQuery(), $queryModel->getVariables());

            // Check errors
            $errors = $data[$field]['userErrors'] ?? [];
            if (count($errors)) {
                $this->errorMessages = $errors;
            } else {
                if ($data) {
                    return $data[$field] ?? false;
                }
                $this->errorMessages[] = 'Empty response';
            }
        } catch (Exception $e) {
            $this->errorMessages[] = $e->getMessage();
        }

        $this->logError(
            'failed',
            $queryModel,
            [
                'field' => $field,
                'errors' => $this->errorMessages,
            ]
        );

        return false;
    }

    public function errors(): array
    {
        return $this->errorMessages;
    }
}
