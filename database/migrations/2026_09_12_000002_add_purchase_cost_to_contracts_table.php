<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->decimal('purchase_cost', 15, 2)->default(0)->after('monthly_amount');
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('purchase_cost');
        });
    }

    /**
     * Backfill purchase_cost for existing contracts from their batch allocations.
     *
     * purchase_cost = Σ (quantity * purchase_price) across the contract's
     * CONTRACT movement allocations (negative outflow quantities).
     */
    private function backfill(): void
    {
        DB::table('contracts')
            ->select('contracts.id')
            ->orderBy('contracts.id')
            ->chunkById(200, function ($contracts) {
                foreach ($contracts as $contract) {
                    $cost = DB::table('batch_allocations')
                        ->join('inventory_movements', 'inventory_movements.id', '=', 'batch_allocations.inventory_movement_id')
                        ->where('inventory_movements.source_type', \App\Models\Contract::class)
                        ->where('inventory_movements.source_id', $contract->id)
                        ->sum(DB::raw('batch_allocations.quantity * batch_allocations.purchase_price'));

                    DB::table('contracts')
                        ->where('id', $contract->id)
                        ->update(['purchase_cost' => round((float) $cost, 2)]);
                }
            });
    }
};
