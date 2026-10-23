<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scheduled Task Settings
    |--------------------------------------------------------------------------
    |
    | Toggles for optional scheduled tasks defined in routes/scheduledtasks.php.
    |
    */

    /*
    | Account storage reconciler
    |
    | Optional weekly scheduled task that recalculates users.storage_used from
    | actual media, correcting drift on accounts that never upload or delete.
    | The upload/delete/read paths already self-heal stale counters, so this is
    | a background hygiene job and is disabled by default.
    */
    'account_storage_reconcile' => env('ACCOUNT_STORAGE_RECONCILE', false),

];
