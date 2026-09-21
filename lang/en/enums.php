<?php

declare(strict_types=1);

return [

    'inventory_movement_type' => [
        'RECEIVE'           => 'Receive',
        'RETURN'            => 'Return',
        'TRANSFER_IN'       => 'Transfer In',
        'TRANSFER_OUT'      => 'Transfer Out',
        'SALE'              => 'Sale',
        'EXPIRED'           => 'Expired',
        'RESTOCK_RECEIVED'  => 'Restock Received',
        'TRANSFER_CANCEL'   => 'Transfer Cancel',
        'SALE_RETURN'       => 'Sale Return',
        'SALE_UPDATE'       => 'Sale Update',
        'CONTRACT'          => 'Contract',
        'MANUAL'            => 'Manual',
    ],

    'wallet_movement_type' => [
        'DEPOSIT'             => 'Deposit',
        'WITHDRAWAL'          => 'Withdrawal',
        'TRANSFER_IN'         => 'Transfer In',
        'TRANSFER_OUT'        => 'Transfer Out',
        'EXPENSE'             => 'Expense',
        'SALARY'              => 'Salary',
        'INSTALLMENT_PAYMENT' => 'Installment Payment',
        'CONTRACT_PAYMENT'    => 'Contract Payment',
        'DRAW_PAYMENT'        => 'Draw Payment',
        'DRAW_TAX'            => 'Draw Tax',
        'PURCHASE_PAYMENT'    => 'Purchase Payment',
        'PURCHASE_RETURN'     => 'Purchase Return',
        'SALE_RETURN'         => 'Sale Return',
        'SALE_UPDATE'         => 'Sale Update',
        'SALE_PAYMENT'        => 'Sale Payment',
        'PAYMENT_CANCEL'      => 'Payment Cancel',
        'ADJUSTMENT'          => 'Adjustment',
    ],

    'sale_return_status' => [
        'PENDING'   => 'Pending',
        'COMPLETED' => 'Completed',
    ],

    'purchase_status' => [
        'pending'   => 'Pending',
        'completed' => 'Completed',
        'canceled'  => 'Canceled',
    ],

    'purchase_payment_method' => [
        'CASH' => 'Cash',
        'BANK' => 'Bank',
    ],

    'restock_status' => [
        'DRAFT'     => 'Draft',
        'SUBMITTED' => 'Submitted',
        'FULFILLED' => 'Fulfilled',
        'CANCELLED' => 'Cancelled',
    ],

    'contract_status' => [
        'DRAFT'     => 'Draft',
        'PENDING'   => 'Pending',
        'APPROVED'  => 'Approved',
        'REJECTED'  => 'Rejected',
        'CONFIRMED' => 'Confirmed',
        'ACTIVE'    => 'Active',
        'COMPLETED' => 'Completed',
        'CLOSED'    => 'Closed',
        'CANCELLED'  => 'Cancelled',
        'CONFIGURED' => 'Configured',
    ],

    'installment_status' => [
        'PENDING'        => 'Pending',
        'UNPAID'         => 'Unpaid',
        'PAID'           => 'Paid',
        'PARTIALLY_PAID' => 'Partially Paid',
    ],

    'installment_payment_method' => [
        'BANK' => 'Bank',
        'CASH' => 'Cash',
    ],

    'draw_status' => [
        'PAID_ON_TIME' => 'Paid On Time',
        'LATE_PAYMENT' => 'Late Payment',
        'POSTPONED'    => 'Postponed',
        'FAILED'       => 'Failed',
    ],

    'client_rating' => [
        'high'   => 'High',
        'medium' => 'Medium',
        'low'    => 'Low',
        'none'   => 'None',
    ],

    'price_type' => [
        'SELLING'     => 'Selling',
        'INSTALLMENT' => 'Installment',
        'WHOLESALE'   => 'Wholesale',
    ],

    'notification_type' => [
        'contract_created'            => 'Contract Created',
        'purchase_created'            => 'Purchase Created',
        'purchase_return_created'     => 'Purchase Return Created',
        'sale_return_created'         => 'Sale Return Created',
        'restock_created'             => 'Restock Created',
        'inventory_transfer_created'  => 'Inventory Transfer Created',
        'expiration_created'          => 'Expiration Created',
        'wallet_transfer_created'     => 'Wallet Transfer Created',
        'contract_approved'           => 'Contract Approved',
        'contract_rejected'           => 'Contract Rejected',
        'contract_configured'         => 'Contract Configured',
        'contract_cancelled'          => 'Contract Cancelled',
        'contract_extended'           => 'Contract Extended',
        'purchase_received'           => 'Purchase Received',
        'purchase_cancelled'          => 'Purchase Cancelled',
        'purchase_return_approved'    => 'Purchase Return Approved',
        'purchase_return_cancelled'   => 'Purchase Return Cancelled',
        'sale_return_approved'        => 'Sale Return Approved',
        'sale_return_cancelled'       => 'Sale Return Cancelled',
        'restock_fulfilled'           => 'Restock Fulfilled',
        'restock_cancelled'           => 'Restock Cancelled',
        'inventory_transfer_dispatched' => 'Inventory Transfer Dispatched',
        'inventory_transfer_received'   => 'Inventory Transfer Received',
        'inventory_transfer_cancelled'  => 'Inventory Transfer Cancelled',
        'expiration_approved'         => 'Expiration Approved',
        'purchase_payment_cancelled'  => 'Purchase Payment Cancelled',
    ],

];
