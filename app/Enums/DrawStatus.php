<?php

declare(strict_types=1);

namespace App\Enums;

enum DrawStatus: string
{
    case PAID_ON_TIME = 'paid_on_time';
    case LATE_PAYMENT = 'late_payment';
    case POSTPONED    = 'postponed';
    case FAILED       = 'failed';

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
        return __('enums.draw_status.' . $this->value);
    }

    public function get_color(): string
    {
        return match ($this) {
            self::PAID_ON_TIME => 'green',
            self::LATE_PAYMENT => 'amber',
            self::POSTPONED    => 'blue',
            self::FAILED       => 'rose',
        };
    }

    public static function default(): ?self
    {
        return null;
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PAID_ON_TIME, self::LATE_PAYMENT, self::POSTPONED, self::FAILED => [],
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions(), true);
    }
}
