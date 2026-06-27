<?php

declare(strict_types=1);

namespace App\Enums;

enum WalletMovementType: string
{
    case DEPOSIT             = 'deposit';
    case WITHDRAWAL          = 'withdrawal';
    case TRANSFER_IN         = 'transfer_in';
    case TRANSFER_OUT        = 'transfer_out';
    case EXPENSE             = 'expense';
    case RETURN              = 'return';
    case INSTALLMENT_PAYMENT = 'installment_payment';
    case PURCHASE_PAYMENT    = 'purchase_payment';
    case SALE_PAYMENT        = 'sale_payment';
    case ADJUSTMENT          = 'adjustment';

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
        return __('enums.wallet_movement_type.' . $this->value);
    }

    public function get_color(): string
    {
        return match ($this) {
            self::DEPOSIT             => 'green',
            self::WITHDRAWAL          => 'red',
            self::TRANSFER_IN         => 'blue',
            self::TRANSFER_OUT        => 'purple',
            self::EXPENSE             => 'orange',
            self::RETURN              => 'emerald',
            self::INSTALLMENT_PAYMENT => 'amber',
            self::PURCHASE_PAYMENT    => 'orange',
            self::SALE_PAYMENT        => 'emerald',
            self::ADJUSTMENT          => 'gray',
        };
    }

    public static function default(): self
    {
        return self::DEPOSIT;
    }
}
