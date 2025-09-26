<?php declare(strict_types=1);

namespace App\Jobs\Shopify;

use App\Actions\Shopify\SyncShopifyOrdersAction;
use App\Enums\Shopify\BulkOperationStatusEnum;
use App\Models\Store;
use App\Services\Shopify\ShopifyClientFactory;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchShopifyOrdersJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    protected $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $storeId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ShopifyClientFactory $shopifyClientFactory): void
    {
        try {
            $store = Store::findOrFail($this->storeId);
            $shopifyClient = $shopifyClientFactory->makeByStore($store);
            $bulkOperation = app(
                SyncShopifyOrdersAction::class,
                [
                    'shopifyClient' => $shopifyClient,
                    'storeId' => $this->storeId
                ]
            )->handle();
            if (!empty($bulkOperation['data']['bulkOperationRunQuery']['userErrors'])) {
                throw new \Exception(json_encode($bulkOperation['data']['bulkOperationRunQuery']['userErrors']));
            }

            $status = $bulkOperation['data']['bulkOperationRunQuery']['bulkOperation']['status'] ?? null;
            $status = BulkOperationStatusEnum::tryfrom($status);
            if ($status !== BulkOperationStatusEnum::CREATED) {
                throw new \Exception('Wrong bulk operation status: ' . json_encode($bulkOperation));
            }

            Log::channel('shopify_graph')->info(
                'Requested bulk operation: ' . $bulkOperation['data']['bulkOperationRunQuery']['bulkOperation']['id']
            );
            CheckBulkOperationStatusJob::dispatch(
                $store->id,
                $bulkOperation['data']['bulkOperationRunQuery']['bulkOperation']['id']
            );
        } catch (\Throwable $e) {
            Log::channel('shopify_graph')->error($e);
        }
    }

    public function uniqueId(): int
    {
        return $this->storeId;
    }
}
