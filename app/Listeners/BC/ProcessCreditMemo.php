<?php declare(strict_types=1);

namespace App\Listeners\BC;

use App\Events\Shopify\ReturnProcessedEvent;
use App\Models\Store;
use App\Services\BC\Sales\CreditMemoHandler;
use App\Services\Shopify\Handlers\OrderNoteHandler;
use Illuminate\Support\Facades\Log;

class ProcessCreditMemo
{
    public function __construct(
        protected CreditMemoHandler $creditMemoHandler,
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
        if (!in_array(strtolower($order->status), ['fulfilled', 'partially_fulfilled'])
            || $orderReturn->bc_id
            || $orderReturn->status !== 'CLOSED'
        ) {
            return;
        }

        $store = Store::findOrFail($order->store_id);
        if ($orderReturn->is_gift_card && !$store->is_process_gift_cards) {
            return;
        }

        if ($orderReturn->is_gift_card && !empty($event->data['partial'])) {
            $orderReturn->is_gift_card = false;
        }

        $creditMemo = $this->creditMemoHandler->handle($order, $orderReturn, $store->order_origin);
        if ($creditMemo && $creditMemo['number']) {
            Log::info('Credit Memo successfully created in BC: ' . $creditMemo['number']);
            $orderReturn->update(['bc_id' => $creditMemo['number']]);
            $this->orderNoteHandler->sendNote($order,
                sprintf('Connectify created a creditnota with number %s in BC', $creditMemo['number'])
            );
        }
    }
}
