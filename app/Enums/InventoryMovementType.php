<?php

declare(strict_types=1);

namespace App\Enums;

enum InventoryMovementType: string
{
    case RECEIVE          = 'RECEIVE';
    case RETURN           = 'RETURN';
    case TRANSFER_IN      = 'TRANSFER_IN';
    case TRANSFER_OUT     = 'TRANSFER_OUT';
    case SALE             = 'SALE';
    case EXPIRED          = 'EXPIRED';
    case RESTOCK_RECEIVED = 'RESTOCK_RECEIVED';

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
        return __('enums.inventory_movement_type.' . $this->value);
    }

    public function get_color(): string
    {
        return match ($this) {
            self::RECEIVE          => 'green',
            self::RETURN           => 'orange',
            self::TRANSFER_IN      => 'blue',
            self::TRANSFER_OUT     => 'purple',
            self::SALE             => 'emerald',
            self::EXPIRED          => 'red',
            self::RESTOCK_RECEIVED => 'teal',
        };
    }

    public static function default(): self
    {
        return self::RECEIVE;
    }
}
