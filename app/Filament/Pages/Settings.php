<?php declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Store;
use App\Support\Shopify\CommonFlags;
use Outerweb\FilamentSettings\Filament\Pages\Settings as BaseSettings;
use Filament\Forms;

class Settings extends BaseSettings
{
    public static function getNavigationGroup(): ?string
    {
        return config('filament-users.group');
    }

    public static function canAccess(): bool
    {
        return (bool)auth()->user()->is_super_admin;
    }

    public function schema(): array|\Closure
    {
        $stores = Store::all();
        return [
            Forms\Components\Tabs::make('Settings')
                ->schema([
                    Forms\Components\Tabs\Tab::make('General')
                    ->schema( function () use ($stores) {
                        $generalSettings = [];
                        $generalSettings[] = Forms\Components\Toggle::make('live_mode')
                            ->label('BC Order Import Live Mode')
                            ->default(false);
                        foreach ($stores as $store) {
                            $generalSettings[] =
                                Forms\Components\Section::make($store->title)
                                    ->schema([
                                        Forms\Components\DatePicker::make(
                                            sprintf('%s.%s.store', CommonFlags::ORDER_SYNC, $store->id),
                                        )
                                            ->label('Order Sync Time')
                                            ->format('Y-m-d\TH:i:s\Z')
                                    ]);
                        }
                        return $generalSettings;
                    }),
                ]),
        ];
    }
}
