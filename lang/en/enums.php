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
        'PENDING' => 'Pending',
        'PAID'    => 'Paid',
        'OVERDUE' => 'Overdue',
    ],

    'installment_payment_method' => [
        'BANK' => 'Bank',
        'CASH' => 'Cash',
    ],

    'subscription_status' => [
        'ACTIVE'    => 'Active',
        'CANCELLED' => 'Cancelled',
        'COMPLETED' => 'Completed',
    ],

    'draw_status' => [
        'PENDING'   => 'Pending',
        'RECEIVED'  => 'Received',
        'CANCELLED' => 'Cancelled',
        'FAILED'    => 'Failed',
    ],

    'price_type' => [
        'SELLING'     => 'Selling',
        'INSTALLMENT' => 'Installment',
        'WHOLESALE'   => 'Wholesale',
    ],

];
