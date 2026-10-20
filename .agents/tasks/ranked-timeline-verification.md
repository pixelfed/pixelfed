# Ranked Timeline Pagination Fix — Verification

## Change

`app/Services/Concerns/SortedSetTimeline.php` — `scoreForStatus()` fallback
changed from `self::snowflakeScore($statusId)` to `(int) $statusId`. For the
small ids the tests seed, `snowflakeScore()` collapsed every member to the same
score (`1549756800`), so the exclusive score bounds in `setRankedMaxId` /
`setRankedMinId` excluded all members and both methods returned `[]`. Scoring by
raw id restores distinct, id-ordered scores and the exclusive bounds now produce
the expected windows.

## Environment

- `composer install --no-interaction --no-progress --prefer-dist`
- `.env` present (copied from `.env.testing`), `APP_KEY` set
- Redis reachable at 127.0.0.1:6379 (responds to PING)
- OAuth keys generated (`storage/oauth-private.key`, `storage/oauth-public.key`)
  via the same openssl RSA 2048 step the CI workflow runs; without them 207
  unrelated OAuth tests throw `LogicException: Invalid key supplied`.

## 1. Four previously-failing test files

Command:
```
vendor/bin/pest tests/Feature/Api/PublicTimelineRankedMinIdTest.php tests/Feature/Api/TimelineRankedMaxIdTest.php tests/Feature/Services/TimelineRankedPaginationBoundsTest.php tests/Feature/Timeline/RankedMinIdNewestTest.php --compact
```

Result:
```
Tests:    16 passed (6192 assertions)
Duration: 19.66s
```

## 2. Full suite

Command:
```
vendor/bin/pest --compact
```

Result:
```
Tests:    1 skipped, 1620 passed (12598 assertions)
Duration: 217.45s
```

Baseline was 15 failed, 1 skipped, 1605 passed. Now 1620 passed, 1 skipped,
0 failed — the 15 ranked-pagination failures are resolved and nothing previously
passing regressed.

## 3. Style

```
vendor/bin/pint --test app/Services/Concerns/SortedSetTimeline.php
PASS   1 file
```
