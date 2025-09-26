<?php declare(strict_types=1);

namespace App\Providers;

use App\Services\Shopify\ShopifyClientFactory;
use Illuminate\Support\ServiceProvider;

class ShopifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ShopifyClientFactory::class, function () {
            return new ShopifyClientFactory();
        });
    }

    public function boot(): void {}
}
