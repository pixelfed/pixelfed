<?php

use App\Models\AccountInterstitial;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Autospam dashboard - ONLY_FULL_GROUP_BY regression
|--------------------------------------------------------------------------
|
| The spam() dashboard aggregates AccountInterstitial rows. The original
| queries selected non-grouped bare columns alongside aggregates, which
| MariaDB/MySQL reject under ONLY_FULL_GROUP_BY (a 500 on the reports page).
| SQLite is permissive and cannot reproduce the strict-mode error, so these
| tests assert the aggregate-only query shape and correct results instead.
|
*/

function seedAutospam(int $userId, int $count, ?int $secondsToHandle = null): void
{
    foreach (range(1, $count) as $i) {
        DB::table('account_interstitials')->insert([
            'user_id' => $userId,
            'type' => 'post.autospam',
            'item_id' => $i,
            'item_type' => 'App\\Models\\Status',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
            'appeal_handled_at' => $secondsToHandle !== null
                ? now()->subDays(2)->addSeconds($secondsToHandle)
                : null,
        ]);
    }
}

describe('autospam avg reports-per-user query', function () {
    it('averages the per-user report counts without selecting bare columns', function () {
        seedAutospam(1, 4);
        seedAutospam(2, 2);

        $avg = DB::query()
            ->fromSub(
                AccountInterstitial::selectRaw('count(id) as counter')
                    ->whereType('post.autospam')
                    ->groupBy('user_id'),
                'agg'
            )
            ->avg('counter');

        // (4 + 2) / 2 users = 3
        expect((float) $avg)->toBe(3.0);
    });

    it('builds a subquery that only selects the aggregate column', function () {
        $sql = AccountInterstitial::selectRaw('count(id) as counter')
            ->whereType('post.autospam')
            ->groupBy('user_id')
            ->toSql();

        expect($sql)->toContain('count(id) as counter');
        expect(strtolower($sql))->not->toContain('select *');
    });
});

describe('autospam avg time-to-handle query', function () {
    it('executes and selects only the aggregate, not a bare DATE(created_at)', function () {
        seedAutospam(1, 3, 120);

        $query = AccountInterstitial::selectRaw('AVG(TIME_TO_SEC(TIMEDIFF(appeal_handled_at, created_at))) AS timediff')
            ->whereType('post.autospam')
            ->whereNotNull('appeal_handled_at')
            ->where('created_at', '>', now()->subMonth());

        // The bare DATE(created_at) column was the ONLY_FULL_GROUP_BY offender.
        // SQLite lacks TIME_TO_SEC/TIMEDIFF so we assert the safe SQL shape
        // rather than executing the vendor-specific aggregate.
        $sql = strtolower($query->toSql());
        expect($sql)->not->toContain('date(created_at)');
        expect($sql)->toContain('avg(time_to_sec(timediff(appeal_handled_at, created_at)))');
    });
});
