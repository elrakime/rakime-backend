<?php

declare(strict_types=1);

namespace App\Enums;

enum DrawStatus: string
{
    case PENDING   = 'pending';
    case RECEIVED  = 'received';
    case CANCELLED = 'cancelled';
    case FAILED    = 'failed';

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
            self::PENDING   => 'amber',
            self::RECEIVED  => 'green',
            self::CANCELLED => 'red',
            self::FAILED    => 'rose',
        };
    }

    public static function default(): self
    {
        return self::PENDING;
    }
}
