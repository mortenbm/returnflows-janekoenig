<?php declare(strict_types=1);

namespace App\Services\Shopify\Mappers\Row;

use App\Models\FulfillmentItem;
use App\Models\Order;
use App\Services\Shopify\Mappers\ReturnMapper;
use App\Services\Shopify\Validators\GiftCardValidator;
use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Collection;

class GiftCartReturnMapper
{
    public static function prepare(Order $order, array $giftCard): void
    {
        GiftCardValidator::validate($giftCard);
        foreach ($order->fulfillments as $key => $fulfillment) {
            $data = [
                'id' => $order->shopify_id,
                'name' => $order->name . '-R' . ++$key,
                'status' => 'OPEN',
                'exchangeLineItems' => [
                    'edges' => [
                        [
                            'node' => [
                                'lineItem' => [
                                    'id' => $giftCard['id'],
                                    'sku' => $giftCard['id'],
                                    'title' => $giftCard['note'],
                                    'quantity' => 1,
                                    'originalUnitPriceSet' => [
                                        'shopMoney' => [
                                            'amount' => $giftCard['balance']['amount'],
                                            'currencyCode' => $giftCard['balance']['currencyCode'],
                                        ]
                                    ],
                                    'totalDiscountSet' => [
                                        'shopMoney' => [
                                            'amount' => 0,
                                            'currencyCode' => $giftCard['balance']['currencyCode'],
                                        ]
                                    ],
                                    'originalTotalSet' => [
                                        'shopMoney' => [
                                            'amount' => $giftCard['balance']['amount'],
                                            'currencyCode' => $giftCard['balance']['currencyCode'],
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ]
                ],
                'returnLineItems' => [
                    'nodes' => self::processGiftCardReturnItems($order, $fulfillment->items)
                ],
                'is_gift_card' => true
            ];

            ReturnMapper::prepare($order, $data);
        }
    }

    public static function prepareGiftCardReturnItem(FulfillmentItem $fulfillmentItem, string $currency): array
    {
        return [
            'id' => $fulfillmentItem->shopify_id,
            'sku' => $fulfillmentItem->sku,
            'title' => $fulfillmentItem->title,
            'quantity' => $fulfillmentItem->quantity,
            'discountedUnitPriceSet' => [
                'shopMoney' => [
                    'amount' => '0.00',
                    'currencyCode' => $currency,
                ]
            ],
            'discountedUnitPriceAfterAllDiscountsSet' => [
                'shopMoney' => [
                    'amount' => '0.00',
                    'currencyCode' => $currency,
                ]
            ],
            'originalUnitPriceSet' => [
                'shopMoney' => [
                    'amount' => '0.00',
                    'currencyCode' => $currency,
                ]
            ],
            'totalDiscountSet' => [
                'shopMoney' => [
                    'amount' => '0.00',
                    'currencyCode' => $currency,
                ]
            ],
            'discountedTotalSet' => [
                'shopMoney' => [
                    'amount' => '0.00',
                    'currencyCode' => $currency,
                ]
            ],
            'originalTotalSet' => [
                'shopMoney' => [
                    'amount' => '0.00',
                    'currencyCode' => $currency,
                ]
            ],
        ];
    }

    private static function processGiftCardReturnItems(Order $order, Collection $items): array
    {
        $returnItems = [];
        foreach ($items as $item) {
            $returnItems[] = [
                'fulfillmentLineItem' => [
                    'lineItem' => [
                        'id' => $item->shopify_id,
                        'sku' => $item->sku,
                        'title' => $item->title,
                        'quantity' => $item->quantity,
                        'discountedUnitPriceSet' => [
                            'shopMoney' => [
                                'amount' => '0',
                                'currencyCode' => $order->currency,
                            ]
                        ],
                        'discountedUnitPriceAfterAllDiscountsSet' => [
                            'shopMoney' => [
                                'amount' => '0',
                                'currencyCode' => $order->currency,
                            ]
                        ],
                        'discountedTotalSet' => [
                            'shopMoney' => [
                                'amount' => '0',
                                'currencyCode' => $order->currency,
                            ]
                        ],
                        'originalUnitPriceSet' => [
                            'shopMoney' => [
                                'amount' => Money::parse($item->price, $order->currency)->formatByDecimal(),
                                'currencyCode' => $order->currency,
                            ]
                        ],
                        'totalDiscountSet' => [
                            'shopMoney' => [
                                'amount' => Money::parse($item->discount, $order->currency)->formatByDecimal(),
                                'currencyCode' => $order->currency,
                            ]
                        ],
                        'originalTotalSet' => [
                            'shopMoney' => [
                                'amount' => Money::parse($item->total, $order->currency)->formatByDecimal(),
                                'currencyCode' => $order->currency,
                            ]
                        ],
                    ]
                ]
            ];
        }
        return $returnItems;
    }
}
