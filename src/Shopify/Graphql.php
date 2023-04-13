<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Shopify;

use Exception;
use Irishdistillers\ShopifyStorefrontCheckout\Interfaces\LogLevelConstants;
use Irishdistillers\ShopifyStorefrontCheckout\Shopify\Models\QueryModel;
use Irishdistillers\ShopifyStorefrontCheckout\Traits\LoggerAwareTrait;
use Irishdistillers\ShopifyStorefrontCheckout\Traits\LogLevelAwareTrait;
use Monolog\Logger;
use stdClass;

class Graphql
{
    use LogLevelAwareTrait, LoggerAwareTrait;

    protected Context $context;

    protected bool $useStoreFrontApi;

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
     * @param int $logLevel
     */
    public function __construct(Context $context, bool $useStoreFrontApi, ?Logger $logger = null, $mock = null, int $logLevel = LogLevelConstants::LOG_LEVEL_NORMAL)
    {
        $this->context = $context;
        $this->useStoreFrontApi = $useStoreFrontApi;
        $this->lastError = null;
        $this->lastResponse = null;
        $this->mock = (bool) $mock;
        $this->mockEndpoints = $mock;
        $this->setLogger($logger);
        $this->setLogLevel($logLevel);
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
     * @param QueryModel $queryModel
     * @return false|string
     * @codeCoverageIgnore
     */
    protected function post(QueryModel $queryModel)
    {
        $variables = $queryModel->getVariables();
        $post = [
            'query' => trim($queryModel->getQuery()),
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
     * Validate query response and throws exceptions, if necessary.
     *
     * @param string $logMessage
     * @param $response
     * @param QueryModel $queryModel
     * @param array $extra
     * @return void
     * @throws Exception
     */
    protected function validateQueryResponse(string $logMessage, $response, QueryModel $queryModel, array $extra = [])
    {
        // Log details
        if ($this->logLevel === LogLevelConstants::LOG_LEVEL_DETAILED) {
            $this->logDebug(
                $logMessage,
                $queryModel,
                array_merge(
                    ['response' => $response],
                    $extra
                )
            );
        }

        if (! $response) {
            throw new Exception('Empty response');
        }

        if (isset($response['errors'])) {
            $this->lastError = $response['errors'];
            throw new Exception('Errors: '.json_encode($response['errors']));
        } elseif (isset($response['userErrors'])) {
            $this->lastError = $response['userErrors'];
            throw new Exception('UserErrors: '.json_encode($response['errors']));
        }
    }

    /**
     * Mock query.
     *
     * @param QueryModel $queryModel
     * @return array|mixed|null
     */
    protected function mockQuery(QueryModel $queryModel)
    {
        $this->setLogContext('Graphql.mockQuery');

        $response = null;
        $endpoint = null;

        $query = $queryModel->getQuery();
        $variables = $queryModel->getVariables();

        // Mock
        if (preg_match('/^([a-z ]*)\(/i', trim($query), $matches)) {
            // Query with parameters
            $endpoint = trim($matches[1]);
        } elseif (preg_match('/^([a-z ]*)\s*{/i', trim($query), $matches)) {
            // Query without parameters
            $endpoint = trim($matches[1]);
        }

        $closure = $this->mockEndpoints[$endpoint] ?? null;

        // Log details
        if ($this->logLevel === LogLevelConstants::LOG_LEVEL_DETAILED) {
            $this->logDebug(
                'request',
                $queryModel,
                [
                    'endpoint' => $endpoint,
                    'closure' => $closure,
                    'is_callable(closure)' => is_callable($closure),
                ]
            );
        }

        if (is_callable($closure)) {
            try {
                // Run query using mock closure
                $response = call_user_func($closure, $query, $variables);

                // Validate response and throw exceptions, if necessary
                $this->validateQueryResponse(
                    'result',
                    $response,
                    $queryModel,
                    [
                        'endpoint' => $endpoint,
                        'closure' => $closure,
                    ]
                );

                // Return response
                return $response;
            } catch (Exception $e) {
                if ($this->logger) {
                    $this->logError(
                        'failed',
                        $queryModel,
                        [
                            'error' => $e->getMessage(),
                        ]
                    );
                }
            }
        }

        // Log details
        if ($this->logLevel === LogLevelConstants::LOG_LEVEL_DETAILED) {
            $this->logDebug(
                'result',
                $queryModel,
                [
                    'endpoint' => $endpoint,
                    'response' => $response,
                ]
            );
        }

        return $response;
    }

    /**
     * Real query, using curl.
     *
     * @param QueryModel $queryModel
     * @return array|mixed|null
     * @codeCoverageIgnore
     */
    protected function curlQuery(QueryModel $queryModel)
    {
        $this->setLogContext('Graphql.curlQuery');

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
                CURLOPT_POSTFIELDS => $this->post($queryModel),
                CURLOPT_HTTPHEADER => $this->headers(),
            ]);

            // Log details
            if ($this->logLevel === LogLevelConstants::LOG_LEVEL_DETAILED) {
                $this->logDebug(
                    'request',
                    $queryModel
                );
            }

            // Run query
            $response = json_decode(curl_exec($curl), true);

            // Store last response
            $this->lastResponse = $response;

            // Validate response and throw exceptions, if necessary
            $this->validateQueryResponse(
                'result',
                $response,
                $queryModel
            );

            // Return response
            return $response['data'] ?? null;
        } catch (Exception $e) {
            $this->logError(
                'failed',
                $queryModel,
                [
                    'error' => $e->getMessage(),
                ]
            );
        } finally {
            curl_close($curl);
        }

        // Log details
        if ($this->logLevel === LogLevelConstants::LOG_LEVEL_DETAILED) {
            $this->logDebug(
                'result',
                $queryModel,
                [
                    'response' => null,
                ]
            );
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

        $queryModel = new QueryModel($query, $variables);

        return $this->mock ?
            $this->mockQuery($queryModel) :
            $this->curlQuery($queryModel);
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
