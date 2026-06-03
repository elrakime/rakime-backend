<?php

declare(strict_types=1);

namespace App\Enums;

enum InstallmentStatus: string
{
    case PENDING = 'PENDING';
    case PAID    = 'PAID';
    case OVERDUE = 'OVERDUE';

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
        return __('enums.installment_status.' . $this->value);
    }

    public function get_color(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::PAID    => 'green',
            self::OVERDUE => 'red',
        };
    }

    public static function default(): self
    {
        return self::PENDING;
    }
}
