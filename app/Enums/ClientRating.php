<?php

declare(strict_types=1);

namespace App\Enums;

enum ClientRating: string
{
    case HIGH   = 'high';
    case MEDIUM = 'medium';
    case LOW    = 'low';
    case NONE   = 'none';

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
        return __('enums.client_rating.' . $this->value);
    }

    public function get_color(): string
    {
        return match ($this) {
            self::HIGH   => 'green',
            self::MEDIUM => 'blue',
            self::LOW    => 'amber',
            self::NONE   => 'gray',
        };
    }

    public static function default(): self
    {
        return self::NONE;
    }
}
