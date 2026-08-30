<?php

declare(strict_types=1);

return [

    'inventory_movement_type' => [
        'RECEIVE'          => 'استقبال',
        'RETURN'           => 'إرجاع',
        'TRANSFER_IN'      => 'تحويل وارد',
        'TRANSFER_OUT'     => 'تحويل صادر',
        'SALE'             => 'بيع',
        'EXPIRED'          => 'منتهي الصلاحية',
        'RESTOCK_RECEIVED' => 'إعادة تخزين مستلمة',
        'TRANSFER_CANCEL'  => 'إلغاء التحويل',
        'SALE_RETURN'      => 'إرجاع مبيعات',
        'SALE_UPDATE'      => 'تحديث مبيعات',
        'MANUAL'           => 'يدوي',
    ],

    'wallet_movement_type' => [
        'DEPOSIT'             => 'إيداع',
        'WITHDRAWAL'          => 'سحب',
        'TRANSFER_IN'         => 'تحويل وارد',
        'TRANSFER_OUT'        => 'تحويل صادر',
        'EXPENSE'             => 'مصروف',
        'SALARY'              => 'راتب',
        'INSTALLMENT_PAYMENT' => 'دفع قسط',
        'CONTRACT_PAYMENT'    => 'دفع عقد',
        'DRAW_PAYMENT'        => 'دفع سحب',
        'DRAW_TAX'            => 'ضريبة سحب',
        'PURCHASE_PAYMENT'    => 'دفع مشتريات',
        'PURCHASE_RETURN'     => 'إرجاع مشتريات',
        'SALE_RETURN'         => 'إرجاع مبيعات',
        'SALE_UPDATE'         => 'تحديث مبيعات',
        'SALE_PAYMENT'        => 'دفع مبيعات',
        'PAYMENT_CANCEL'      => 'إلغاء الدفع',
        'ADJUSTMENT'          => 'تسوية',
    ],

    'sale_return_status' => [
        'PENDING'   => 'قيد الانتظار',
        'COMPLETED' => 'مكتمل',
    ],

    'purchase_status' => [
        'pending'   => 'قيد الانتظار',
        'completed' => 'مكتمل',
        'canceled'  => 'ملغي',
    ],

    'purchase_payment_method' => [
        'CASH' => 'نقداً',
        'BANK' => 'بنك',
    ],

    'restock_status' => [
        'DRAFT'     => 'مسودة',
        'SUBMITTED' => 'مقدمة',
        'FULFILLED' => 'منجزة',
        'CANCELLED' => 'ملغاة',
    ],

    'contract_status' => [
        'DRAFT'     => 'مسودة',
        'PENDING'   => 'قيد الانتظار',
        'APPROVED'  => 'مقبول',
        'REJECTED'  => 'مرفوض',
        'CONFIRMED' => 'مؤكد',
        'ACTIVE'    => 'نشط',
        'COMPLETED' => 'مكتمل',
        'CLOSED'    => 'مغلق',
        'CANCELLED'  => 'ملغى',
        'CONFIGURED' => 'مُهيأ',
    ],

    'installment_status' => [
        'UNPAID'         => 'غير مدفوع',
        'PAID'           => 'مدفوع',
        'PARTIALLY_PAID' => 'مدفوع جزئياً',
    ],

    'installment_payment_method' => [
        'BANK' => 'بنك',
        'CASH' => 'نقداً',
    ],

    'draw_status' => [
        'PAID_ON_TIME' => 'مدفوع في الوقت',
        'LATE_PAYMENT' => 'دفع متأخر',
        'POSTPONED'    => 'مؤجل',
        'FAILED'       => 'فاشل',
    ],

    'price_type' => [
        'SELLING'     => 'البيع',
        'INSTALLMENT' => 'التقسيط',
        'WHOLESALE'   => 'الجملة',
    ],

];
