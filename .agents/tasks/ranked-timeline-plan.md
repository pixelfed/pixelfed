# Ranked Timeline Pagination Fix — Implementation Plan

## Context and root cause (confirmed from git history + code)

15 tests fail across 4 files, all asserting ranked pagination on
`getRankedMaxId` / `getRankedMinId`:

- `tests/Feature/Api/PublicTimelineRankedMinIdTest.php`
- `tests/Feature/Api/TimelineRankedMaxIdTest.php`
- `tests/Feature/Services/TimelineRankedPaginationBoundsTest.php`
- `tests/Feature/Timeline/RankedMinIdNewestTest.php`

In every failure the method returns `[]` where a real id window is expected.

The revert commit `a844a763c` ("Revert timeline sorted-set feed changes
(#7800)") restored the service/trait to a prior state. The surviving current
trait `app/Services/Concerns/SortedSetTimeline.php` already uses the correct
pagination SQL:

- `setRankedMaxId`: `Redis::zrevrangebyscore($key, '('.$score, '-inf', ['limit' => [0, $limit]])` — exclusive upper bound, offset 0.
- `setRankedMinId`: `Redis::zrevrangebyscore($key, '+inf', '('.$score, ['limit' => [0, $limit]])` — exclusive lower bound, newest-first, offset 0.

These bounds are exactly the "exclusive boundary, newest-first window"
semantics the 4 test files pin, so the pagination query logic itself does NOT
need to change.

The real regression is the **score fallback**. The scoring used when a status
has no cached `created_at` changed during the revert:

- Prior (passing) design (`git show 3912af9ba:app/Services/PublicTimelineService.php`): `statusScore()` fell back to `(int) $statusId` — the raw id becomes the sorted-set score. Cursor scoring (`cursorScore`) routed through the same fallback, so add-scoring and cursor-scoring agreed.
- Current (failing) design: `SortedSetTimeline::scoreForStatus()` falls back to `self::snowflakeScore($statusId)`.

`snowflakeScore()` computes `floor((($id >> 22) + 1549756800000) / 1000)`. For
the small integer ids the tests use (100–600, 10–50), `$id >> 22` is `0`, so
**every test id collapses to the same score** (`1549756800`). With all members
sharing one score, the exclusive score bound `'('.$score` excludes every
member, and both ranked methods return `[]`. That is the observed failure.

The tests seed ids directly via `add()` (e.g. `PublicTimelineService::add(100)`)
and never create real `Status` rows, so `StatusService::get()` returns nothing
and the fallback is always taken. Restoring the raw-id fallback makes the score
equal the id (as the passing implementation did), the sorted set orders by id,
and the already-correct exclusive bounds produce the expected windows.

## Decision: fix the implementation, do NOT delete tests

The 4 test files originate from independent pagination-correctness commits that
predate and are not part of the reverted sorted-set feature:

- `PublicTimelineRankedMinIdTest.php` — `0c4f215d0` (exclusive min_id bound).
- `TimelineRankedMaxIdTest.php` — `6a73ec705` (exclusive max_id bound, offset 0).
- `TimelineRankedPaginationBoundsTest.php` + `RankedMinIdNewestTest.php` — `3912af9ba` (newest-first window above min_id).

They assert intended, still-valid pagination behavior. Fix the implementation.
Do NOT delete any test file.

## Scope guard: do NOT reintroduce reverted machinery

The revert `a844a763c` also removed broader machinery. Do NOT bring any of it
back — it is out of scope and unrelated to these failures:

- feed-rebuild baseline/publish (`rebuildBaseline`, `publishRebuild`, `purgeRebuildKeys`, `rebuildKeyTtl`)
- empty-warm guard (`emptyWarmInterval`, `shouldEmptyWarm`, `markEmptyWarmed`, `clearEmptyWarmGuard`, `READ_LIMIT`)
- feed-recovery, unfollow-preservation, network-reconcile (`orderByDesc('created_at')` warm ranking, `resolveCursorMember` rank-based paging, etc.)

Also do NOT change `warmCache()` in any service, and do NOT change the existing
exclusive score-bound queries in `setRankedMaxId` / `setRankedMinId` — they are
already correct.

## Implementation

- [ ] 1. Restore the raw-id score fallback in the shared trait.
      In `scoreForStatus()`, change the final fallback return from
      `self::snowflakeScore($statusId)` to `(int) $statusId`, so a status with
      no resolvable `created_at` is scored by its own id — matching the passing
      pre-revert design and keeping add-scoring and cursor-scoring consistent
      (both route through `scoreForStatus`). Leave the `StatusService::get()` +
      `created_at` branch, the method signature, and the surrounding code
      untouched. Leave `snowflakeScore()` defined (it is still referenced by the
      `warmCache()` rebuild paths in `HomeTimelineService` and
      `PublicTimelineService`, which are out of scope).
      Files: `app/Services/Concerns/SortedSetTimeline.php`
      Verify: covered by the full test run in step 2.

- [ ] 2. Run the four previously-failing test files and confirm all pass.
      This is the authoritative verification; the fix is score-driven, not a
      grep target.
      Files: none (verification only)
      Verify (from the worktree root
      `/Users/sparelaptop/github/pixelfed/.worktrees/fix-ranked-timeline-tests`):
      first-time setup if `vendor/` is absent —
      `composer install --no-interaction --no-progress --prefer-dist`,
      then `cp -n .env.testing .env && php artisan key:generate`,
      and ensure a Redis server is reachable at `REDIS_HOST`/`REDIS_PORT`
      (CI uses `127.0.0.1:6379`; export `REDIS_HOST=127.0.0.1 REDIS_PORT=6379`).
      Then:
      `vendor/bin/pest tests/Feature/Api/PublicTimelineRankedMinIdTest.php tests/Feature/Api/TimelineRankedMaxIdTest.php tests/Feature/Services/TimelineRankedPaginationBoundsTest.php tests/Feature/Timeline/RankedMinIdNewestTest.php --compact`
      Expected: all tests pass (previously 15 failing, now 0). In particular the
      bounds property test (`TimelineRankedPaginationBoundsTest`) runs 120
      random iterations per service and must stay green.

- [ ] 3. Run the full suite to confirm no regression elsewhere.
      Changing the fallback also affects live `add()` scoring for statuses
      without a cached `created_at`; confirm no other timeline test regresses.
      Files: none (verification only)
      Verify: `vendor/bin/pest --compact` — the suite passes (or, at minimum,
      no new failures appear relative to the pre-change baseline). If the local
      environment cannot run the full suite, state that explicitly and rely on
      the CI workflow `.github/workflows/php-laravel-tests.yml`
      (`vendor/bin/pest --compact` with Redis + sqlite) as the gate.

- [ ] 4. Confirm formatting matches the project style.
      The change uses the same cast/style already present in the file, so no
      reformat is expected.
      Files: `app/Services/Concerns/SortedSetTimeline.php`
      Verify: `vendor/bin/pint --test app/Services/Concerns/SortedSetTimeline.php`
      — reports no style changes needed.

## Expected test traces (score == id after the fix)

- `getRankedMaxId(400, 3)` on `[100,200,300,500]` → `zrevrangebyscore('(400','-inf',[0,3])` → `[300,200,100]`.
- `getRankedMaxId(400, 10)` on `[100..500]` → `[300,200,100]`, excludes 400.
- `getRankedMaxId(1, 550, 3)` (Home) on `[100..600]` → `[500,400,300]` (full limit, no off-by-one).
- `getRankedMinId(200, 10)` on `[100,200,300]` → `[300]`, excludes 200.
- `getRankedMinId(200, 2)` on `[100..500]` → `[500,400]`.
- `getRankedMinId(10, 2)` on `[10,20,30,40,50]` → `[50,40]`.
- `getRankedMinId(1, 3)` (Network) on `[10..50]` → `[50,40,30]` (low sentinel yields newest page).
- GroupFeed `getRankedMaxId(1, 400, 3)` on `[100,200,300,500]` → `[300,200,100]`: `GroupFeedService` is independent of the trait, already scores by raw id (`zadd(key, $val, $val)`) and already uses the exclusive `'('.$start` bound — no change required.

## Notes / assumptions

- `GroupFeedService::getRankedMinId` still uses an inclusive `$end` bound, but no
  test exercises it; left unchanged to keep the fix minimal.
- The `warmCache()` rebuild paths score missing-`created_at` rows with
  `snowflakeScore()`; this pre-existing inconsistency with live `add()` scoring
  is out of scope and untouched. No failing test depends on it.
