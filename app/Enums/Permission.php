<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    // Users
    case VIEW_USERS   = 'users.view';
    case CREATE_USERS = 'users.create';
    case UPDATE_USERS = 'users.update';
    case DELETE_USERS = 'users.delete';

    // Branches
    case VIEW_BRANCHES   = 'branches.view';
    case CREATE_BRANCHES = 'branches.create';
    case UPDATE_BRANCHES = 'branches.update';
    case DELETE_BRANCHES = 'branches.delete';

    // Accounts
    case VIEW_ACCOUNTS   = 'accounts.view';
    case CREATE_ACCOUNTS = 'accounts.create';
    case UPDATE_ACCOUNTS = 'accounts.update';
    case DELETE_ACCOUNTS = 'accounts.delete';

    // Wilayas
    case VIEW_WILAYAS   = 'wilayas.view';
    case CREATE_WILAYAS = 'wilayas.create';
    case UPDATE_WILAYAS = 'wilayas.update';
    case DELETE_WILAYAS = 'wilayas.delete';

    // Categories
    case VIEW_CATEGORIES   = 'categories.view';
    case CREATE_CATEGORIES = 'categories.create';
    case UPDATE_CATEGORIES = 'categories.update';
    case DELETE_CATEGORIES = 'categories.delete';

    // Types
    case VIEW_TYPES   = 'types.view';
    case CREATE_TYPES = 'types.create';
    case UPDATE_TYPES = 'types.update';
    case DELETE_TYPES = 'types.delete';

    // Brands
    case VIEW_BRANDS   = 'brands.view';
    case CREATE_BRANDS = 'brands.create';
    case UPDATE_BRANDS = 'brands.update';
    case DELETE_BRANDS = 'brands.delete';

    // Colors
    case VIEW_COLORS   = 'colors.view';
    case CREATE_COLORS = 'colors.create';
    case UPDATE_COLORS = 'colors.update';
    case DELETE_COLORS = 'colors.delete';

    // Products
    case VIEW_PRODUCTS   = 'products.view';
    case CREATE_PRODUCTS = 'products.create';
    case UPDATE_PRODUCTS = 'products.update';
    case DELETE_PRODUCTS = 'products.delete';

    // Suppliers
    case VIEW_SUPPLIERS   = 'suppliers.view';
    case CREATE_SUPPLIERS = 'suppliers.create';
    case UPDATE_SUPPLIERS = 'suppliers.update';
    case DELETE_SUPPLIERS = 'suppliers.delete';

    // Clients
    case VIEW_CLIENTS   = 'clients.view';
    case CREATE_CLIENTS = 'clients.create';
    case UPDATE_CLIENTS = 'clients.update';
    case DELETE_CLIENTS = 'clients.delete';

    // Inventory
    case VIEW_INVENTORY   = 'inventory.view';
    case CREATE_INVENTORY = 'inventory.create';
    case UPDATE_INVENTORY = 'inventory.update';
    case DELETE_INVENTORY = 'inventory.delete';

    // Inventory Movements
    case VIEW_INVENTORY_MOVEMENTS = 'inventory_movements.view';

    // Stocks
    case VIEW_STOCKS   = 'stocks.view';
    case CREATE_STOCKS = 'stocks.create';
    case UPDATE_STOCKS = 'stocks.update';
    case DELETE_STOCKS = 'stocks.delete';

    // Batches
    case VIEW_BATCHES   = 'batches.view';
    case CREATE_BATCHES = 'batches.create';
    case UPDATE_BATCHES = 'batches.update';
    case DELETE_BATCHES = 'batches.delete';

    // Prices
    case VIEW_PRICES   = 'prices.view';
    case CREATE_PRICES = 'prices.create';
    case UPDATE_PRICES = 'prices.update';
    case DELETE_PRICES = 'prices.delete';

    // Purchases
    case VIEW_PURCHASES    = 'purchases.view';
    case CREATE_PURCHASES  = 'purchases.create';
    case UPDATE_PURCHASES  = 'purchases.update';
    case DELETE_PURCHASES  = 'purchases.delete';
    case APPROVE_PURCHASES = 'purchases.approve';

    // Purchase Payments
    case VIEW_PURCHASE_PAYMENTS   = 'purchase_payments.view';
    case CREATE_PURCHASE_PAYMENTS = 'purchase_payments.create';

    // Purchase Returns
    case VIEW_PURCHASE_RETURNS    = 'purchase_returns.view';
    case CREATE_PURCHASE_RETURNS  = 'purchase_returns.create';
    case UPDATE_PURCHASE_RETURNS  = 'purchase_returns.update';
    case DELETE_PURCHASE_RETURNS  = 'purchase_returns.delete';
    case APPROVE_PURCHASE_RETURNS = 'purchase_returns.approve';

    // Sales
    case VIEW_SALES   = 'sales.view';
    case CREATE_SALES = 'sales.create';
    case UPDATE_SALES = 'sales.update';
    case DELETE_SALES = 'sales.delete';

    // Inventory Transfers
    case VIEW_INVENTORY_TRANSFERS    = 'inventory_transfers.view';
    case CREATE_INVENTORY_TRANSFERS  = 'inventory_transfers.create';
    case UPDATE_INVENTORY_TRANSFERS  = 'inventory_transfers.update';
    case DELETE_INVENTORY_TRANSFERS  = 'inventory_transfers.delete';
    case RECEIVE_INVENTORY_TRANSFERS = 'inventory_transfers.receive';

    // Restocks
    case VIEW_RESTOCKS    = 'restocks.view';
    case CREATE_RESTOCKS  = 'restocks.create';
    case UPDATE_RESTOCKS  = 'restocks.update';
    case DELETE_RESTOCKS  = 'restocks.delete';
    case APPROVE_RESTOCKS = 'restocks.approve';

    // Expirations
    case VIEW_EXPIRATIONS    = 'expirations.view';
    case CREATE_EXPIRATIONS  = 'expirations.create';
    case UPDATE_EXPIRATIONS  = 'expirations.update';
    case DELETE_EXPIRATIONS  = 'expirations.delete';
    case APPROVE_EXPIRATIONS = 'expirations.approve';

    // Wallet
    case VIEW_WALLET    = 'wallet.view';
    case CREATE_WALLET  = 'wallet.create';
    case UPDATE_WALLET  = 'wallet.update';
    case DELETE_WALLET  = 'wallet.delete';
    case WALLET_DEPOSIT = 'wallet.deposit';
    case WALLET_WITHDRAW = 'wallet.withdraw';

    // Wallet Movements
    case VIEW_WALLET_MOVEMENTS = 'wallet_movements.view';

    // Wallet Transfers
    case VIEW_WALLET_TRANSFERS   = 'wallet_transfers.view';
    case CREATE_WALLET_TRANSFERS = 'wallet_transfers.create';
    case DELETE_WALLET_TRANSFERS = 'wallet_transfers.delete';

    // Roles
    case VIEW_ROLES   = 'roles.view';
    case CREATE_ROLES = 'roles.create';
    case UPDATE_ROLES = 'roles.update';
    case DELETE_ROLES = 'roles.delete';

    // Permissions
    case VIEW_PERMISSIONS = 'permissions.view';

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
        return __('permissions.' . strtolower($this->name));
    }

    public function get_color(): string
    {
        return match (explode('.', $this->value)[0]) {
            'users', 'branches'               => 'red',
            'categories', 'brands', 'products', 'suppliers', 'accounts', 'wilayas', 'types', 'colors' => 'blue',
            'clients'                         => 'teal',
            'inventory', 'inventory_movements', 'stocks', 'batches', 'prices' => 'orange',
            'purchases', 'purchase_payments', 'purchase_returns' => 'yellow',
            'sales'                           => 'green',
            'inventory_transfers', 'restocks', 'expirations' => 'cyan',
            'wallet', 'wallet_movements', 'wallet_transfers' => 'emerald',
            'roles', 'permissions'            => 'indigo',
            default                           => 'gray',
        };
    }

    public static function default(): self
    {
        return self::VIEW_PRODUCTS;
    }
}
