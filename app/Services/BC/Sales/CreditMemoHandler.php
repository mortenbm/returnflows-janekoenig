<?php declare(strict_types=1);

namespace App\Services\BC\Sales;

use App\Actions\BC\CreateCreditMemoAction;
use App\Actions\BC\CreateCreditMemoLinesAction;
use App\Actions\BC\GetCreditMemoAction;
use App\Enums\CountryList;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Support\BC\CustomerShippingNumber;
use App\Support\Shopify\GiftCardSkuHelper;
use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Collection;

use Illuminate\Support\Facades\Log;
use Outerweb\Settings\Models\Setting;

class CreditMemoHandler
{
    public function __construct(
        protected CreateCreditMemoAction $createCreditMemoAction,
        protected CustomerHandler $customerHandler,
        protected GetCreditMemoAction $getCreditMemoAction,
        protected CreateCreditMemoLinesAction $createCreditMemoLinesAction
    ) {
    }

    public function handle(Order $order, OrderReturn $orderReturn, $orderStore = null): array
    {
        $customer = $this->customerHandler->handle($order);
        $creditMemo = [
            'creditMemoDate' => $orderReturn->created_at->format('Y-m-d'),
            'number' => $orderReturn->title,
            'currencyCode' => $order->currency !== 'DKK' ? $order->currency : '',
            'externalDocumentNumber' => $order->shopify_id,
            'customerNumber' => $customer['customer_number'],
            'email' => $order->email,
            'discountAmount' => (float) Money($orderReturn->discount_amount)->formatByDecimal(),
            'discountPct' => 0,
            'salesCreditMemoLines' =>
                $this->getReturnItems(
                    $orderReturn->items,
                    $order->store_id,
                    (bool) $orderReturn->is_gift_card
                ),
            ...$this->getShippingAddress($order),
            ...$this->getBillingAddress($order),
        ];
        $shippingItem = $this->getShippingItem($order, $orderReturn, $customer['customer_type']);
        if ($shippingItem) {
            $creditMemo['salesCreditMemoLines'][] = $shippingItem;
        }

        if ($orderReturn->return_label) {
            $creditMemo['salesCreditMemoLines'][] = [
                'lineType' => 'G/L Account',
                'lineObjectNumber' => CustomerShippingNumber::getShippingLineNumber(
                    $customer['customer_type'],
                    optional($order->shippingAddress()->first())->country
                ),
                'description' => 'Return label',
                'quantity' => 1,
                'unitPrice' => -1 * (float) Money::parse($orderReturn->return_label, $order->currency)->formatByDecimal()
            ];
        }

        if (Setting::get('live_mode', false)) {
            unset($creditMemo['externalDocumentNumber']);
            $creditMemo['webshopOrderNo'] = $order->name;
            $creditMemo['orderOrigin'] = $orderStore;
        }

        // check if exist CreditMemo
        $creditMemoBC = $this->getCreditMemoAction->handle('webshopOrderNo', $creditMemo['webshopOrderNo']);
        if (isset($creditMemoBC['value'][0]['id'])) {
            Log::info('CreditMemo already exist!');
            foreach ($creditMemo['salesCreditMemoLines'] as $line) {

                foreach ($creditMemoBC['value'][0]['salesCreditMemoLines'] as $creditMemoLine) {
                    if ($creditMemoLine['lineObjectNumber'] === $line['lineObjectNumber']) {
                        Log::info('CreditMemoLine already exist');
                        continue 2;
                    }
                }

                $line['documentId'] = $creditMemoBC['value'][0]['id'];
                $this->createCreditMemoLinesAction->handle($line);
            }

            $ret = $creditMemoBC['value'][0];

        } else {
            $ret = $this->createCreditMemoAction->handle($creditMemo);
        }

        return $ret;
    }

    private function getShippingAddress(Order $order): array
    {
        $shippingAddress = $order->shippingAddress()->first();
        return [
            'customerName' => $shippingAddress->first_name . ' ' . $shippingAddress->last_name,
            'phoneNumber' => $shippingAddress->phone,
            'sellToAddress' => $shippingAddress->address,
            'sellToCity' => $shippingAddress->city,
            'sellToCountry' => CountryList::tryFrom($shippingAddress->country)?->name ?? 'DK',
            'sellToPostCode' => $shippingAddress->zip,
        ];
    }

    private function getBillingAddress(Order $order): array
    {
        $billingAddress = $order->billingAddress()->first();
        return [
            'billToAddress' => $billingAddress->address,
            'billToCity' => $billingAddress->city,
            'billToCountry' => CountryList::tryFrom($billingAddress->country)?->name ?? 'DK',
            'billToPostCode' => $billingAddress->zip,
        ];
    }

    private function getShippingItem(Order $order, OrderReturn $orderReturn, string $customerType): array
    {
        if (!$orderReturn->shipping_amount) {
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
            'unitPrice' => (float) Money::parse($orderReturn->shipping_amount, $order->currency)->formatByDecimal()
        ];
    }

    private function getReturnItems(Collection $items, int $storeId, bool $isGiftCard): array
    {
        $creditMemoItems = [];
        foreach ($items as $item) {
            $creditMemoItems[] = [
                'lineType' => 'Item',
                'lineObjectNumber' => GiftCardSkuHelper::getGiftCardSku($item->sku, $storeId),
                'description' => $item->title,
                'quantity' => $item->quantity,
                'unitPrice' => !$isGiftCard ? (float) Money($item->price)->formatByDecimal() : 0.0,
                'discountAmount' => !$isGiftCard ? (float) Money($item->discount)->formatByDecimal() : 0.0,
            ];
        }
        return $creditMemoItems;
    }
}
