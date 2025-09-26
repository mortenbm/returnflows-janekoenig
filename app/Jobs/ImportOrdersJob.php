<?php declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Shopify\FetchShopifyOrdersJob;
use App\Models\Store;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportOrdersJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $stores = Store::query()->where('is_active', 1)->get();
        foreach ($stores as $store) {
            FetchShopifyOrdersJob::dispatch($store->id);
        }
    }
}
