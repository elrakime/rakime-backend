<?php

declare(strict_types=1);

namespace App\Enums;

enum InstallmentStatus: string
{
    case UNPAID         = 'unpaid';
    case PAID           = 'paid';
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
        return __('enums.installment_status.' . $this->value);
    }

    public function get_color(): string
    {
        return match ($this) {
            self::UNPAID         => 'amber',
            self::PAID           => 'green',
            self::PARTIALLY_PAID => 'blue',
        };
    }

    public static function default(): self
    {
        return self::UNPAID;
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::UNPAID         => [self::PAID, self::PARTIALLY_PAID],
            self::PARTIALLY_PAID => [self::PAID],
            self::PAID           => [],
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions(), true);
    }
}
