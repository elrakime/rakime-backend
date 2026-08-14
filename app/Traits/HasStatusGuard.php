<?php

declare(strict_types=1);

namespace App\Traits;

use BackedEnum;
use Exception;

trait HasStatusGuard
{
    public function assertCanTransitionTo(string|int $newStatus): void
    {
        $statusClass = $this->statusEnumClass();

        $current = $this->status;

        if (!$current instanceof $statusClass) {
            throw new Exception('Cannot transition a model without a valid current status.', 422);
        }

        $target = $statusClass::tryFrom($newStatus);

        if ($target === null) {
            throw new Exception("Invalid status value '{$newStatus}'.", 422);
        }

        if (!$current->canTransitionTo($target)) {
            throw new Exception(
                "Invalid status transition from '{$current->value}' to '{$target->value}'.",
                422,
            );
        }
    }

    protected function statusEnumClass(): string
    {
        $statusClass = $this->getCasts()['status'] ?? null;

        if (
            !is_string($statusClass)
            || !enum_exists($statusClass)
            || !is_subclass_of($statusClass, BackedEnum::class)
        ) {
            throw new Exception('Model status is not cast to a backed enum.', 422);
        }

        return $statusClass;
    }
}
