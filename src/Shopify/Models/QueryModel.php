<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Shopify\Models;

class QueryModel
{
    protected string $query;

    protected array $variables;

    public function __construct(string $query, array $variables = [])
    {
        $this->query = $query;
        $this->variables = $variables;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return array
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'variables' => $this->variables,
        ];
    }
}
