<?php

declare(strict_types=1);

namespace App\Enums;

enum PurchasePaymentMethod: string
{
    case CASH = 'CASH';
    case BANK = 'BANK';

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
        return __('enums.purchase_payment_method.' . $this->value);
    }

    public function get_color(): string
    {
        return match ($this) {
            self::CASH => 'green',
            self::BANK => 'blue',
        };
    }

    public static function default(): self
    {
        return self::CASH;
    }
}
