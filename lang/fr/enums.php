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
        'CONTRACT'         => 'Contrat',
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
        'CONTRACT_PAYMENT'    => 'Paiement de contrat',
        'DRAW_PAYMENT'        => 'Paiement de prélèvement',
        'DRAW_TAX'            => 'Taxe de prélèvement',
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
        'PENDING'        => 'En attente',
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

    'client_rating' => [
        'high'   => 'Élevé',
        'medium' => 'Moyen',
        'low'    => 'Faible',
        'none'   => 'Aucun',
    ],

    'price_type' => [
        'SELLING'     => 'Vente',
        'INSTALLMENT' => 'Tranche',
        'WHOLESALE'   => 'Gros',
    ],

    'notification_type' => [
        'contract_created'            => 'Contrat créé',
        'purchase_created'            => 'Achat créé',
        'purchase_return_created'     => 'Retour d\'achat créé',
        'sale_return_created'         => 'Retour de vente créé',
        'restock_created'             => 'Réapprovisionnement créé',
        'inventory_transfer_created'  => 'Transfert de stock créé',
        'expiration_created'          => 'Expiration créée',
        'wallet_transfer_created'     => 'Transfert de portefeuille créé',
        'contract_approved'           => 'Contrat approuvé',
        'contract_rejected'           => 'Contrat rejeté',
        'contract_configured'         => 'Contrat configuré',
        'contract_cancelled'          => 'Contrat annulé',
        'contract_extended'           => 'Contrat prolongé',
        'purchase_received'           => 'Achat reçu',
        'purchase_cancelled'          => 'Achat annulé',
        'purchase_return_approved'    => 'Retour d\'achat approuvé',
        'purchase_return_cancelled'   => 'Retour d\'achat annulé',
        'sale_return_approved'        => 'Retour de vente approuvé',
        'sale_return_cancelled'       => 'Retour de vente annulé',
        'restock_fulfilled'           => 'Réapprovisionnement exécuté',
        'restock_cancelled'           => 'Réapprovisionnement annulé',
        'inventory_transfer_dispatched' => 'Transfert de stock expédié',
        'inventory_transfer_received'   => 'Transfert de stock reçu',
        'inventory_transfer_cancelled'  => 'Transfert de stock annulé',
        'expiration_approved'         => 'Expiration approuvée',
        'purchase_payment_cancelled'  => 'Paiement d\'achat annulé',
    ],

];
