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
    ],

    'wallet_movement_type' => [
        'DEPOSIT'             => 'إيداع',
        'WITHDRAWAL'          => 'سحب',
        'TRANSFER_IN'         => 'تحويل وارد',
        'TRANSFER_OUT'        => 'تحويل صادر',
        'EXPENSE'             => 'مصروف',
        'SALARY'              => 'راتب',
        'INSTALLMENT_PAYMENT' => 'دفع قسط',
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
        'PENDING' => 'قيد الانتظار',
        'PAID'    => 'مدفوع',
        'OVERDUE' => 'متأخر',
    ],

    'installment_payment_method' => [
        'BANK' => 'بنك',
        'CASH' => 'نقداً',
    ],

    'subscription_status' => [
        'ACTIVE'    => 'نشط',
        'CANCELLED' => 'ملغى',
        'COMPLETED' => 'مكتمل',
    ],

    'draw_status' => [
        'PENDING'   => 'قيد الانتظار',
        'RECEIVED'  => 'مستلم',
        'CANCELLED' => 'ملغى',
        'FAILED'    => 'فاشل',
    ],

    'price_type' => [
        'SELLING'     => 'البيع',
        'INSTALLMENT' => 'التقسيط',
        'WHOLESALE'   => 'الجملة',
    ],

];
