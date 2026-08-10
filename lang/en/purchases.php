<?php

return [
    'not_pending' => 'A purchase can only be modified if it is in pending status.',
    'must_be_completed' => 'The purchase must be completed before payment processing can begin.',
    'amount_exceeds_remaining' => 'The payment amount exceeds the remaining balance due.',
    'no_items' => 'At least one item must be added to the purchase.',
    'invalid_supplier' => 'The specified supplier is unavailable or no longer active.',
    'payment_already_canceled' => 'This payment has already been canceled.',
    'payment_canceled_note' => 'Payment of :amount canceled. Funds returned to wallet.',
    'inventory_branch_mismatch' => 'The selected inventory does not belong to this purchase\'s branch.',
    'missing_branch' => 'No branch assigned to this purchase. Please provide an inventory_id.',
    'no_branch_inventories' => 'No inventory found for this purchase\'s branch.',
    'multiple_branch_inventories' => 'This branch has multiple inventories. Please specify which inventory to use.',
    'selling_price_below_purchase' => 'Selling price must be greater than the purchase price (:price).',
    'selling_price_below_old_batches' => 'Selling price must be greater than the maximum purchase price of existing stock batches (:price).',
    'installment_price_below_purchase' => 'Installment price must be greater than the purchase price (:price).',
    'installment_price_below_old_batches' => 'Installment price must be greater than the maximum purchase price of existing stock batches (:price).',
];
