<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Wallet;
use App\Models\WalletTransfer;
use App\Services\WalletTransferService;
use Illuminate\Database\Seeder;

class WalletTransferSeeder extends Seeder
{
    public function run(): void
    {
        $mainBranch   = Branch::where('code', 'M')->first();
        $secondBranch = Branch::where('code', 'S')->first();

        if (! $mainBranch || ! $secondBranch) {
            return;
        }

        $fromWallet = Wallet::where('owner_type', Branch::class)
            ->where('owner_id', $mainBranch->id)
            ->first();

        $toWallet = Wallet::where('owner_type', Branch::class)
            ->where('owner_id', $secondBranch->id)
            ->first();

        if (! $fromWallet || ! $toWallet) {
            return;
        }

        if (WalletTransfer::where('from_wallet_id', $fromWallet->id)->exists()) {
            return;
        }

        // Only transfer if the source wallet has sufficient balance.
        if ((float) $fromWallet->balance <= 0) {
            return;
        }

        app(WalletTransferService::class)->create([
            'from_wallet_id' => $fromWallet->id,
            'to_wallet_id'   => $toWallet->id,
            'amount'         => 100000,
            'note'           => 'Seeded wallet transfer',
        ]);
    }
}
