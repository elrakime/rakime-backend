<?php

return [
    'not_draft' => 'Un achat ne peut être modifié que s\'il est à l\'état de brouillon.',
    'must_be_received' => 'L\'achat doit être reçu avant que le traitement du paiement puisse commencer.',
    'amount_exceeds_remaining' => 'Le montant du paiement dépasse le solde restant dû.',
    'no_items' => 'Au moins un article doit être ajouté à l\'achat.',
    'invalid_supplier' => 'Le fournisseur spécifié est indisponible ou n\'est plus actif.',
    'payment_already_canceled' => 'Ce paiement a déjà été annulé.',
    'payment_canceled_note' => 'Paiement de :amount annulé. Fonds retournés au portefeuille.',
    'inventory_branch_mismatch' => 'L\'inventaire sélectionné n\'appartient pas à la branche de cet achat.',
    'missing_branch' => 'Aucune branche assignée à cet achat. Veuillez fournir un inventory_id.',
    'no_branch_inventories' => 'Aucun inventaire trouvé pour la branche de cet achat.',
    'multiple_branch_inventories' => 'Cette branche a plusieurs inventaires. Veuillez spécifier lequel utiliser.',
];
