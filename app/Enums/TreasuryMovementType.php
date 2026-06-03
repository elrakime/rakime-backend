<?php

declare(strict_types=1);

namespace App\Enums;

enum TreasuryMovementType: string
{
    case DEPOSIT             = 'DEPOSIT';
    case WITHDRAWAL          = 'WITHDRAWAL';
    case TRANSFER_IN         = 'TRANSFER_IN';
    case TRANSFER_OUT        = 'TRANSFER_OUT';
    case INSTALLMENT_PAYMENT = 'INSTALLMENT_PAYMENT';
    case PURCHASE_PAYMENT    = 'PURCHASE_PAYMENT';
    case SALE_PAYMENT        = 'SALE_PAYMENT';
    case ADJUSTMENT          = 'ADJUSTMENT';

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
        return __('enums.treasury_movement_type.' . $this->value);
    }

    public function get_color(): string
    {
        return match ($this) {
            self::DEPOSIT             => 'green',
            self::WITHDRAWAL          => 'red',
            self::TRANSFER_IN         => 'blue',
            self::TRANSFER_OUT        => 'purple',
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
