<?php

declare(strict_types=1);

return [

    'inventory_movement_type' => [
        'RECEIVE'          => 'Réception',
        'RETURN'           => 'Retour',
        'TRANSFER_IN'      => 'Transfert entrant',
        'TRANSFER_OUT'     => 'Transfert sortant',
        'SALE'             => 'Vente',
        'EXPIRED'          => 'Expiré',
        'RESTOCK_RECEIVED' => 'Réapprovisionnement reçu',
    ],

    'treasury_movement_type' => [
        'DEPOSIT'             => 'Dépôt',
        'WITHDRAWAL'          => 'Retrait',
        'TRANSFER_IN'         => 'Transfert entrant',
        'TRANSFER_OUT'        => 'Transfert sortant',
        'INSTALLMENT_PAYMENT' => 'Paiement par échéance',
        'PURCHASE_PAYMENT'    => "Paiement d'achat",
        'SALE_PAYMENT'        => 'Paiement de vente',
        'ADJUSTMENT'          => 'Ajustement',
    ],

    'purchase_status' => [
        'DRAFT'          => 'Brouillon',
        'RECEIVED'       => 'Reçu',
        'PAID'           => 'Payé',
        'PARTIALLY_PAID' => 'Partiellement payé',
    ],

    'purchase_payment_method' => [
        'CASH' => 'Espèces',
        'BANK' => 'Banque',
    ],

    'restock_order_status' => [
        'DRAFT'     => 'Brouillon',
        'SUBMITTED' => 'Soumis',
        'FULFILLED' => 'Traité',
        'CANCELLED' => 'Annulé',
    ],

    'contract_status' => [
        'DRAFT'     => 'Brouillon',
        'PENDING'   => 'En attente',
        'APPROVED'  => 'Approuvé',
        'REJECTED'  => 'Rejeté',
        'CONFIRMED' => 'Confirmé',
        'ACTIVE'    => 'Actif',
        'COMPLETED' => 'Terminé',
        'CLOSED'    => 'Clôturé',
        'CANCELLED' => 'Annulé',
    ],

    'installment_status' => [
        'PENDING' => 'En attente',
        'PAID'    => 'Payé',
        'OVERDUE' => 'En retard',
    ],

    'installment_payment_method' => [
        'BANK' => 'Banque',
        'CASH' => 'Espèces',
    ],

    'subscription_status' => [
        'ACTIVE'    => 'Actif',
        'CANCELLED' => 'Annulé',
        'COMPLETED' => 'Terminé',
    ],

    'draw_status' => [
        'PENDING'   => 'En attente',
        'RECEIVED'  => 'Reçu',
        'CANCELLED' => 'Annulé',
        'FAILED'    => 'Échoué',
    ],

    'price_type' => [
        'selling'     => 'Vente',
        'installment' => 'Tranche',
        'wholesale'   => 'Gros',
    ],

];
