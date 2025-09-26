<?php declare(strict_types=1);

namespace App\Actions\BC;

use App\Services\BC\BusinessCentralClient;
use Outerweb\Settings\Models\Setting;

class CreateCreditMemoAction
{
    protected string $endpoint = 'salesCreditMemos';

    public function __construct(
        protected BusinessCentralClient $businessCentralClient
    ) {
    }

    public function handle(array $payload): array
    {
        return $this->businessCentralClient->post($this->getEndpoint(), $payload, Setting::get('live_mode', false));
    }

    private function getEndpoint(): string
    {
        $endpoint = $this->endpoint;
        if (Setting::get('live_mode', false)) {
            $endpoint = sprintf(
                '%s%s/%s/api/dynamicsInspireApS/connectify/v2.0/companies(%s)/%s',
                rtrim(config('services.bc.domain'), '/') . '/',
                config('services.bc.live.tenant_id'),
                config('services.bc.env'),
                config('services.bc.live.company_id'),
                $this->endpoint . '?$expand=salesCreditMemoLines'
            );
        }
        return $endpoint;
    }
}
