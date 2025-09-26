<?php

namespace App\Filament\Resources;

use App\Enums\NewOrderSystemEnum;
use App\Filament\Resources\StoreResource\Pages;
use App\Filament\Resources\StoreResource\RelationManagers;
use App\Models\Store;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationGroup(): ?string
    {
        if (config('filament-users.shield')) {
            return __('filament-shield::filament-shield.nav.group');
        }

        return config('filament-users.group') ?: trans('filament-users::user.group');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->is_super_admin;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label('Is Active'),
                Forms\Components\Toggle::make('is_process_gift_cards')
                    ->label('Create Gift Cards Order/CreditMemo'),
                Forms\Components\Toggle::make('is_process_new_orders')
                    ->label('Create New Orders'),
                Forms\Components\Select::make('user_id')
                    ->label('Store Owner')
                    ->options(User::all()->pluck('name', 'id'))
                    ->relationship('users', 'name')
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->required(),
                Forms\Components\TextInput::make('shopify_domain')
                    ->label('Shopify Domain')
                    ->required(),
                Forms\Components\TextInput::make('shopify_client_id')
                    ->label('Shopify Client ID')
                    ->required(),
                Forms\Components\TextInput::make('shopify_client_secret')
                    ->label('Shopify Client Secret')
                    ->autocomplete(false)
                    ->password()
                    ->revealable()
                    ->required(),
                Forms\Components\TextInput::make('shopify_access_token')
                    ->label('Shopify Access Token')
                    ->autocomplete(false)
                    ->password()
                    ->revealable()
                    ->required(),
                Forms\Components\TextInput::make('bc_sku')
                    ->label('BC SKU')
                    ->required(),
                Forms\Components\Select::make('new_order_system')
                    ->label('New Order System')
                    ->options(NewOrderSystemEnum::class),
                Forms\Components\TextInput::make('order_origin')
                    ->label('Order Origin')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('users.name')
                    ->label('Store Owner'),
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Is Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('shopify_domain')
                    ->label('Shopify Domain'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }
}
