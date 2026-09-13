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
    | Weekly scheduled task that recalculates users.storage_used from actual
    | media, correcting drift. The incremental charge/refund on the
    | upload/delete paths is best-effort (a finalize-job retry can over-count),
    | so this reconciler is the authoritative backstop and is enabled by
    | default. The upload/delete/read paths also self-heal stale counters inline.
    */
    'account_storage_reconcile' => env('ACCOUNT_STORAGE_RECONCILE', true),

];
