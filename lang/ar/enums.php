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
        'CONTRACT'         => 'عقد',
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
        'PENDING'        => 'قيد الانتظار',
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

    'client_rating' => [
        'high'   => 'مرتفع',
        'medium' => 'متوسط',
        'low'    => 'منخفض',
        'none'   => 'لا يوجد',
    ],

    'price_type' => [
        'SELLING'     => 'البيع',
        'INSTALLMENT' => 'التقسيط',
        'WHOLESALE'   => 'الجملة',
    ],

    'notification_type' => [
        'contract_created'            => 'إنشاء عقد',
        'purchase_created'            => 'إنشاء عملية شراء',
        'purchase_return_created'     => 'إنشاء إرجاع شراء',
        'sale_return_created'         => 'إنشاء إرجاع مبيعات',
        'restock_created'             => 'إنشاء إعادة تخزين',
        'inventory_transfer_created'  => 'إنشاء تحويل مخزون',
        'expiration_created'          => 'إنشاء انتهاء صلاحية',
        'wallet_transfer_created'     => 'إنشاء تحويل محفظة',
        'contract_approved'           => 'الموافقة على العقد',
        'contract_rejected'           => 'رفض العقد',
        'contract_configured'         => 'إعداد العقد',
        'contract_cancelled'          => 'إلغاء العقد',
        'contract_extended'           => 'تمديد العقد',
        'purchase_received'           => 'استلام عملية الشراء',
        'purchase_cancelled'          => 'إلغاء عملية الشراء',
        'purchase_return_approved'    => 'الموافقة على إرجاع الشراء',
        'purchase_return_cancelled'   => 'إلغاء إرجاع الشراء',
        'sale_return_approved'        => 'الموافقة على إرجاع المبيعات',
        'sale_return_cancelled'       => 'إلغاء إرجاع المبيعات',
        'restock_fulfilled'           => 'تنفيذ إعادة التخزين',
        'restock_cancelled'           => 'إلغاء إعادة التخزين',
        'inventory_transfer_dispatched' => 'إرسال تحويل المخزون',
        'inventory_transfer_received'   => 'استلام تحويل المخزون',
        'inventory_transfer_cancelled'  => 'إلغاء تحويل المخزون',
        'expiration_approved'         => 'الموافقة على انتهاء الصلاحية',
        'purchase_payment_cancelled'  => 'إلغاء دفعة شراء',
    ],

];
