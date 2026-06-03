<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case ADMIN    = 'admin';
    case MANAGER  = 'manager';
    case EMPLOYEE = 'employee';

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
        return __('roles.' . strtolower($this->name));
    }

    public function get_color(): string
    {
        return match ($this) {
            self::ADMIN     => 'red',
            self::MANAGER   => 'purple',
            self::EMPLOYEE  => 'green',
        };
    }

    public static function default(): self
    {
        return self::MANAGER;
    }
}
