<?php declare(strict_types=1);

namespace App\Services\Shopify\Handlers;

use App\Actions\Shopify\AddOrderNoteAction;
use App\Models\Order;
use App\Models\Store;
use App\Services\Shopify\ShopifyClientFactory;

class OrderNoteHandler
{
    public function sendNote(Order $order, string $message): void
    {
        $store = Store::findOrFail($order->store_id);
        $shopifyClientFactory = app(ShopifyClientFactory::class);
        $shopifyClient = $shopifyClientFactory->makeByStore($store);
        $orderNoteAction = app(AddOrderNoteAction::class, ['shopifyClient' => $shopifyClient]);
        $orderNoteAction->handle([
            'order_id' => $order->shopify_id,
            'note' => $message,
        ]);
    }
}
