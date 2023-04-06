<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Shopify;

use Exception;
use Monolog\Logger;
use stdClass;

class Graphql
{
    protected Context $context;

    protected bool $useStoreFrontApi;

    protected ?Logger $logger;

    /** @var string|array|null */
    protected $lastError;

    protected ?array $lastResponse;

    protected bool $mock;

    protected ?array $mockEndpoints;

    /**
     * Graphql constructor.
     *
     * @param Context $context
     * @param bool $useStoreFrontApi If true, it uses StoreFront API
     * @param Logger|null $logger Optional logger
     * @param null $mock If it's an array, pass mock endpoints. Graphql interaction will be fully mocked. To be used by unit tests.
     */
    public function __construct(Context $context, bool $useStoreFrontApi, ?Logger $logger = null, $mock = null)
    {
        $this->context = $context;
        $this->logger = $logger;
        $this->useStoreFrontApi = $useStoreFrontApi;
        $this->lastError = null;
        $this->lastResponse = null;
        $this->mock = (bool) $mock;
        $this->mockEndpoints = $mock;
    }

    /**
     * Get the URL path to be used for API requests.
     *
     * @return string
     */
    public function getApiPath(): string
    {
        return 'https://'.$this->context->getShopBaseUrl().
            ($this->useStoreFrontApi ? '' : '/admin').
            '/api/'.$this->context->getApiVersion().'/graphql.json';
    }

    /**
     * Prepare post used by curl.
     *
     * @param string $query
     * @param array $variables
     * @return false|string
     * @codeCoverageIgnore
     */
    protected function post(string $query, array $variables = [])
    {
        $post = [
            'query' => trim($query),
            'variables' => empty($variables) ? new stdClass() : $variables,
        ];

        return json_encode($post);
    }

    /**
     * Get headers used by curl.
     *
     * @return array
     */
    public function headers(): array
    {
        // Prepare headers
        $headers = [
        ];

        $headers[] = 'Content-Type: application/json';
        if ($this->useStoreFrontApi) {
            $headers[] = 'X-Shopify-Storefront-Access-Token: '.$this->context->getShopifyStoreFrontAccessToken();
        } else {
            $headers[] = 'X-Shopify-Access-Token: '.$this->context->getShopifyAccessToken();
        }

        return $headers;
    }

    /**
     * Mock query.
     *
     * @param string $query
     * @param array $variables
     * @return array|mixed|null
     */
    protected function mockQuery(string $query, array $variables = [])
    {
        // Mock
        if (preg_match('/^([a-z ]*)\(/i', trim($query), $matches)) {
            $endpoint = $matches[1];
            $closure = $this->mockEndpoints[$endpoint] ?? null;
            if (is_callable($closure)) {
                return call_user_func($closure, $query, $variables);
            }
        }

        return null;
    }

    /**
     * Real query, using curl.
     *
     * @param string $query
     * @param array $variables
     * @return array|mixed|null
     * @codeCoverageIgnore
     */
    protected function curlQuery(string $query, array $variables = [])
    {
        // Prepare CURL
        $curl = curl_init();

        try {
            curl_setopt_array($curl, [
                CURLOPT_URL => $this->getApiPath(),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $this->post($query, $variables),
                CURLOPT_HTTPHEADER => $this->headers(),
            ]);

            $response = json_decode(curl_exec($curl), true);

            // Store last response
            $this->lastResponse = $response;

            if (! $response) {
                throw new Exception('Empty response');
            }

            if (isset($response['errors'])) {
                $this->lastError = $response['errors'];
                throw new Exception('Errors: '.json_encode($response['errors']));
            }

            return $response['data'] ?? null;
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Graphql.query failed', ['query' => $query, 'e' => $e->getMessage()]);
            }
        } finally {
            curl_close($curl);
        }

        return null;
    }

    /**
     * Query.
     *
     * @param string $query
     * @param array $variables
     * @return array|mixed|null
     */
    public function query(string $query, array $variables = [])
    {
        $this->lastError = null;
        $this->lastResponse = null;

        if ($this->mock) {
            return $this->mockQuery($query, $variables);
        }

        return $this->curlQuery($query, $variables);
    }

    /**
     * Get last error.
     *
     * @return string|array|null
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Get last response.
     *
     * @return array|null
     */
    public function getLastResponse(): ?array
    {
        return $this->lastResponse;
    }
}
