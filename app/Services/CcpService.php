<?php

namespace App\Services;

class CcpService
{
    public function __construct(
        private string $ccp
    ) {}

    /**
     * Calculate and return the key (clé) of the account.
     */
    public function getKey(): string
    {
        $ccp = str_pad($this->ccp, 10, '0', STR_PAD_LEFT);
        $values = str_split($ccp);
        $total = 0;
        $z = 9;

        for ($i = 4; $i <= 13; $i++) {
            $total += (int) $values[$z] * $i;
            $z--;
        }

        return str_pad((string) ($total % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate and return the RIP of the account.
     */
    public function getRip(): string
    {
        $ccpInt = (int) $this->ccp;
        $remainder = ($ccpInt * 100) % 97;

        if ($remainder + 85 > 97) {
            $x = 97 - ($remainder + 85 - 97);
        } else {
            $x = 97 - ($remainder + 85);
        }

        return '00799999' . str_pad((string) $ccpInt, 10, '0', STR_PAD_LEFT) . (string) $x;
    }
}
