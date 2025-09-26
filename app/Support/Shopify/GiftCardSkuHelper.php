<?php declare(strict_types=1);

namespace App\Support\Shopify;

use App\Models\Store;
use Illuminate\Support\Str;

class GiftCardSkuHelper
{
    public static function getGiftCardSku(string $sku, int $storeId): string
    {
        $giftCardType = Str::between($sku, 'shopify/', '/');
        if ($giftCardType === 'GiftCard') {
            $giftCardSku = Store::query()->where('id', $storeId)->value('bc_sku') ?? $sku;
            $sku = $giftCardSku;
        }
        return $sku;
    }
}
