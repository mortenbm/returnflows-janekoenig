<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\FulfillmentItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturnItem;
use App\Models\Payment;
use App\Models\Store;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationGroup(): ?string
    {
        return __('Orders');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Tabs::make('Order Container')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('Order')
                            ->schema([
                                Infolists\Components\Section::make('Summary')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('shopify_id')
                                            ->label('Shopify ID'),
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('Shopify Increment'),
                                        Infolists\Components\TextEntry::make('bc_id')
                                            ->label('New Order ID'),
                                        Infolists\Components\TextEntry::make('status'),
                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label('Created At')
                                            ->dateTime(),
                                        Infolists\Components\TextEntry::make('email')
                                            ->icon('heroicon-m-envelope')
                                            ->iconPosition(IconPosition::Before),
                                        Infolists\Components\TextEntry::make('tags')
                                            ->listWithLineBreaks(),
                                        Infolists\Components\TextEntry::make('risk_level')
                                            ->label('Risk Level'),
                                        Infolists\Components\TextEntry::make('client_ip')
                                            ->label('Client IP'),
                                        Infolists\Components\TextEntry::make('customer_note')
                                            ->label('Customer Note'),
                                    ])->columns(),

                                Infolists\Components\Section::make('Addresses')
                                    ->schema([
                                        Infolists\Components\RepeatableEntry::make('addresses')
                                            ->hiddenLabel()
                                            ->schema([
                                                Infolists\Components\TextEntry::make('type')
                                                    ->label('Type')
                                                    ->formatStateUsing(fn(string $state) => ucfirst($state) . ' Address'
                                                    ),
                                                Infolists\Components\TextEntry::make('first_name')
                                                    ->hiddenLabel()
                                                    ->markdown()
                                                    ->formatStateUsing(function ($record) {
                                                        $formattedAddress = implode("\n", array_filter([
                                                            $record->first_name . ' ' . $record->last_name,
                                                            $record->address,
                                                            $record->phone,
                                                            $record->city,
                                                            $record->province,
                                                            $record->zip,
                                                            $record->country,
                                                            $record->company,
                                                        ]));
                                                        return nl2br($formattedAddress);
                                                    }),
                                            ])
                                            ->grid()
                                            ->columnSpanFull(),
                                    ])->columns(),

                                Infolists\Components\Section::make('Items')
                                    ->schema([
                                        Infolists\Components\RepeatableEntry::make('items')->hiddenLabel()
                                            ->schema([
                                                Infolists\Components\TextEntry::make('shopify_id')
                                                    ->label('Shopify ID'),
                                                Infolists\Components\TextEntry::make('sku'),
                                                Infolists\Components\TextEntry::make('title'),
                                                Infolists\Components\TextEntry::make('quantity'),
                                                Infolists\Components\TextEntry::make('discount')
                                                    ->money(fn(OrderItem $record) => $record->currency, divideBy: 100),
                                                Infolists\Components\TextEntry::make('total')
                                                    ->money(fn(OrderItem $record) => $record->currency, divideBy: 100),
                                            ])
                                            ->columns(3),
                                    ]),
                                Infolists\Components\Section::make('Payments')
                                    ->schema([
                                        Infolists\Components\RepeatableEntry::make('payments')->hiddenLabel()
                                            ->schema([
                                                Infolists\Components\TextEntry::make('currency'),
                                                Infolists\Components\TextEntry::make('status'),
                                                Infolists\Components\TextEntry::make('gateway_name'),
                                                Infolists\Components\TextEntry::make('amount')
                                                    ->money(fn(Payment $record) => $record->currency, divideBy: 100),
                                                Infolists\Components\TextEntry::make('shopify_id')
                                                    ->label('Shopify ID')
                                            ])->columns(3)
                                    ]),
                                Infolists\Components\Section::make('Totals')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('subtotal')
                                            ->money(fn(Order $record) => $record->currency, divideBy: 100),
                                        Infolists\Components\TextEntry::make('discount')
                                            ->money(fn(Order $record) => $record->currency, divideBy: 100),
                                        Infolists\Components\TextEntry::make('shipping_amount')
                                            ->money(fn(Order $record) => $record->currency, divideBy: 100),
                                        Infolists\Components\TextEntry::make('total')
                                            ->money(fn(Order $record) => $record->currency, divideBy: 100),
                                        Infolists\Components\TextEntry::make('currency')
                                    ])->columns()
                            ]),
                        Infolists\Components\Tabs\Tab::make('Fulfillments')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('fulfillments')->hiddenLabel()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('shopify_id')
                                            ->label('Shopify ID'),
                                        Infolists\Components\TextEntry::make('title')
                                            ->label('Shopify Increment'),
                                        Infolists\Components\TextEntry::make('status'),
                                        Infolists\Components\TextEntry::make('delivered_at')
                                            ->label('Delivered At'),
                                        Infolists\Components\TextEntry::make('estimated_delivered_at')
                                            ->label('Estimated Delivered At'),
                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label('Created At'),
                                        Infolists\Components\TextEntry::make('updated_at')
                                            ->label('Updated At'),
                                        Infolists\Components\Section::make('Items')
                                            ->schema([
                                                Infolists\Components\RepeatableEntry::make('Items')
                                                    ->hiddenLabel()
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('shopify_id')
                                                            ->label('Shopify ID'),
                                                        Infolists\Components\TextEntry::make('sku'),
                                                        Infolists\Components\TextEntry::make('title'),
                                                        Infolists\Components\TextEntry::make('quantity'),
                                                        Infolists\Components\TextEntry::make('discount')
                                                            ->money(fn(FulfillmentItem $record)
                                                                => $record->currency, divideBy: 100),
                                                        Infolists\Components\TextEntry::make('total')
                                                            ->money(fn(FulfillmentItem $record)
                                                                => $record->currency, divideBy: 100),
                                                    ])
                                                    ->columns(3),
                                            ]),
                                    ])
                                    ->columns(3),

                            ])->visible( fn (Order $record) => $record->fulfillments()->exists() ),
                        Infolists\Components\Tabs\Tab::make('Returns')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('returns')
                                    ->hiddenLabel()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('shopify_id')
                                            ->label('Shopify ID'),
                                        Infolists\Components\TextEntry::make('title')
                                            ->label('Shopify Increment'),
                                        Infolists\Components\TextEntry::make('bc_id')
                                            ->label('Business Central ID'),
                                        Infolists\Components\TextEntry::make('status'),
                                        Infolists\Components\IconEntry::make('is_gift_card')
                                            ->label('Is Gift Card')
                                            ->boolean(),
                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label('Created At'),
                                        Infolists\Components\TextEntry::make('updated_at')
                                            ->label('Updated At'),
                                        Infolists\Components\Section::make('Items')
                                            ->schema([
                                                Infolists\Components\RepeatableEntry::make('Items')
                                                    ->hiddenLabel()
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('shopify_id')
                                                            ->label('Shopify ID'),
                                                        Infolists\Components\TextEntry::make('sku'),
                                                        Infolists\Components\TextEntry::make('title'),
                                                        Infolists\Components\TextEntry::make('quantity'),
                                                        Infolists\Components\TextEntry::make('discount')
                                                            ->money(fn(OrderReturnItem $record)
                                                                => $record->currency, divideBy: 100),
                                                        Infolists\Components\TextEntry::make('total')
                                                            ->money(fn(OrderReturnItem $record)
                                                                => $record->currency, divideBy: 100),
                                                    ])
                                                    ->columns(3),
                                            ]),
                                    ])
                                    ->columns(3),
                            ])->visible( fn (Order $record) => $record->returns()->exists() )
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order ID'),
                Tables\Columns\TextColumn::make('store.title')
                    ->label('Store'),
                Tables\Columns\TextColumn::make('shopify_id')
                    ->label('Shopify ID'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Shopify Increment'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->sortable()
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('store_id')
                    ->label('Store')
                    ->options( fn (): array => Store::query()->pluck('title', 'id')->all() ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
