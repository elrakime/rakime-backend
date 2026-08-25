<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reshape the installment/draw schema for bank-return import integration:
     *
     * - draws: drop month_number, rename scheduled_date -> due_date, add
     *   last_attempted_at, tax_amount and metadata; make status nullable with
     *   no default (draws are only created by import with an explicit status).
     * - installments: drop month_number; swap status enum to unpaid/paid/partially_paid.
     * - contracts: drop draw_date (first installment due_date is the anchor).
     *
     * NOTE: MySQL DDL is non-transactional, so each step is guarded with
     * hasColumn() checks to tolerate a partially-applied run.
     */
    public function up(): void
    {
        // --- draws ---
        if (Schema::hasColumn('draws', 'month_number')) {
            Schema::table('draws', function (Blueprint $table) {
                $table->dropColumn('month_number');
            });
        }

        if (Schema::hasColumn('draws', 'scheduled_date')) {
            Schema::table('draws', function (Blueprint $table) {
                $table->renameColumn('scheduled_date', 'due_date');
            });
        }

        if (! Schema::hasColumn('draws', 'last_attempted_at')) {
            Schema::table('draws', function (Blueprint $table) {
                $table->date('last_attempted_at')->nullable()->after('due_date');
            });
        }

        if (! Schema::hasColumn('draws', 'tax_amount')) {
            Schema::table('draws', function (Blueprint $table) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('last_attempted_at');
            });
        }

        if (! Schema::hasColumn('draws', 'metadata')) {
            Schema::table('draws', function (Blueprint $table) {
                $table->json('metadata')->nullable()->after('tax_amount');
            });
        }

        // Backfill legacy draw statuses, then swap the enum + make nullable.
        // Step 1: allow NULL on the legacy enum so we can clear obsolete rows.
        DB::statement(
            "ALTER TABLE draws MODIFY COLUMN status ENUM('pending','received','cancelled','failed') NULL DEFAULT NULL"
        );

        // Step 2: legacy draws were eagerly created and are obsolete; clear them.
        DB::statement("UPDATE draws SET status = NULL");

        // Step 3: swap to the new enum set (nullable, no default).
        DB::statement(
            "ALTER TABLE draws MODIFY COLUMN status ENUM('paid_on_time','late_payment','postponed','failed') NULL DEFAULT NULL"
        );

        // --- installments ---
        if (Schema::hasColumn('installments', 'month_number')) {
            Schema::table('installments', function (Blueprint $table) {
                $table->dropColumn('month_number');
            });
        }

        // Step 1: extend the legacy enum temporarily so old values survive.
        DB::statement(
            "ALTER TABLE installments MODIFY COLUMN status ENUM('pending','paid','overdue','unpaid','partially_paid') NULL DEFAULT NULL"
        );

        // Step 2: map legacy statuses to the new set.
        DB::statement(
            "UPDATE installments SET status = CASE WHEN status = 'paid' THEN 'paid' ELSE 'unpaid' END"
        );

        // Step 3: swap to the new enum set (NOT NULL, default unpaid).
        DB::statement(
            "ALTER TABLE installments MODIFY COLUMN status ENUM('unpaid','paid','partially_paid') NOT NULL DEFAULT 'unpaid'"
        );

        // --- contracts ---
        if (Schema::hasColumn('contracts', 'draw_date')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->dropColumn('draw_date');
            });
        }
    }

    public function down(): void
    {
        // --- contracts ---
        if (! Schema::hasColumn('contracts', 'draw_date')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->date('draw_date')->nullable()->after('months_count');
            });
        }

        // --- installments ---
        DB::statement(
            "ALTER TABLE installments MODIFY COLUMN status ENUM('pending','paid','overdue') NOT NULL DEFAULT 'pending'"
        );

        if (! Schema::hasColumn('installments', 'month_number')) {
            Schema::table('installments', function (Blueprint $table) {
                $table->unsignedSmallInteger('month_number')->after('contract_id');
            });
        }

        // --- draws ---
        DB::statement(
            "ALTER TABLE draws MODIFY COLUMN status ENUM('pending','received','cancelled','failed') NOT NULL DEFAULT 'pending'"
        );

        foreach (['metadata', 'tax_amount', 'last_attempted_at'] as $column) {
            if (Schema::hasColumn('draws', $column)) {
                Schema::table('draws', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        if (Schema::hasColumn('draws', 'due_date')) {
            Schema::table('draws', function (Blueprint $table) {
                $table->renameColumn('due_date', 'scheduled_date');
            });
        }

        if (! Schema::hasColumn('draws', 'month_number')) {
            Schema::table('draws', function (Blueprint $table) {
                $table->unsignedSmallInteger('month_number')->after('installment_id');
            });
        }
    }
};
