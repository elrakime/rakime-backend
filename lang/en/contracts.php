<?php

return [
    'not_pending' => 'A contract can only be modified if it is in pending status.',
    'cannot_update' => 'This contract can no longer be updated.',
    'cannot_update_max_amount' => 'Only administrators can update the maximum amount.',
    'client_banned' => 'This client is banned and cannot create new contracts.',
    'cannot_cancel' => 'This contract can no longer be cancelled.',
    'cannot_update_months_count' => 'The months count cannot be changed after the contract is configured.',
    'cannot_update_active_amounts' => 'Advance and maximum amounts cannot be changed on an active contract.',
    'cannot_update_active' => 'Only the items can be updated on an active contract.',
    'cannot_update_after_start_date' => 'The items of an active contract can only be updated before the start date.',
    'cannot_change_net_amount' => 'The net amount of an active contract cannot be changed.',
    'net_exceeds_max_amount' => 'The net amount exceeds the maximum amount allowed for this contract.',
    'cannot_extend' => 'Only closed or cancelled contracts can be extended.',
    'cannot_extend_items' => 'Extension contracts cannot have items.',
    'missing_remaining_amount' => 'The contract has no remaining amount to extend.',
];
