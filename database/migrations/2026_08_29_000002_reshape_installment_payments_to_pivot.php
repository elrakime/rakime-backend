<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reshape installment_payments into a payment<->installment pivot.
     *
     * Before: installment_id (unique) + amount + received_by + note.
     * After:  payment_id + installment_id (unique together).
     *
     * The amount/received_by/note move up to the new `payments` table.
     * A single payment may settle many installments, but an installment is
     * settled by exactly one payment.
     *
     * NOTE: MySQL DDL is non-transactional, so each step is guarded with
     * hasColumn()/FK checks to tolerate a partially-applied run.
     */
    public function up(): void
    {
        // Drop the FK + its dependent unique index on installment_id (if still present).
        if ($this->hasForeignKey('installment_payments_installment_id_foreign')) {
            Schema::table('installment_payments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('installment_id');
            });
        }

        // Drop the FK on received_by (if still present) before dropping the column.
        if ($this->hasForeignKey('installment_payments_received_by_foreign')) {
            Schema::table('installment_payments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('received_by');
            });
        }

        Schema::table('installment_payments', function (Blueprint $table) {
            if (Schema::hasColumn('installment_payments', 'amount')) {
                $table->dropColumn('amount');
            }

            if (Schema::hasColumn('installment_payments', 'received_by')) {
                $table->dropColumn('received_by');
            }

            if (Schema::hasColumn('installment_payments', 'note')) {
                $table->dropColumn('note');
            }
        });

        // Re-add installment_id (non-unique) if it was dropped, and add payment_id.
        Schema::table('installment_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('installment_payments', 'installment_id')) {
                $table->foreignId('installment_id')->after('id')->constrained('installments')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('installment_payments', 'payment_id')) {
                $table->foreignId('payment_id')->after('id')->constrained('contract_payments')->cascadeOnDelete();
            }
        });

        // Composite unique (payment_id, installment_id).
        Schema::table('installment_payments', function (Blueprint $table) {
            $table->unique(['payment_id', 'installment_id']);
        });
    }

    public function down(): void
    {
        Schema::table('installment_payments', function (Blueprint $table) {
            $table->dropUnique(['payment_id', 'installment_id']);
            $table->dropConstrainedForeignId('payment_id');
            $table->dropConstrainedForeignId('installment_id');

            $table->unsignedInteger('amount')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->string('note')->nullable();
        });

        Schema::table('installment_payments', function (Blueprint $table) {
            $table->unique(['installment_id']);
        });
    }

    private function hasForeignKey(string $name): bool
    {
        $key = DB::selectOne(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'installment_payments'
               AND CONSTRAINT_NAME = ? LIMIT 1",
            [$name],
        );

        return $key !== null;
    }
};
