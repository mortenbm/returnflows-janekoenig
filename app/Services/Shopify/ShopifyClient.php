<?php declare(strict_types=1);

namespace App\Services\Shopify;

use Illuminate\Support\Facades\Log;
use Shopify\Clients\Graphql;
use Shopify\Exception\HttpRequestException;
use Shopify\Exception\MissingArgumentException;
use Symfony\Component\HttpFoundation\Response;

class ShopifyClient
{
    protected Graphql $graphqlClient;
    public function __construct(
        protected array $config
    ) {
        $this->_init();
    }

    /**
     * @throws \Throwable
     * @throws HttpRequestException
     * @throws MissingArgumentException
     * @throws \JsonException
     */
    public function query(array $payload): array
    {
        try {
            $response = $this->graphqlClient->query($payload);
            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw new \Exception('Invalid HTTP status code: ' . $response->getStatusCode());
            }

            $response = $response->getDecodedBody();
            if (!empty($response['errors'])) {
                $this->processErrors($response['errors']);
                throw new \Exception('Shopify graphql api errors.');
            }

            $operation = data_get($response, 'data');
            if (is_array($operation) && !empty($operation)) {
                $operationType = array_key_first($operation);
                $userErrors = data_get($response, "data.{$operationType}.userErrors", []);
                if (!empty($userErrors)) {
                    $this->processErrors($userErrors);
                    throw new \Exception('Shopify graphql api errors.');
                }
            }

            return $response;
        } catch (\Throwable $e) {
            Log::channel('shopify_graph')->error('GraphQL query failed', [
                'message' => $e->getMessage(),
                'payload' => $payload,
            ]);

            throw $e;
        }
    }

    protected function processErrors(array $errors): void
    {
        foreach ($errors as $error) {
            Log::channel('shopify_graph')->error('GraphQL error', [
                'error' => !empty($error['message']) ? $error['message'] : $error,
            ]);
        }
    }

    protected function _init(): void
    {
        $this->graphqlClient = new Graphql(
            $this->config['domain'],
            $this->config['token'],
        );
    }
}
