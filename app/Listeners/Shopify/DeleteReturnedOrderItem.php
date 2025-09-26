<?php declare(strict_types=1);

namespace App\Listeners\Shopify;

use App\Events\Shopify\ReturnItemProcessedEvent;
use App\Models\OrderItem;

class DeleteReturnedOrderItem
{
    public function handle(ReturnItemProcessedEvent $event): void
    {
        if (!$event->orderReturnItem) {
            return;
        }

        $orderLineItem = OrderItem::query()->where('shopify_id', $event->orderReturnItem->shopify_id);
        if ($orderLineItem->exists()) {
            $orderLineItem->delete();
        }
    }
}
