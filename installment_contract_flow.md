# Installment Contract — Intended Flow

## 1. Contract Creation (DRAFT)

An `installment_contracts` row is created with `status = PENDING`. At this point:

- `client_id`, `account_id`, `branch_id` are set — who's buying, which bank account will handle draws, which branch is processing it
- `note` may be added for internal context
- `reference`, `max_amount`, `advance_amount`, `months_count`, `total_amount`, `monthly_amount` are all still `null` — not known yet
- No items are attached yet — items are added in step 3

## 2. Admin Approval

An admin reviews the draft and sets `max_amount` — a credit ceiling this client is approved for. The contract status moves to `APPROVED`.

## 3. Employee Fills In Terms & Adds Items

After approval, an employee sets:

- `advance_amount` — a down payment, deducted from the total
- `months_count` — how many months to split the remaining balance over
- Items are added via `installment_contract_items` — product, stock, quantity, price. Pricing snapshot columns exist on the item already, but are only meaningful once confirmed.

## 4. Confirmation

At confirmation:

- `reference` is generated (contract becomes trackable/bank-facing)
- `total_amount` is snapshotted as the sum of `installment_price × qty` across items
- Each item's `unit_price_snapshot` and `installment_price_snapshot` are locked in from current product prices
- `monthly_amount` is computed — schema note says `total_amount / months_count`, but should likely be `(total_amount - advance_amount) / months_count` (⚠️ verify business logic)
- Contract status becomes `CONFIRMED` (or similar)

## 5. Installment Schedule Generation

Once confirmed, `installments` rows are generated — one per month (`month_number` 1 to `months_count`), each carrying:

- `monthly_amount`
- a `due_date` (tied to the account's `draw_day`)
- initial status `PENDING`

## 6. Payment Method Branches Into Two Paths

### Path A — Bank Draw (subscription-based)

- One or more `installment_subscriptions` are created per contract (`subscription_number`, e.g. 1–4 — possibly splitting the monthly amount across multiple bank subscriptions if it exceeds `account.max_draw_amount`)
- Each subscription has a stable `reference` for the bank, and its own per-draw `amount`
- For each month, an `installment_draws` row ties a subscription to a specific `installment`, scheduled on the account's `draw_day`
- Draws move through `draw_status` (PENDING → presumably SUCCESS/FAILED)

### Path B — Cash Payment

- If a client pays a given month's installment in cash instead, an `installment_cash_payments` row is created, tied 1:1 to that `installment` (enforced by a `unique` constraint)
- `received_by` tracks the employee who took the cash
- Presumably bypasses/cancels any pending draw for that installment

## 7. Ongoing Lifecycle

Each `installments` row updates its `status` (PENDING → PAID, likely also OVERDUE/FAILED) as either a draw succeeds or a cash payment is recorded — whichever happens first for that month.

---

## Open Questions / Things to Confirm

- **Monthly amount formula** — does it subtract `advance_amount` before dividing, or is the advance handled separately outside the installment schedule entirely?
- **Draw failure handling** — is there a retry, a switch to cash, or a penalty path? Not modeled yet.
- **Multiple subscriptions per contract** — for splitting large monthly amounts across accounts/cards, or for something else (e.g. co-signers)?
