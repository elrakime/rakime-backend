<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case ADMIN    = 'admin';
    case MANAGER  = 'manager';
    case EMPLOYEE = 'employee';

    public static function keys(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function values(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $case) => $case->get_name(), self::cases()),
        );
    }

    public static function colors(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $case) => $case->get_color(), self::cases()),
        );
    }

    public function get_name(): string
    {
        return __('roles.' . strtolower($this->name));
    }

    public function get_color(): string
    {
        return match ($this) {
            self::ADMIN     => 'red',
            self::MANAGER   => 'purple',
            self::EMPLOYEE  => 'green',
        };
    }

    public static function default(): self
    {
        return self::MANAGER;
    }

    /**
     * @return array<string>
     */
    public static function defaultPermissions(self $role): array
    {
        return match ($role) {
            self::ADMIN => array_map(fn (Permission $p) => $p->value, Permission::cases()),

            self::MANAGER => [
                // Branches
                Permission::VIEW_BRANCHES->value,
                Permission::CREATE_BRANCHES->value,
                Permission::UPDATE_BRANCHES->value,
                Permission::DELETE_BRANCHES->value,
                // Accounts
                Permission::VIEW_ACCOUNTS->value,
                Permission::CREATE_ACCOUNTS->value,
                Permission::UPDATE_ACCOUNTS->value,
                Permission::DELETE_ACCOUNTS->value,
                // Wilayas
                Permission::VIEW_WILAYAS->value,
                Permission::CREATE_WILAYAS->value,
                Permission::UPDATE_WILAYAS->value,
                Permission::DELETE_WILAYAS->value,
                // Categories
                Permission::VIEW_CATEGORIES->value,
                Permission::CREATE_CATEGORIES->value,
                Permission::UPDATE_CATEGORIES->value,
                Permission::DELETE_CATEGORIES->value,
                // Types
                Permission::VIEW_TYPES->value,
                Permission::CREATE_TYPES->value,
                Permission::UPDATE_TYPES->value,
                Permission::DELETE_TYPES->value,
                // Brands
                Permission::VIEW_BRANDS->value,
                Permission::CREATE_BRANDS->value,
                Permission::UPDATE_BRANDS->value,
                Permission::DELETE_BRANDS->value,
                // Colors
                Permission::VIEW_COLORS->value,
                Permission::CREATE_COLORS->value,
                Permission::UPDATE_COLORS->value,
                Permission::DELETE_COLORS->value,
                // Products
                Permission::VIEW_PRODUCTS->value,
                Permission::CREATE_PRODUCTS->value,
                Permission::UPDATE_PRODUCTS->value,
                Permission::DELETE_PRODUCTS->value,
                // Suppliers
                Permission::VIEW_SUPPLIERS->value,
                Permission::CREATE_SUPPLIERS->value,
                Permission::UPDATE_SUPPLIERS->value,
                Permission::DELETE_SUPPLIERS->value,
                // Clients
                Permission::VIEW_CLIENTS->value,
                Permission::CREATE_CLIENTS->value,
                Permission::UPDATE_CLIENTS->value,
                Permission::DELETE_CLIENTS->value,
                // Inventory
                Permission::VIEW_INVENTORY->value,
                Permission::CREATE_INVENTORY->value,
                Permission::UPDATE_INVENTORY->value,
                Permission::DELETE_INVENTORY->value,
                // Inventory Movements
                Permission::VIEW_INVENTORY_MOVEMENTS->value,
                // Stocks
                Permission::VIEW_STOCKS->value,
                Permission::CREATE_STOCKS->value,
                Permission::UPDATE_STOCKS->value,
                Permission::DELETE_STOCKS->value,
                // Batches
                Permission::VIEW_BATCHES->value,
                Permission::CREATE_BATCHES->value,
                Permission::UPDATE_BATCHES->value,
                Permission::DELETE_BATCHES->value,
                // Prices
                Permission::VIEW_PRICES->value,
                Permission::CREATE_PRICES->value,
                Permission::UPDATE_PRICES->value,
                Permission::DELETE_PRICES->value,
                // Purchases
                Permission::VIEW_PURCHASES->value,
                Permission::CREATE_PURCHASES->value,
                Permission::UPDATE_PURCHASES->value,
                Permission::DELETE_PURCHASES->value,
                Permission::APPROVE_PURCHASES->value,
                // Purchase Payments
                Permission::VIEW_PURCHASE_PAYMENTS->value,
                Permission::CREATE_PURCHASE_PAYMENTS->value,
                // Purchase Returns
                Permission::VIEW_PURCHASE_RETURNS->value,
                Permission::CREATE_PURCHASE_RETURNS->value,
                Permission::UPDATE_PURCHASE_RETURNS->value,
                Permission::DELETE_PURCHASE_RETURNS->value,
                Permission::APPROVE_PURCHASE_RETURNS->value,
                // Sales
                Permission::VIEW_SALES->value,
                Permission::CREATE_SALES->value,
                Permission::UPDATE_SALES->value,
                Permission::DELETE_SALES->value,
                // Inventory Transfers
                Permission::VIEW_INVENTORY_TRANSFERS->value,
                Permission::CREATE_INVENTORY_TRANSFERS->value,
                Permission::UPDATE_INVENTORY_TRANSFERS->value,
                Permission::DELETE_INVENTORY_TRANSFERS->value,
                Permission::RECEIVE_INVENTORY_TRANSFERS->value,
                // Restocks
                Permission::VIEW_RESTOCKS->value,
                Permission::CREATE_RESTOCKS->value,
                Permission::UPDATE_RESTOCKS->value,
                Permission::DELETE_RESTOCKS->value,
                Permission::APPROVE_RESTOCKS->value,
                // Expirations
                Permission::VIEW_EXPIRATIONS->value,
                Permission::CREATE_EXPIRATIONS->value,
                Permission::UPDATE_EXPIRATIONS->value,
                Permission::DELETE_EXPIRATIONS->value,
                Permission::APPROVE_EXPIRATIONS->value,
                // Wallet
                Permission::VIEW_WALLET->value,
                Permission::CREATE_WALLET->value,
                Permission::UPDATE_WALLET->value,
                Permission::DELETE_WALLET->value,
                Permission::WALLET_DEPOSIT->value,
                Permission::WALLET_WITHDRAW->value,
                // Wallet Movements
                Permission::VIEW_WALLET_MOVEMENTS->value,
                // Wallet Transfers
                Permission::VIEW_WALLET_TRANSFERS->value,
                Permission::CREATE_WALLET_TRANSFERS->value,
                Permission::DELETE_WALLET_TRANSFERS->value,
            ],

            self::EMPLOYEE => [
                // View only: branches, accounts, wilayas, categories, types, brands, colors, suppliers
                Permission::VIEW_BRANCHES->value,
                Permission::VIEW_ACCOUNTS->value,
                Permission::VIEW_WILAYAS->value,
                Permission::VIEW_CATEGORIES->value,
                Permission::VIEW_TYPES->value,
                Permission::VIEW_BRANDS->value,
                Permission::VIEW_COLORS->value,
                Permission::VIEW_SUPPLIERS->value,
                // Products
                Permission::VIEW_PRODUCTS->value,
                // Clients: full access
                Permission::VIEW_CLIENTS->value,
                Permission::CREATE_CLIENTS->value,
                Permission::UPDATE_CLIENTS->value,
                Permission::DELETE_CLIENTS->value,
                // Inventory: view + create
                Permission::VIEW_INVENTORY->value,
                Permission::CREATE_INVENTORY->value,
                // Inventory Movements
                Permission::VIEW_INVENTORY_MOVEMENTS->value,
                // Stocks: view + create
                Permission::VIEW_STOCKS->value,
                Permission::CREATE_STOCKS->value,
                // Batches: view + create
                Permission::VIEW_BATCHES->value,
                Permission::CREATE_BATCHES->value,
                // Prices: view + create
                Permission::VIEW_PRICES->value,
                Permission::CREATE_PRICES->value,
                // Purchases: view + create
                Permission::VIEW_PURCHASES->value,
                Permission::CREATE_PURCHASES->value,
                // Purchase Payments: view + create
                Permission::VIEW_PURCHASE_PAYMENTS->value,
                Permission::CREATE_PURCHASE_PAYMENTS->value,
                // Purchase Returns: view + create
                Permission::VIEW_PURCHASE_RETURNS->value,
                Permission::CREATE_PURCHASE_RETURNS->value,
                // Sales: full access
                Permission::VIEW_SALES->value,
                Permission::CREATE_SALES->value,
                Permission::UPDATE_SALES->value,
                Permission::DELETE_SALES->value,
                // Inventory Transfers: view + create
                Permission::VIEW_INVENTORY_TRANSFERS->value,
                Permission::CREATE_INVENTORY_TRANSFERS->value,
                // Restocks: view + create
                Permission::VIEW_RESTOCKS->value,
                Permission::CREATE_RESTOCKS->value,
                // Expirations: view + create
                Permission::VIEW_EXPIRATIONS->value,
                Permission::CREATE_EXPIRATIONS->value,
                // Wallet: view only
                Permission::VIEW_WALLET->value,
                // Wallet Movements: view
                Permission::VIEW_WALLET_MOVEMENTS->value,
                // Wallet Transfers: view + create
                Permission::VIEW_WALLET_TRANSFERS->value,
                Permission::CREATE_WALLET_TRANSFERS->value,
            ],
        };
    }
}
