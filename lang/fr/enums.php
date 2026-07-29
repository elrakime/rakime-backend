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
        'TRANSFER_CANCEL'  => 'Transfert annulé',
        'SALE_RETURN'      => 'Retour de vente',
        'SALE_UPDATE'      => 'Mise à jour vente',
    ],

    'wallet_movement_type' => [
        'DEPOSIT'             => 'Dépôt',
        'WITHDRAWAL'          => 'Retrait',
        'TRANSFER_IN'         => 'Transfert entrant',
        'TRANSFER_OUT'        => 'Transfert sortant',
        'EXPENSE'             => 'Dépense',
        'SALARY'              => 'Salaire',
        'INSTALLMENT_PAYMENT' => 'Paiement par échéance',
        'PURCHASE_PAYMENT'    => "Paiement d'achat",
        'PURCHASE_RETURN'     => "Retour d'achat",
        'SALE_RETURN'         => 'Retour de vente',
        'SALE_UPDATE'         => 'Mise à jour vente',
        'SALE_PAYMENT'        => 'Paiement de vente',
        'PAYMENT_CANCEL'      => 'Paiement annulé',
        'ADJUSTMENT'          => 'Ajustement',
    ],

    'sale_return_status' => [
        'PENDING'   => 'En attente',
        'COMPLETED' => 'Terminé',
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

    'restock_status' => [
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
        'SELLING'     => 'Vente',
        'INSTALLMENT' => 'Tranche',
        'WHOLESALE'   => 'Gros',
    ],

];
