<?php declare(strict_types=1);

namespace App\Jobs\Shopify;

use App\Actions\Shopify\FetchFulfillmentsByOrderAction;
use App\Actions\Shopify\FetchReturnsByOrderAction;
use App\Actions\Shopify\GetGiftCardByIdAction;
use App\Enums\Shopify\EntityTypesEnum;
use App\Models\Order;
use App\Models\Store;
use App\Services\Shopify\Mappers\FulfillmentMapper;
use App\Services\Shopify\Mappers\OrderItemMapper;
use App\Services\Shopify\Mappers\OrderMapper;
use App\Services\Shopify\Mappers\ReturnMapper;
use App\Services\Shopify\Mappers\Row\GiftCartReturnMapper;
use App\Services\Shopify\ShopifyClient;
use App\Services\Shopify\ShopifyClientFactory;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessShopifyOrdersJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable, Batchable;

    protected $tries = 3;

    protected ?ShopifyClient $shopifyClient = null;

    public function __construct(
        protected int $storeId,
        protected array $rows = [],
        protected array $orderGiftCardIds = [],
    ) {
    }

    public function uniqueId(): int
    {
        return $this->storeId;
    }

    public function handle(): void
    {
        $this->processRows();
        $this->applyGiftCards();
    }

    private function processRows(): void
    {
        foreach ($this->rows as $row) {
            try {
                DB::transaction( fn () => $this->resolveRow($row));
            } catch (\Throwable $e) {
                Log::channel('shopify_graph')->error($e);
            }
        }
    }

    private function applyGiftCards(): void
    {
        foreach ($this->orderGiftCardIds as $orderId => $giftCardData) {
            try {
                $order = Order::query()->with(['fulfillments', 'fulfillments.items'])->find($orderId);
                if (!$order || !$order->fulfillments()->exists()) {
                    return;
                }

                $getGiftCardByIdAction = app(GetGiftCardByIdAction::class, ['shopifyClient' => $this->getClient()]);
                $giftCard = $getGiftCardByIdAction->handle($giftCardData['giftCardId']);
                if (!$giftCard) {
                    throw new \Exception(
                        'Missing git card with id: ' . $giftCardData['giftCardId']
                    );
                }

                if (!isset($giftCardData['isReturn'])) {
                    GiftCartReturnMapper::prepare($order, $giftCard['data']['giftCard']);
                }

                if (!empty($giftCardData['isReturn'])) {
                    $giftCard['data']['giftCard']['skuList'] = !empty($giftCardData['giftCardSkuList']) ?
                        json_decode($giftCardData['giftCardSkuList']) : [];
                    $fetchReturnsByOrderAction = app(
                        FetchReturnsByOrderAction::class,
                        ['shopifyClient' => $this->getClient()]
                    );
                    $returns = $fetchReturnsByOrderAction->handle($order->shopify_id);
                    if (!empty($returns['data']['order']['returns']['nodes'])) {
                        $returns = $returns['data']['order']['returns']['nodes'];
                        collect($returns)->map( function ($return) use ($order, &$giftCard) {
                            $return['giftCard'] = $giftCard['data']['giftCard'];
                            $return['partial'] = true;
                            ReturnMapper::prepare($order, $return);
                            $giftCard['data']['giftCard']['skuList'] = [];
                        });
                    }
                }
            } catch (\Throwable $e) {
                Log::channel('shopify_graph')->error($e);
            }
        }
    }

    private function resolveRow(array $row): void
    {
        if (empty($row['id'])) {
            throw new \Exception('Missing entity id in row');
        }

        $row['store_id'] = $this->storeId;
        $entityType = Str::between($row['id'], 'shopify/', '/');
        $entityType = EntityTypesEnum::tryFrom($entityType);
        match ($entityType) {
            EntityTypesEnum::ORDER => $this->processOrder($row),
            EntityTypesEnum::LINE_ITEM => $this->processLineItem($row),
            EntityTypesEnum::RETURN => $this->processReturn($row),
            default => throw new \Exception('Unknown entity type'),
        };
    }

    private function processOrder(array $row): void
    {
        Log::channel('shopify_graph')->info('Order processed start: ' . $row['id'] . ' - ' . $row['name'] ?? '');
        $order = OrderMapper::prepare($row);
        Log::channel('shopify_graph')->info('Order processed successfully: ' . $row['id'] . ' - ' . $row['name'] ?? '');

        if (!empty($row['fulfillments'])) {
            $this->processFulfillments($order);
        }

        if (!empty($row['giftCardId']) && !empty($row['giftCardSkuList'])) {
            $this->orderGiftCardIds[$order->id]['giftCardId'] = $row['giftCardId']['value'];
            $this->orderGiftCardIds[$order->id]['giftCardSkuList'] = $row['giftCardSkuList']['value'];
        }
    }

    private function processLineItem(array $row): void
    {
        OrderItemMapper::prepare($row);
    }

    private function processFulfillments(Order $order): void
    {
        $fetchFulfillmentsByOrderAction = app(
            FetchFulfillmentsByOrderAction::class,
            ['shopifyClient' => $this->getClient()]
        );
        $fulfillments = $fetchFulfillmentsByOrderAction->handle($order->shopify_id);
        if (!empty($fulfillments['data']['order']['fulfillments'])) {
            $fulfillments = $fulfillments['data']['order']['fulfillments'];
            collect($fulfillments)->map( fn ($fulfillment) => FulfillmentMapper::prepare($order, $fulfillment) );
        }
    }

    private function processReturn(array $row): void
    {
        if (empty($row['__parentId'])) {
            throw new \Exception('Missing parentId in return entity: ' . $row['id']);
        }

        $order = Order::query()
            ->with(['fulfillments', 'fulfillments.items'])
            ->firstWhere(column: 'shopify_id', operator: '=', value: $row['__parentId']);
        if (!$order || !$order->id || !$order->fulfillments()->exists()) {
            throw new \Exception('Order doesn\'t exist for item: ' . $row['id']);
        }

        if (array_key_exists($order->id, $this->orderGiftCardIds)) {
            $this->orderGiftCardIds[$order->id]['isReturn'] = true;
            return;
        }

        $fetchReturnsByOrderAction = app(FetchReturnsByOrderAction::class, ['shopifyClient' => $this->getClient()]);
        $returns = $fetchReturnsByOrderAction->handle($order->shopify_id);
        if (!empty($returns['data']['order']['returns']['nodes'])) {
            $returns = $returns['data']['order']['returns']['nodes'];
            collect($returns)->map( fn ($return) => ReturnMapper::prepare($order, $return) );
        }
    }

    private function getClient(): ShopifyClient
    {
        if ($this->shopifyClient === null) {
            $store = Store::findOrFail($this->storeId);
            $shopifyClientFactory = app(ShopifyClientFactory::class);
            $shopifyClient = $shopifyClientFactory->makeByStore($store);
            $this->shopifyClient = $shopifyClient;
        }
        return $this->shopifyClient;
    }
}
