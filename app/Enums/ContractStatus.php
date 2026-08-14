<?php

declare(strict_types=1);

namespace App\Enums;

enum ContractStatus: string
{
    case PENDING   = 'pending';
    case APPROVED  = 'approved';
    case REJECTED  = 'rejected';
    case CONFIRMED = 'confirmed';
    case CONFIGURED = 'configured';
    case ACTIVE    = 'active';
    case COMPLETED = 'completed';
    case CLOSED    = 'closed';
    case CANCELLED  = 'cancelled';

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
            self::PENDING   => 'amber',
            self::APPROVED  => 'blue',
            self::REJECTED  => 'red',
            self::CONFIRMED => 'purple',
            self::ACTIVE    => 'green',
            self::COMPLETED => 'emerald',
            self::CLOSED    => 'slate',
            self::CANCELLED  => 'rose',
            self::CONFIGURED => 'teal',
        };
    }

    public static function default(): self
    {
        return self::PENDING;
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::APPROVED, self::REJECTED],
            self::APPROVED => [self::CONFIRMED],
            self::CONFIRMED => [self::CONFIGURED],
            self::CONFIGURED => [self::ACTIVE, self::CANCELLED],
            self::ACTIVE => [self::COMPLETED, self::CLOSED, self::CANCELLED],
            self::REJECTED, self::COMPLETED, self::CLOSED, self::CANCELLED => [],
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions(), true);
    }
}
