<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Mock\Graphql\Query;

use Exception;

class MockGraphqlQuery
{
    protected array $variables;

    protected ?string $type;

    protected ?string $endpoint;

    protected array $context;

    protected array $fields;

    /**
     * @throws Exception
     */
    public function __construct(?string $query, ?array $variables)
    {
        $this->variables = $variables ?? [];
        $this->parseQuery($query);
    }

    /**
     * Parse Graphql query.
     *
     * @param string|null $query
     * @throws Exception
     */
    protected function parseQuery(?string $query)
    {
        $this->type = null;
        $this->endpoint = null;
        $this->context = [];
        $this->fields = [];

        if ($query) {
            $query = array_filter(array_map('trim', explode("\n", $query)));

            if (! count($query)) {
                // Empty or invalid query
                return;
            }

            // Get type
            if (preg_match('/^[a-z]*/', $query[0], $matches)) {
                $this->type = $matches[0];
            }

            // Remove first and last lines
            array_shift($query);
            array_pop($query);

            if (! count($query)) {
                // Empty or invalid query
                throw new Exception('Empty query');
            }

            // Get context
            if (substr($query[0], 0, strlen('@inContext')) === '@inContext') {
                $context = trim(str_replace('{', '', $query[0]));
                $context = str_replace(['@inContext(', ')'], ['', ''], $context);
                $this->context = [];
                foreach (explode(',', $context) as $row) {
                    list($key, $value) = array_map('trim', explode(':', $row));
                    // Replace value using variables
                    $variable = $this->variables[preg_replace('/^\$/', '', $value)] ?? null;
                    $this->context[$key] = $variable;
                }
                array_shift($query);
                array_pop($query);
            }

            // Get endpoint
            $this->endpoint = trim(str_replace('{', '', $query[0]));
            array_shift($query);
            if (trim($query[count($query) - 1]) === '}') {
                array_pop($query);
            }

            // Get fields
            $current = null;
            foreach ($query as $item) {
                $item = trim($item);
                if (substr($item, -1, 1) === '{') {
                    $current = trim(substr($item, 0, strlen($item) - 1));
                    $this->fields[$current] = [];
                } elseif ($item === '}') {
                    $current = null;
                } elseif ($current) {
                    $this->fields[$current][] = $item;
                } else {
                    $this->fields[] = $item;
                }
            }
        }
    }

    /**
     * Get type, e.g. query, mutation.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Get variable in context.
     *
     * @return mixed|null
     */
    public function getContext(string $variableId)
    {
        return $this->context[$variableId] ?? null;
    }

    /**
     * Get endpoint.
     *
     * @return string|null
     */
    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    /**
     * Get fields.
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return array
     */
    public function getVariables(): array
    {
        return $this->variables;
    }
}
