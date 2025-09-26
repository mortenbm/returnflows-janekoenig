<?php

namespace App\Jobs\Shopify;

use App\Actions\Shopify\GetBulkOperationStatusAction;
use App\Enums\Shopify\BulkOperationStatusEnum;
use App\Models\Store;
use App\Services\Shopify\ShopifyClientFactory;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckBulkOperationStatusJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $storeId,
        protected string $bulkOperationId
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(
        ShopifyClientFactory $shopifyClientFactory,
    ): void {
        $store = Store::findOrFail($this->storeId);
        $shopifyClient = $shopifyClientFactory->makeByStore($store);
        $tries = 0;
        do {
            $response = app(
                GetBulkOperationStatusAction::class,
                ['shopifyClient' => $shopifyClient]
            )->handle($this->bulkOperationId);
            $status = $response['data']['node']['status'] ?? null;
            $status = BulkOperationStatusEnum::tryfrom($status);

            if ($status === BulkOperationStatusEnum::COMPLETED) {
                $url = $response['data']['node']['url'] ?? null;
                if ($url === null) {
                    Log::channel('shopify_graph')->info('Missing orders in this period: ' . $this->bulkOperationId);
                    break;
                }

                FetchBulkOperationResultJob::dispatch(
                    $this->storeId,
                    $this->bulkOperationId,
                    $response['data']['node']['url']
                );
                break;
            }

            if ($status !== BulkOperationStatusEnum::RUNNING) {
                Log::channel('shopify_graph')->error('Unexpected bulk operation status: ' . json_encode($response));
                break;
            }

            // 30 minutes
            $tries++;
            if ($tries >= 60) {
                Log::channel('shopify_graph')->error(
                    'Bulk operation check exceeded max attempts for ID: ' . $this->bulkOperationId
                );
                break;
            }

            sleep(30);
        } while (true);
    }

    public function uniqueId(): int
    {
        return $this->storeId;
    }
}
