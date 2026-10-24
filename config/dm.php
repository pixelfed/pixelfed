<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Group conversations
    |--------------------------------------------------------------------------
    |
    | A conversation is identified by its set of participants. The limit counts
    | everyone in the conversation, including the person who started it.
    | Inbound messages addressed to more actors than this are dropped.
    |
    */

    'groups' => [
        'enabled' => (bool) env('DM_GROUPS_ENABLED', true),
        'max_participants' => (int) env('DM_MAX_PARTICIPANTS', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message limits
    |--------------------------------------------------------------------------
    */

    'max_message_length' => (int) env('DM_MAX_MESSAGE_LENGTH', 2000),

    'max_media' => (int) env('DM_MAX_MEDIA', 4),

    /*
    |--------------------------------------------------------------------------
    | Message requests
    |--------------------------------------------------------------------------
    |
    | A recipient who does not accept messages from everyone gets the
    | conversation as a request. Until they accept (or reply), a local sender
    | can only send `sender_limit` messages, and at most `inbound_limit`
    | messages from a remote sender are stored.
    |
    */

    'requests' => [
        'sender_limit' => (int) env('DM_REQUEST_SENDER_LIMIT', 1),
        'inbound_limit' => (int) env('DM_REQUEST_INBOUND_LIMIT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Federation
    |--------------------------------------------------------------------------
    |
    | `max_actor_fetches` caps how many unknown actors a single inbound message
    | can make this server fetch while resolving its participants.
    |
    */

    'federation' => [
        'max_actor_fetches' => (int) env('DM_MAX_ACTOR_FETCHES', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backfill
    |--------------------------------------------------------------------------
    |
    | The migration converts legacy direct messages inline only when there are
    | fewer rows than this. Larger instances run
    | `php artisan dm:backfill-conversations` themselves.
    |
    */

    'backfill' => [
        'inline_threshold' => (int) env('DM_BACKFILL_INLINE_THRESHOLD', 25000),
    ],
];
