<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderReturnResource\Pages;
use App\Filament\Resources\OrderReturnResource\RelationManagers;
use App\Models\OrderReturn;
use App\Models\OrderReturnItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderReturnResource extends Resource
{
    protected static ?string $model = OrderReturn::class;

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
                Infolists\Components\Section::make('Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('order_id')
                            ->label('Order ID'),
                        Infolists\Components\TextEntry::make('shopify_id')
                            ->label('Shopify ID'),
                        Infolists\Components\TextEntry::make('bc_id')
                            ->label('Business Central ID'),
                        Infolists\Components\TextEntry::make('title')
                            ->label('Shopify Increment'),
                        Infolists\Components\TextEntry::make('status'),
                        Infolists\Components\IconEntry::make('is_gift_card')
                            ->label('Is Gift Card')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime(),
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
                                    ->money(fn(OrderReturnItem $record) => $record->currency, divideBy: 100),
                                Infolists\Components\TextEntry::make('total')
                                    ->money(fn(OrderReturnItem $record) => $record->currency, divideBy: 100),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order ID'),
                Tables\Columns\TextColumn::make('shopify_id')
                    ->label('Shopify ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Shopify Increment'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->sortable()
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
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
            'index' => Pages\ListOrderReturns::route('/'),
            'create' => Pages\CreateOrderReturn::route('/create'),
            'edit' => Pages\EditOrderReturn::route('/{record}/edit'),
            'view' => Pages\ViewOrderReturn::route('/{record}'),
        ];
    }
}
