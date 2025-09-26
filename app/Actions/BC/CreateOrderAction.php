<?php declare(strict_types=1);

namespace App\Actions\BC;

use App\Services\BC\BusinessCentralClient;

class CreateOrderAction
{
    protected string $endpoint = 'salesOrders';

    public function __construct(
        protected BusinessCentralClient $businessCentralClient
    ) {
    }

    public function handle(array $payload): array
    {
        return $this->businessCentralClient->post($this->endpoint, $payload);
    }
}
