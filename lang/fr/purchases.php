<?php

return [
    'not_pending' => 'Un achat ne peut être modifié que s\'il est en attente.',
    'must_be_completed' => 'L\'achat doit être terminé avant que le traitement du paiement puisse commencer.',
    'amount_exceeds_remaining' => 'Le montant du paiement dépasse le solde restant dû.',
    'no_items' => 'Au moins un article doit être ajouté à l\'achat.',
    'invalid_supplier' => 'Le fournisseur spécifié est indisponible ou n\'est plus actif.',
    'payment_already_canceled' => 'Ce paiement a déjà été annulé.',
    'payment_canceled_note' => 'Paiement de :amount annulé. Fonds retournés au portefeuille.',
    'inventory_branch_mismatch' => 'L\'inventaire sélectionné n\'appartient pas à la branche de cet achat.',
    'missing_branch' => 'Aucune branche assignée à cet achat. Veuillez fournir un inventory_id.',
    'no_branch_inventories' => 'Aucun inventaire trouvé pour la branche de cet achat.',
    'multiple_branch_inventories' => 'Cette branche a plusieurs inventaires. Veuillez spécifier lequel utiliser.',
    'selling_price_below_purchase' => 'Le prix de vente doit être supérieur au prix d\'achat (:price).',
    'selling_price_below_old_batches' => 'Le prix de vente doit être supérieur au prix d\'achat maximum des lots en stock (:price).',
    'installment_price_below_purchase' => 'Le prix de versement doit être supérieur au prix d\'achat (:price).',
    'installment_price_below_old_batches' => 'Le prix de versement doit être supérieur au prix d\'achat maximum des lots en stock (:price).',
];
