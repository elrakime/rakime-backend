<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename the `payments` table to `contract_payments` and drop the
     * `received_by` column (and its FK). Also repoint dependent FKs
     * (installment_payments.payment_id, contract_early_cancelations.payment_id)
     * to the renamed table.
     *
     * This is a corrective migration for the already-applied dev schema. On a
     * fresh install, the original migrations already produce the final shape
     * (contract_payments without received_by), so the steps here are guarded.
     */
    public function up(): void
    {
        // 1. Rename the table if the old name still exists.
        if (Schema::hasTable('payments') && ! Schema::hasTable('contract_payments')) {
            Schema::rename('payments', 'contract_payments');
        }

        // 2. Drop received_by FK + column if still present.
        if (Schema::hasColumn('contract_payments', 'received_by')) {
            // The FK was created before the rename, so it keeps the old name.
            if ($this->hasForeignKey('payments_received_by_foreign')) {
                DB::statement('ALTER TABLE `contract_payments` DROP FOREIGN KEY `payments_received_by_foreign`');
            }

            Schema::table('contract_payments', function (Blueprint $table) {
                $table->dropColumn('received_by');
            });
        }

        // 3. Repoint dependent FKs to contract_payments (only if they point at the old table).
        $this->repointForeignKey('installment_payments', 'installment_payments_payment_id_foreign', 'payments', 'contract_payments');
        $this->repointForeignKey('contract_early_cancelations', 'contract_early_cancelations_payment_id_foreign', 'payments', 'contract_payments');
    }

    public function down(): void
    {
        // Reverse the FK repointing.
        $this->repointForeignKey('installment_payments', 'installment_payments_payment_id_foreign', 'contract_payments', 'payments');
        $this->repointForeignKey('contract_early_cancelations', 'contract_early_cancelations_payment_id_foreign', 'contract_payments', 'payments');

        // Restore received_by.
        if (Schema::hasTable('contract_payments') && ! Schema::hasColumn('contract_payments', 'received_by')) {
            Schema::table('contract_payments', function (Blueprint $table) {
                $table->foreignId('received_by')->nullable()->constrained('users');
            });
        }

        // Rename back.
        if (Schema::hasTable('contract_payments') && ! Schema::hasTable('payments')) {
            Schema::rename('contract_payments', 'payments');
        }
    }

    private function hasForeignKey(string $name): bool
    {
        $key = DB::selectOne(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_NAME = ? LIMIT 1",
            [$name],
        );

        return $key !== null;
    }

    private function repointForeignKey(string $table, string $fkName, string $from, string $to): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $row = DB::selectOne(
            "SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? LIMIT 1",
            [$table, $fkName],
        );

        if ($row === null || $row->REFERENCED_TABLE_NAME !== $from) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fkName}`");
        DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$fkName}` FOREIGN KEY (`payment_id`) REFERENCES `{$to}` (`id`) ON DELETE CASCADE");
    }
};
