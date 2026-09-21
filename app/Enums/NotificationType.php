<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationType: string
{
    // Approval requests (R1: notify admins)
    case CONTRACT_CREATED          = 'contract_created';
    case PURCHASE_CREATED          = 'purchase_created';
    case PURCHASE_RETURN_CREATED   = 'purchase_return_created';
    case SALE_RETURN_CREATED       = 'sale_return_created';
    case RESTOCK_CREATED           = 'restock_created';
    case INVENTORY_TRANSFER_CREATED = 'inventory_transfer_created';
    case EXPIRATION_CREATED        = 'expiration_created';
    case WALLET_TRANSFER_CREATED   = 'wallet_transfer_created';

    // Admin decisions/actions (R2: notify branch staff)
    case CONTRACT_APPROVED         = 'contract_approved';
    case CONTRACT_REJECTED         = 'contract_rejected';
    case CONTRACT_CONFIGURED       = 'contract_configured';
    case CONTRACT_CANCELLED        = 'contract_cancelled';
    case CONTRACT_EXTENDED         = 'contract_extended';
    case PURCHASE_RECEIVED         = 'purchase_received';
    case PURCHASE_CANCELLED        = 'purchase_cancelled';
    case PURCHASE_RETURN_APPROVED  = 'purchase_return_approved';
    case PURCHASE_RETURN_CANCELLED = 'purchase_return_cancelled';
    case SALE_RETURN_APPROVED      = 'sale_return_approved';
    case SALE_RETURN_CANCELLED     = 'sale_return_cancelled';
    case RESTOCK_FULFILLED         = 'restock_fulfilled';
    case RESTOCK_CANCELLED         = 'restock_cancelled';
    case INVENTORY_TRANSFER_DISPATCHED = 'inventory_transfer_dispatched';
    case INVENTORY_TRANSFER_RECEIVED   = 'inventory_transfer_received';
    case INVENTORY_TRANSFER_CANCELLED  = 'inventory_transfer_cancelled';
    case EXPIRATION_APPROVED       = 'expiration_approved';
    case PURCHASE_PAYMENT_CANCELLED = 'purchase_payment_cancelled';

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

    public function get_name(): string
    {
        return __('enums.notification_type.' . $this->value);
    }
}
