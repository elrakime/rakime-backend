<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('purchase_cost', 15, 2)->default(0)->after('total_amount');
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('purchase_cost');
        });
    }

    /**
     * Backfill purchase_cost for existing sales from their batch allocations.
     *
     * purchase_cost = Σ (quantity * purchase_price) across the sale's movement
     * allocations (SALE negative, SALE_UPDATE signed, SALE_RETURN positive).
     * Signed quantities net automatically, so returns reduce the cost.
     */
    private function backfill(): void
    {
        DB::table('sales')
            ->select('sales.id')
            ->orderBy('sales.id')
            ->chunkById(200, function ($sales) {
                foreach ($sales as $sale) {
                    $cost = DB::table('batch_allocations')
                        ->join('inventory_movements', 'inventory_movements.id', '=', 'batch_allocations.inventory_movement_id')
                        ->where('inventory_movements.source_type', \App\Models\Sale::class)
                        ->where('inventory_movements.source_id', $sale->id)
                        ->sum(DB::raw('batch_allocations.quantity * batch_allocations.purchase_price'));

                    DB::table('sales')
                        ->where('id', $sale->id)
                        ->update(['purchase_cost' => round((float) $cost, 2)]);
                }
            });
    }
};
