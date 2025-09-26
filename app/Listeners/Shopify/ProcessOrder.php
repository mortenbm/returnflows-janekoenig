<?php declare(strict_types=1);

namespace App\Listeners\Shopify;

use App\Actions\Shopify\CreateOrder;
use App\Enums\NewOrderSystemEnum;
use App\Events\Shopify\ReturnProcessedEvent;
use App\Models\Store;
use App\Services\Shopify\ShopifyClientFactory;
use Cknow\Money\Money;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessOrder
{
    public function handle(ReturnProcessedEvent $event): void
    {
        try {
            if (!$event->order || !$event->orderReturn) {
                return;
            }

            $order = $event->order;
            if (!in_array(strtolower($order->status), ['fulfilled', 'partially_fulfilled']) || $order->bc_id) {
                return;
            }

            $store = Store::findOrFail($order->store_id);
            if (!$store->is_process_new_orders || $store->new_order_system === NewOrderSystemEnum::BC) {
                return;
            }

            if (!empty($event->data['is_gift_card']) && empty($event->data['partial'])) {
                return;
            }

            if (empty($event->data) || empty($event->data['exchangeLineItems']['edges'])) {
                return;
            }

            $tags = $order->tags;
            $tags[] = $order->shopify_id;
            $shippingAddress = $order->shippingAddress->first();
            $billingAddress = $order->billingAddress->first();
            $requireShipping = (bool)$order->shipping_amount;
            $shopifyOrder = [
                'options' => [
                    'inventoryBehaviour' => 'BYPASS'
                ],
                'order' => [
                    'email' => $order->email,
                    'tags' => $tags,
                    'note' => 'Original order: ' . $order->shopify_id,
                    'currency' => $order->currency,
                    'shippingAddress' => [
                        'firstName' => $shippingAddress->first_name,
                        'lastName' => $shippingAddress->last_name,
                        'address1' => $shippingAddress->address,
                        'city' => $shippingAddress->city,
                        'province' => $shippingAddress->province,
                        'country' => $shippingAddress->country,
                        'zip' => $shippingAddress->zip,
                        'phone' => $shippingAddress?->phone,
                        'company' => $shippingAddress?->company,
                    ],
                    'billingAddress' => [
                        'firstName' => $billingAddress->first_name,
                        'lastName' => $billingAddress->last_name,
                        'address1' => $billingAddress->address,
                        'city' => $billingAddress->city,
                        'province' => $billingAddress->province,
                        'country' => $billingAddress->country,
                        'zip' => $billingAddress->zip,
                        'phone' => $billingAddress?->phone,
                        'company' => $billingAddress?->company,
                    ],
                    'lineItems' => $this->getOrderItems($event->data['exchangeLineItems']['edges'], $requireShipping),
                    'metafields' => [
                        [
                            'key' => 'order_id',
                            'namespace' => 'returnflows',
                            'value' => $order->shopify_id,
                            'type' => 'single_line_text_field'
                        ]
                    ],
                    'transactions' => [
                        'kind' => 'SALE',
                        'status' => 'SUCCESS',
                        'amountSet' => [
                            'shopMoney' => [
                                'amount' => $this->getOrderAmount($event->data['exchangeLineItems']['edges'], $order),
                                'currencyCode' => $order->currency
                            ]
                        ]
                    ]
                ],
            ];

            if ($requireShipping) {
                $shopifyOrder['order']['shippingLines'] = [
                    'title' => $order->shipping_title,
                    'code' => $order->shipping_code,
                    'source' => $order->shipping_source,
                    'priceSet' => [
                        'shopMoney' => [
                            'amount' => (float) Money::parse($order->shipping_amount, $order->currency)->formatByDecimal(),
                            'currencyCode' => $order->currency
                        ]
                    ]
                ];
            }
            $shopifyClientFactory = app(ShopifyClientFactory::class);
            $shopifyClient = $shopifyClientFactory->makeByStore($store);
            $createOrderAction = app(CreateOrder::class, ['shopifyClient' => $shopifyClient]);
            $newShopifyOrder = $createOrderAction->handle($shopifyOrder);
            $order->update(['bc_id' => $newShopifyOrder['id']]);
        } catch (\Throwable $e) {
            Log::error('Can not create order in the Shopify. Details:');
            Log::error($e);
        }
    }

    private function getOrderAmount(array $items, $order): float
    {
        $amount = 0;
        foreach ($items as $edge) {
            if (!empty($edge['node']['lineItem']['originalTotalSet'])) {
                $amount += (float)$edge['node']['lineItem']['originalTotalSet']['shopMoney']['amount'];
            }
        }

        $shippingAmount = $order->shipping_amount
            ? (float) Money::parse($order->shipping_amount, $order->currency)->formatByDecimal()
            : 0;
        return (float)$amount + $shippingAmount;
    }

    private function getOrderItems(array $lineItems, $requireShipping): array
    {
        $orderItems = [];
        foreach ($lineItems as $edge) {
            if (!empty($edge['node']['lineItem'])) {
                $lineItem = $edge['node']['lineItem'];
                $orderItem = [
                    'title' => $lineItem['title'],
                    'sku' => $lineItem['sku'],
                    'quantity' => $lineItem['quantity'],
                    'priceSet' => [
                        'shopMoney' => [
                            'amount' => (float)$lineItem['originalUnitPriceSet']['shopMoney']['amount'],
                            'currencyCode' => $lineItem['originalUnitPriceSet']['shopMoney']['currencyCode'],
                        ]
                    ],
                    'requiresShipping' => $requireShipping,
                ];

                if (!empty($lineItem['variant'])) {
                    if (!empty($lineItem['variant']['id']) && !empty($lineItem['variant']['title'])) {
                        $orderItem['variantId'] = $lineItem['variant']['id'];
                        $orderItem['variantTitle'] = $lineItem['variant']['title'];
                    }

                    if (!empty($lineItem['variant']['inventoryItem'])
                        && !empty($lineItem['variant']['inventoryItem']['measurement'])
                        && !empty($lineItem['variant']['inventoryItem']['measurement']['weight'])) {
                        $orderItem['weight'] = [
                            'unit' => $lineItem['variant']['inventoryItem']['measurement']['weight']['unit'],
                            'value' => $lineItem['variant']['inventoryItem']['measurement']['weight']['value']
                        ];
                    }
                }
                $orderItems[] = $orderItem;
            }
        }
        return $orderItems;
    }
}
