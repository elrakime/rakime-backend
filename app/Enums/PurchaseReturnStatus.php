<?php

declare(strict_types=1);

namespace App\Enums;

enum PurchaseReturnStatus: string
{
    case PENDING   = 'pending';
    case COMPLETED = 'completed';

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
        return __('enums.purchase_return_status.' . $this->value);
    }

    public function get_color(): string
    {
        return match ($this) {
            self::PENDING   => 'amber',
            self::COMPLETED => 'green',
        };
    }

    public static function default(): self
    {
        return self::PENDING;
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::COMPLETED],
            self::COMPLETED => [],
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions(), true);
    }
}
