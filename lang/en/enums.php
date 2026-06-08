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
    ],

    'treasury_movement_type' => [
        'DEPOSIT'             => 'Deposit',
        'WITHDRAWAL'          => 'Withdrawal',
        'TRANSFER_IN'         => 'Transfer In',
        'TRANSFER_OUT'        => 'Transfer Out',
        'INSTALLMENT_PAYMENT' => 'Installment Payment',
        'PURCHASE_PAYMENT'    => 'Purchase Payment',
        'SALE_PAYMENT'        => 'Sale Payment',
        'ADJUSTMENT'          => 'Adjustment',
    ],

    'purchase_status' => [
        'DRAFT'         => 'Draft',
        'RECEIVED'      => 'Received',
        'PAID'          => 'Paid',
        'PARTIALLY_PAID' => 'Partially Paid',
    ],

    'purchase_payment_method' => [
        'CASH' => 'Cash',
        'BANK' => 'Bank',
    ],

    'restock_order_status' => [
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
        'CANCELLED' => 'Cancelled',
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
        'cash'        => 'Cash',
        'installment' => 'Installment',
        'wholesale'   => 'Wholesale',
    ],

];
