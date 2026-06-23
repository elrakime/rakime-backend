<?php

declare(strict_types=1);

namespace App\Enums;

enum PurchaseStatus: string
{
    case DRAFT         = 'draft';
    case RECEIVED      = 'received';
    case PAID          = 'paid';
    case PARTIALLY_PAID = 'partially_paid';

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
        return __('enums.purchase_status.' . $this->value);
    }

    public function get_color(): string
    {
        return match ($this) {
            self::DRAFT          => 'gray',
            self::RECEIVED       => 'blue',
            self::PAID           => 'green',
            self::PARTIALLY_PAID => 'amber',
        };
    }

    public static function default(): self
    {
        return self::DRAFT;
    }
}
