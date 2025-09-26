<?php declare(strict_types=1);

namespace App\Actions\BC;

use App\Services\BC\BusinessCentralClient;

class GetCustomerAction
{
    protected string $endpoint = 'Customers';

    public function __construct(
        protected BusinessCentralClient $businessCentralClient
    ) {
    }

    public function handle(array $params): array
    {
        return $this->businessCentralClient->get($this->endpoint, false, $params);
    }
}
