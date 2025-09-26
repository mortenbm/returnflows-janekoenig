<?php declare(strict_types=1);

namespace App\Listeners\BC;

use App\Enums\NewOrderSystemEnum;
use App\Events\Shopify\ReturnProcessedEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Store;
use App\Services\BC\Sales\OrderHandler;
use App\Services\Shopify\Handlers\OrderNoteHandler;
use Cknow\Money\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProcessOrder
{
    public function __construct(
        protected OrderHandler $orderHandler,
        protected OrderNoteHandler $orderNoteHandler,
    ) {
    }

    public function handle(ReturnProcessedEvent $event): void
    {
        if (!$event->order || !$event->orderReturn) {
            return;
        }

        $order = $event->order;
        $orderReturn = $event->orderReturn;
        if (!in_array(strtolower($order->status), ['fulfilled', 'partially_fulfilled']) || $order->bc_id) {
            return;
        }

        $store = Store::findOrFail($order->store_id);
        if (!$store->is_process_new_orders || $store->new_order_system === NewOrderSystemEnum::SHOPIFY) {
            return;
        }

        if (!empty($event->data['is_gift_card']) && empty($event->data['partial'])) {
            return;
        }

        $orderItems = $this->getRelationItems($order);
        /**
         * Potential issue
         *  - case: giftcard
         *  - partial return
         *  - is full return if there are gift card in items? Or we need order
         */
        $returnItems = $this->getRelationItems($orderReturn);
        $isFullReturn = $orderItems->every( function ($qty, $sku) use ($returnItems) {
            return !empty($returnItems[$sku]) && $returnItems[$sku] = $qty;
        });

        if (empty($event->data)) {
            return;
        }

        if ($isFullReturn && $returnItems->keys()->diff($orderItems->keys())->isEmpty()
            && empty($event->data['is_gift_card'])) {
            return;
        }

        if (empty($event->data['exchangeLineItems']['edges'])) {
            return;
        }

        $newInternalOrder = $this->getNewInternalOrder($order);
        $bcOrderItems = $this->getOrderItems($event->data['exchangeLineItems']['edges']);
        $newInternalOrder->setRelation('items', $bcOrderItems);
        $bcOrder = $this->orderHandler->handle($newInternalOrder);
        if ($bcOrder && $bcOrder['number']) {
            Log::info('Order successfully created in BC: ' . $order['number']);
            $note = sprintf('Connectify created a new order with number %s in BC', $order['number']);
            $order->update([
                'bc_id' => $order['number'],
                'customer_note' => $note,
            ]);
            $this->orderNoteHandler->sendNote($order, $note);
        }
    }

    private function getOrderItems(array $lineItems): array
    {
        $orderItems = [];
        foreach ($lineItems as $edge) {
            if (!empty($edge['node']['lineItem'])) {
                $lineItem = $edge['node']['lineItem'];
                $orderItems[] = new OrderItem([
                    'shopify_id' => $lineItem['id'],
                    'sku' => $lineItem['sku'],
                    'title' => $lineItem['title'],
                    'quantity' => $lineItem['quantity'],
                    'price' => Money::parseByDecimal(
                        $lineItem['originalUnitPriceSet']['shopMoney']['amount'],
                        $lineItem['originalUnitPriceSet']['shopMoney']['currencyCode'],
                    )->getAmount(),
                    'discount' => Money::parseByDecimal(
                        $lineItem['totalDiscountSet']['shopMoney']['amount'],
                        $lineItem['totalDiscountSet']['shopMoney']['currencyCode']
                    )->getAmount(),
                    'total' => Money::parseByDecimal(
                        $lineItem['originalTotalSet']['shopMoney']['amount'],
                        $lineItem['originalTotalSet']['shopMoney']['currencyCode']
                    )->getAmount(),
                ]);
            }
        }
        return $orderItems;
    }

    private function getNewInternalOrder(Order $order): Order
    {
        $newInternalOrder = $order->replicate();
        unset($newInternalOrder->items);
        return $newInternalOrder;
    }

    private function getRelationItems(Order|OrderReturn $parent): Collection
    {
        return $parent->items->keyBy('sku')->map( fn ($item) => $item->quantity );
    }
}
