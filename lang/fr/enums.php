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
        'MANUAL'           => 'Manuel',
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
        'pending'   => 'En attente',
        'completed' => 'Terminé',
        'canceled'  => 'Annulé',
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
        'CANCELLED'  => 'Annulé',
        'CONFIGURED' => 'Configuré',
    ],

    'installment_status' => [
        'UNPAID'         => 'Non payé',
        'PAID'           => 'Payé',
        'PARTIALLY_PAID' => 'Partiellement payé',
    ],

    'installment_payment_method' => [
        'BANK' => 'Banque',
        'CASH' => 'Espèces',
    ],

    'draw_status' => [
        'PAID_ON_TIME' => 'Payé à temps',
        'LATE_PAYMENT' => 'Paiement tardif',
        'POSTPONED'    => 'Reporté',
        'FAILED'       => 'Échoué',
    ],

    'price_type' => [
        'SELLING'     => 'Vente',
        'INSTALLMENT' => 'Tranche',
        'WHOLESALE'   => 'Gros',
    ],

];
