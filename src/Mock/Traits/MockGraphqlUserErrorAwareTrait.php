<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Traits;

use Exception;
use Irishdistillers\ShopifyStorefrontCheckout\Mock\Exceptions\MockGraphqlValidationException;

trait MockGraphqlUserErrorAwareTrait
{
    /**
     * Get single userError for graphql query.
     *
     * @param string $field
     * @param string $message
     * @param string|null $code
     * @return array
     */
    protected function getUserError(string $field, string $message, ?string $code = null): array
    {
        return [
            'code' => $code ?? rand(10000, 20000),
            'field' => $field,
            'message' => $message,
        ];
    }

    /**
     * Prepare userError responses for graphql query.
     *
     * @param Exception|MockGraphqlValidationException $e
     * @return array
     */
    protected function prepareErrors($e): array
    {
        if (! $e instanceof MockGraphqlValidationException) {
            return [
                $e->getMessage(),
            ];
        }

        return array_map(function ($field) {
            if (is_array($field)) {
                return $this->getUserError($field['field'] ?? 'unknown', $field['message'] ?? 'Unknown');
            }

            return $this->getUserError($field, 'Field '.$field.' is mandatory');
        }, $e->getFailures());
    }
}
