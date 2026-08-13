# Batch Creation & Quantity Changes — Full Audit

## Data Model

- **Table:** `batches`
- **Migration:** `database/migrations/2024_01_01_000014_create_batches_table.php`
- **Model:** `app/Models/Batch.php`

Columns: `id`, `stock_id`, `source_id`, `source_type`, `purchase_price`, `initial_quantity`, `current_quantity`, `created_by`, `updated_by`, timestamps.

- `source` is a polymorphic relation (MorphTo).
- `Batch` uses the `HasUserstamps` trait for automatic `created_by` / `updated_by` filling.
- `Stock::batches()` defines `hasMany(Batch::class)`.

---

## Batch Creation (6 cases + seeder)

| # | File | Business Event | Trigger |
|---|---|---|---|
| 1 | `app/Services/PurchaseService.php` (L154-160) | Purchase order received — one batch per purchase item | `POST /purchases/{id}/receive` |
| 2 | `app/Services/StockService.php` (L68-75) | Stock entry created manually with an initial quantity | `POST /stocks` |
| 3 | `app/Services/InventoryTransferService.php` (L154-160) | Transfer received at destination inventory | `POST /inventory-transfers/{id}/receive` |
| 4 | `app/Services/InventoryTransferService.php` (L198-204) | Dispatched transfer canceled (restores stock at source) | `POST /inventory-transfers/{id}/cancel` |
| 5 | `app/Services/BatchService.php` (L38-47) | Direct/manual batch creation | `POST /stocks/{stock}/batches` |
| 6 | `database/seeders/PurchaseSeeder.php` (L56-67, 100-111) | Database seeding (dev/test only) | `php artisan db:seed` |

### Details

#### 1. PurchaseService::receive() — PRIMARY PATH

When a purchase order is "received" (status changes from PENDING to RECEIVED), the system finds or creates a `Stock` record for each product in the target inventory, then creates a new batch for each purchase item. The batch's `source` points to the `PurchaseItem`.

#### 2. StockService::create()

When creating a stock entry manually, an optional initial batch can be created if `initial_quantity` is provided.

#### 3. InventoryTransferService::receive()

When a dispatched inventory transfer is received at the destination inventory, a new batch is created at the destination stock for each transferred item. `purchase_price` is always `0`.

#### 4. InventoryTransferService::cancel()

When a dispatched transfer is canceled (not received), the stock that was deducted during dispatch is restored by creating a new batch at the source inventory (a reversal/credit batch).

#### 5. BatchService::create()

A direct, standalone batch creation endpoint allowing manual batch additions.

#### 6. PurchaseSeeder

Creates sample batches directly via `Batch::firstOrCreate()` for development/testing.

---

## Batch Quantity Decrements (5 cases)

| # | File | Business Event | Method |
|---|---|---|---|
| A1 | `app/Services/SaleService.php` (L340) | Sale created — FIFO deduction across batches | `decrement()` |
| A2 | `app/Services/SaleService.php` (L262) | Sale updated (items added/increased) — FIFO deduction of delta | `decrement()` |
| A3 | `app/Services/ExpirationService.php` (L112) | Expiration approved — FIFO deduction of expired qty | `decrement()` |
| A4 | `app/Services/PurchaseReturnService.php` (L125) | Purchase return approved — deducts from the specific source batch | `decrement()` |
| A5 | `app/Services/InventoryTransferService.php` (L116) | Transfer dispatched — deducts from first available batch at source | `decrement()` |

---

## Batch Quantity Increments (2 cases)

| # | File | Business Event | Method |
|---|---|---|---|
| B1 | `app/Services/SaleService.php` (L277) | Sale updated (items reduced/removed) — delta returned to first batch | `increment()` |
| B2 | `app/Services/SaleReturnService.php` (L131) | Sale return approved — returned qty added to first batch | `increment()` |

---

## Manual Override (1 case)

| # | File | Business Event | Trigger |
|---|---|---|---|
| C1 | `app/Services/BatchService.php` (L54-62) | Direct update of any batch field (source, price, quantities) | `PUT /stocks/{stock}/batches/{batch}` |

---

## Inventory Movement Tracking

All quantity modifications (except the manual `BatchService::update()`) are accompanied by an `InventoryMovement` record via `app/Services/InventoryService.php`.

Movement types used: `RECEIVE`, `RETURN`, `TRANSFER_OUT`, `TRANSFER_IN`, `SALE`, `EXPIRED`, `RESTOCK_RECEIVED`, `TRANSFER_CANCEL`, `SALE_RETURN`, `SALE_UPDATE`.

---

## Key Observations

1. **FIFO is used for deductions** (sales, expirations, purchase returns) — batches are ordered by `created_at`, oldest consumed first.
2. **Returns/increments always go to the first batch** — no effort to return stock to its original batch.
3. **Transfers create new batches at destination** (`purchase_price = 0`) and **canceled transfers create credit batches** at source.
4. **All quantity changes (except manual `BatchService::update()`) are accompanied by `InventoryMovement` records.**
5. **Potential inconsistency** in `PurchaseReturnService.php` (L125): `source_type` is matched as `'purchase_items'` (snake_case) while elsewhere it is stored as `PurchaseItem::class` (FQCN).

---

## Summary Table

| # | File:Line(s) | Operation | Business Event | Trigger |
|---|---|---|---|---|
| **Batch Creation** | | | | |
| 1a | `PurchaseService.php:154-160` | `batches()->create()` | Purchase received | `POST /purchases/{id}/receive` |
| 2 | `StockService.php:68-75` | `batches()->create()` | Stock created with quantity | `POST /stocks` |
| 3 | `InventoryTransferService.php:154-160` | `batches()->create()` | Transfer received at destination | `POST /inventory-transfers/{id}/receive` |
| 4 | `InventoryTransferService.php:198-204` | `batches()->create()` | Dispatched transfer canceled | `POST /inventory-transfers/{id}/cancel` |
| 6 | `BatchService.php:38-47` | `batches()->create()` | Manual batch creation | `POST /stocks/{stock}/batches` |
| 7 | `PurchaseSeeder.php:56,100` | `Batch::firstOrCreate()` | Database seeding | `php artisan db:seed` |
| **Batch Quantity Modifications** | | | | |
| A1 | `SaleService.php:340` | `decrement()` | Sale created (deduct stock) | `POST /sales` |
| A2 | `SaleService.php:262` | `decrement()` | Sale updated (added/increased items) | `PUT /sales/{id}` |
| B1 | `SaleService.php:277` | `increment()` | Sale updated (reduced/removed items) | `PUT /sales/{id}` |
| A3 | `ExpirationService.php:112` | `decrement()` | Expiration approved | `POST /expirations/{id}/approve` |
| A4 | `PurchaseReturnService.php:125` | `decrement()` | Purchase return approved | `POST /purchases/{p}/returns/{r}/approve` |
| A5 | `InventoryTransferService.php:116` | `decrement()` | Transfer dispatched | `POST /inventory-transfers/{id}/dispatch` |
| B2 | `SaleReturnService.php:131` | `increment()` | Sale return approved | `POST /sales/{s}/returns/{r}/approve` |
| C1 | `BatchService.php:56` | `update()` | Manual batch update | `PUT /stocks/{stock}/batches/{batch}` |
