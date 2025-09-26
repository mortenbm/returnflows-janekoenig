<?php declare(strict_types=1);

namespace App\Services\BC\Sales;

use App\Actions\BC\CreateOrderAction;
use App\Models\Order;
use App\Support\BC\CustomerShippingNumber;
use App\Support\Shopify\GiftCardSkuHelper;
use Cknow\Money\Money;
use Illuminate\Support\Carbon;

class OrderHandler
{
    public function __construct(
        protected CreateOrderAction $createOrderAction,
        protected CustomerHandler $customerHandler,
    ) {
    }

    public function handle(Order $order): array
    {
        $customer = $this->customerHandler->handle($order);
        $order = [
            'orderDate' => Carbon::now()->format('Y-m-d'),
            'currencyCode' => $order->currency !== 'DKK' ? $order->currency : '',
            'customerNumber' => $customer['customer_number'],
            'externalDocumentNumber' => $order->shopify_id,
            'email' => $order->email,
            'salesOrderLines' => [
                ...$this->getOrderItems($order),
                ...$this->getShippingItem($order, $customer['customer_type'])
            ]
        ];
        return $this->createOrderAction->handle($order);
    }

    private function getOrderItems(Order $order): array
    {
        $orderItems = [];
        foreach ($order->items as $item) {
            $orderItems[] = [
                'lineType' => 'Item',
                'lineObjectNumber' => GiftCardSkuHelper::getGiftCardSku($item->sku, $order->store_id),
                'description' => $item->title,
                'quantity' => $item->quantity,
                'unitPrice' => (float)Money($item->price)->formatByDecimal(),
                'discountAmount' => (float)Money($item->discount)->formatByDecimal(),
            ];
        }
        return $orderItems;
    }

    private function getShippingItem(Order $order, string $customerType): array
    {
        if (!$order->shipping_title) {
            return [];
        }

        return [
            'lineType' => 'G/L Account',
            'lineObjectNumber' => CustomerShippingNumber::getShippingLineNumber(
                $customerType,
                optional($order->shippingAddress()->first())->country
            ),
            'description' => $order->shipping_title,
            'quantity' => 1,
            'unitPrice' => (float) Money::parse($order->shipping_amount, $order->currency)->formatByDecimal()
        ];
    }
}
