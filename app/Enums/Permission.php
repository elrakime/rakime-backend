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

    // Employees
    case VIEW_EMPLOYEES   = 'employees.view';
    case CREATE_EMPLOYEES = 'employees.create';
    case UPDATE_EMPLOYEES = 'employees.update';
    case DELETE_EMPLOYEES = 'employees.delete';

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
    case MANAGE_INVENTORY = 'inventory.manage';

    // Purchases
    case VIEW_PURCHASES    = 'purchases.view';
    case CREATE_PURCHASES  = 'purchases.create';
    case UPDATE_PURCHASES  = 'purchases.update';
    case DELETE_PURCHASES  = 'purchases.delete';
    case APPROVE_PURCHASES = 'purchases.approve';

    // Purchase Payments (2 endpoints: index, store)
    case VIEW_PURCHASE_PAYMENTS   = 'purchase_payments.view';
    case CREATE_PURCHASE_PAYMENTS = 'purchase_payments.create';

    // Purchase Returns (6 endpoints: index, store, show, update, destroy, approve)
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

    // Transfers
    case VIEW_TRANSFERS    = 'transfers.view';
    case CREATE_TRANSFERS  = 'transfers.create';
    case APPROVE_TRANSFERS = 'transfers.approve';

    // Restocks
    case VIEW_RESTOCKS    = 'restocks.view';
    case CREATE_RESTOCKS  = 'restocks.create';
    case UPDATE_RESTOCKS  = 'restocks.update';
    case DELETE_RESTOCKS  = 'restocks.delete';
    case APPROVE_RESTOCKS = 'restocks.approve';

    // Treasury
    case VIEW_TREASURY   = 'treasury.view';
    case MANAGE_TREASURY = 'treasury.manage';

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
            'users', 'employees', 'branches' => 'red',
            'categories', 'brands', 'products', 'suppliers' => 'blue',
            'clients'                         => 'teal',
            'inventory'                       => 'orange',
            'purchases', 'purchase_payments', 'purchase_returns' => 'yellow',
            'sales'                           => 'green',
            'transfers', 'restocks'            => 'cyan',
            'treasury'                        => 'emerald',
            'roles', 'permissions'            => 'indigo',
            default                           => 'gray',
        };
    }

    public static function default(): self
    {
        return self::VIEW_PRODUCTS;
    }
}
