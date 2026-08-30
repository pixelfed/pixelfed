<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ties a Web Push subscription to the OAuth access token that registered it.
 *
 * Without this there is no way to tell one device's subscription from
 * another's, so DELETE /api/v1/push/subscription could only ever delete all
 * of them — disabling push on a phone would silently kill it on a tablet
 * too. Mastodon's Push API is per access token, and matching that also keeps
 * a client's per-device state coherent.
 *
 * Nullable because rows registered before this migration have no token
 * recorded; PushSubscriptionController treats those as legacy and cleans
 * them up on the next unsubscribe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('webpush.database_connection'))
            ->table(config('webpush.table_name'), function (Blueprint $table) {
                // varchar(100) matches oauth_access_tokens.id, which is a
                // random string rather than an auto-increment integer.
                $table->string('access_token_id', 100)->nullable()->after('subscribable_id');
                $table->index('access_token_id');
            });
    }

    public function down(): void
    {
        Schema::connection(config('webpush.database_connection'))
            ->table(config('webpush.table_name'), function (Blueprint $table) {
                $table->dropIndex(['access_token_id']);
                $table->dropColumn('access_token_id');
            });
    }
};
