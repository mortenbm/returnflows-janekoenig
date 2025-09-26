<?php declare(strict_types=1);

namespace App\Jobs\Shopify;

use App\Support\Shopify\CommonFlags;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Outerweb\Settings\Models\Setting;

class FetchBulkOperationResultJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable, Batchable;

    protected $tries = 3;

    public function __construct(
        protected int $storeId,
        protected string $bulkOperationId,
        protected string $bulkOperationResultUrl,
    ) {
    }

    public function uniqueId(): int
    {
        return $this->storeId;
    }

    public function handle(): void
    {
        try {
            $operationId = Str::afterLast($this->bulkOperationId, '/');
            $path = Storage::disk('local')->path(sprintf('%s.json', $operationId));
            $response = Http::sink($path)->get($this->bulkOperationResultUrl);

            if ($response->successful()) {
                $jobs = $this->getJobs($path);
                if (!$jobs) {
                    throw new \Exception('Unexpected behavior. Can not fetch data from file');
                }

                Storage::disk('local')->delete(sprintf('%s.json', $operationId));
                Setting::set(
                    sprintf('%s.%s.store', CommonFlags::ORDER_SYNC, $this->storeId),
                    Carbon::now()->format('Y-m-d\TH:i:s\Z')
                );
                Bus::batch($jobs)->dispatch();
            }
        } catch (\Throwable $e) {
            Log::channel('shopify_graph')->error(
                'Failed to fetch bulk operation result. Bulk operation id: ' . $this->bulkOperationId
            );
            throw $e;
        }
    }

    private function getJobs(string $path): array
    {
        $jobs = [];
        $stream = fopen($path, 'r');
        if (!$stream) {
            throw new \Exception('Unable to open file: ' . $path);
        }

        $batch = [];
        $batchSize = 100;
        while (($row = fgets($stream)) !== false) {
            $row = trim($row);
            if ($row === '') {
                continue;
            }

            $batch[] = json_decode($row, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            if (count($batch) === $batchSize) {
                $jobs[] = new ProcessShopifyOrdersJob($this->storeId, $batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $jobs[] = new ProcessShopifyOrdersJob($this->storeId, $batch);
        }

        fclose($stream);
        return $jobs;
    }
}
