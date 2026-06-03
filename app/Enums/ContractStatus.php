<?php

declare(strict_types=1);

namespace App\Enums;

enum ContractStatus: string
{
    case DRAFT     = 'DRAFT';
    case PENDING   = 'PENDING';
    case APPROVED  = 'APPROVED';
    case REJECTED  = 'REJECTED';
    case CONFIRMED = 'CONFIRMED';
    case ACTIVE    = 'ACTIVE';
    case COMPLETED = 'COMPLETED';
    case CLOSED    = 'CLOSED';
    case CANCELLED = 'CANCELLED';

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
        return __('enums.contract_status.' . $this->value);
    }

    public function get_color(): string
    {
        return match ($this) {
            self::DRAFT     => 'gray',
            self::PENDING   => 'amber',
            self::APPROVED  => 'blue',
            self::REJECTED  => 'red',
            self::CONFIRMED => 'purple',
            self::ACTIVE    => 'green',
            self::COMPLETED => 'emerald',
            self::CLOSED    => 'slate',
            self::CANCELLED => 'rose',
        };
    }

    public static function default(): self
    {
        return self::DRAFT;
    }
}
