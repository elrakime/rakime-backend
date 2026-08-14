<?php

declare(strict_types=1);

namespace App\Enums;

enum PurchasePaymentStatus: string
{
    case PAID     = 'paid';
    case CANCELED = 'canceled';

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
        return __('enums.purchase_payment_status.' . $this->value);
    }

    public function get_color(): string
    {
        return match ($this) {
            self::PAID     => 'green',
            self::CANCELED => 'red',
        };
    }

    public static function default(): self
    {
        return self::PAID;
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PAID => [self::CANCELED],
            self::CANCELED => [],
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions(), true);
    }
}
