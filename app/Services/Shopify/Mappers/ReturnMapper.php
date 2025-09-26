<?php declare(strict_types=1);

namespace App\Services\Shopify\Mappers;

use App\Events\Shopify\ReturnItemProcessedEvent;
use App\Events\Shopify\ReturnProcessedEvent;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Services\Shopify\Mappers\Row\GiftCartReturnMapper;
use App\Services\Shopify\Validators\GiftCardValidator;
use App\Services\Shopify\Validators\ReturnValidator;
use Cknow\Money\Money;

class ReturnMapper
{
    public static function prepare(Order $order, array $data): OrderReturn
    {
        ReturnValidator::validate($data);
        if (!empty($data['giftCard'])) {
            GiftCardValidator::validate($data['giftCard']);
        }

        $totalShippingDiscount = 0;
        if (!empty($data['refunds']['edges'])) {
            foreach ($data['refunds']['edges'] as $refund) {
                if (!empty($refund['node']['refundShippingLines']['edges'])) {
                    foreach ($refund['node']['refundShippingLines']['edges'] as $refundShippingLine) {
                        $totalShippingDiscount += (int) Money::parseByDecimal(
                            $refundShippingLine['node']['subtotalAmountSet']['shopMoney']['amount'] ?? 0,
                            $refundShippingLine['node']['subtotalAmountSet']['shopMoney']['currencyCode']
                        )->getAmount();
                        $totalShippingDiscount += (int) Money::parseByDecimal(
                            $refundShippingLine['node']['taxAmountSet']['shopMoney']['amount'] ?? 0,
                            $refundShippingLine['node']['taxAmountSet']['shopMoney']['currencyCode']
                        )->getAmount();
                    }
                }
            }
        }

        $return_label = 0;

        if (!empty($data['returnShippingFees'])) {
            foreach ($data['returnShippingFees'] as $shippingFee) {
                $return_label += (int) Money::parseByDecimal(
                    $shippingFee['amountSet']['shopMoney']['amount'] ?? 0,
                    $shippingFee['amountSet']['shopMoney']['currencyCode']
                )->getAmount();
            }
        }



        $orderReturn = OrderReturn::updateOrCreate(
            [
                'shopify_id' => $data['id']
            ],
            [
                'order_id' => $order->id,
                'store_id' => $order->store_id,
                'shopify_id' => $data['id'],
                'title' => $data['name'],
                'status' => $data['status'],
                'is_gift_card' => !empty($data['giftCard']) || !empty($data['is_gift_card']),
                'shipping_amount' => $totalShippingDiscount,
                'return_label' => $return_label,
            ]
        );

        $usedNodes = [];
        $orderReturnDiscount = 0;
        foreach ($data['returnLineItems']['nodes'] as $node) {

            if (in_array($node['fulfillmentLineItem']['lineItem']['sku'] . '_' . $node['fulfillmentLineItem']['lineItem']['id'], $usedNodes)) {
                continue;
            }
            $quantity = 0;
            foreach ($data['returnLineItems']['nodes'] as $node_) {
                if ($node_['fulfillmentLineItem']['lineItem']['sku'] == $node['fulfillmentLineItem']['lineItem']['sku']) {
                    $quantity += ($node['quantity'] ?? 1);
                }
            }

            $node['fulfillmentLineItem']['lineItem']['quantity'] = $quantity;
            $returnItem = ReturnItemMapper::prepare($node['fulfillmentLineItem']['lineItem'], $orderReturn->id);
            if ($returnItem->orderReturnLevelDiscount) {
                $orderReturnDiscount += $returnItem->orderReturnLevelDiscount;
            }

            $usedNodes[] = $node['fulfillmentLineItem']['lineItem']['sku'] . '_' . $node['fulfillmentLineItem']['lineItem']['id'];

            // dump ($returnItem);

            if (!$orderReturn->is_gift_card) {
                ReturnItemProcessedEvent::dispatch($returnItem);
            }
        }

        if (!empty($data['giftCard']) && !empty($data['giftCard']['skuList'])) {
            foreach ($order->fulfillments as $fulfillment) {
                foreach ($fulfillment->items as $fulfillmentItem) {
                    if (in_array($fulfillmentItem->sku, $data['giftCard']['skuList'])) {

                        $counts = array_count_values($data['giftCard']['skuList']);
                        $fulfillmentItem->quantity = $counts[$fulfillmentItem->sku] ?? 1;

                        ReturnItemMapper::prepare(
                            GiftCartReturnMapper::prepareGiftCardReturnItem($fulfillmentItem, $order->currency),
                            $orderReturn->id,
                            true
                        );
                    }
                }
            }
        }

        $orderReturn->update(['discount_amount' => $orderReturnDiscount]);
        ReturnProcessedEvent::dispatch($order, $orderReturn, $data);
        return $orderReturn;
    }
}
