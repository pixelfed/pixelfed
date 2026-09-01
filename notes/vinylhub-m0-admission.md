# VinylHub M0 admission record

This is a review record for the M0 admission delivery, not a replacement for
the current owner Issue, app contract or Pixelfed operational documentation.
The Controller must refresh moving branch and image references before a later
release.

## Candidate qualification

Reviewed 2026-09-01 in `MODE = DELIVERY` for `mirrorforce/pixelfed#1`, linked
to `mirrorforce/vinyl-catalog-app#68` and scheduled by app `#88`.

```text
UPSTREAM_REPOSITORY       = pixelfed/pixelfed
FORK_REPOSITORY           = mirrorforce/pixelfed
ADMISSION_REFERENCE       = v0.12.9
REFERENCE_COMMIT          = 0dc12f23edda47b9e6336c3b1d85687442225055
REFERENCE_TREE            = 450e4671a243b364ba41ef07f318981228987946
UPSTREAM_DEV              = 1a49fb5873fb8db9f2f34e194e46e51362531d9d
FORK_DEV                  = 1a49fb5873fb8db9f2f34e194e46e51362531d9d
CANDIDATE_SOURCE_COMMIT   = 1a49fb5873fb8db9f2f34e194e46e51362531d9d
CANDIDATE_SOURCE_TREE     = 46c54d08c5e1e6d928af72b9a0e33473f91ee9ae
```

Fork/upstream parity is `PASS`; the candidate is the exact current upstream
`dev` source at review time, not an invented release branch or normalized
history. The bounded v0.12.9-to-candidate drift is 556 commits and 1,287
changed paths (46,192 insertions and 28,843 deletions). The drift includes
native Pixelfed application, migration, dependency, CI, media, feed and
moderation evolution and is accepted as upstream-traceable source drift.

The candidate contains existing upstream cache-based `idempotency-key` checks
in the API controllers. M0 adds no account-edge behavior, durable Status
operation-key seam, Product orchestration, Catalog/Core logic or other #72/#76
feature delta.

## Runtime baseline

The admitted runtime source is the candidate tree plus this repository-local
governance overlay. The application source and migrations are unchanged by
M0. The Docker PHP base is pinned to the reviewed multi-architecture manifest:

```text
BASE_IMAGE = serversideup/php:8.5-frankenphp
BASE_IMAGE_MANIFEST = sha256:c8e9d95cd6b83180662f63de646937f3b304041ac4edfbd95ff8bd684467d035
PHP_DECLARATION = ^8.3|^8.4|^8.5
COMPOSER_PLATFORM = 8.3.0
LARAVEL = ^12.0
```

The candidate runtime inputs were reviewed by exact Git blobs:

```text
Dockerfile    a0164b37460ba50334257cea50451436f52cd2925
composer.json d7c21a8027fcb4dbaf48c9c8605b1dd07e7c7f45
composer.lock 3569fc3e8c3fad441108a82bd241564be888e146
package.json  cb8d11bb9f309c7284310429c60ff39344008401
package-lock  d5a96484e30fb81482ab7380c58ffb082a234a35
```

Pixelfed's native policy remains in force: `dev` tracks upstream deployable
code, release images are created from explicit `v*.*.*` tags, and a moving
branch/image tag is not a VinylHub admission identity. A later VinylHub
release must record the exact owner commit/tree and resulting runtime image
digest in its owner PR and app integration packet. Production cutover is not
part of M0.

## Evidence boundary

This record establishes source qualification and runtime inputs. Native
build/migration/startup and baseline Community smoke results belong in the
owner PR evidence packet and must be rerun against the reviewed delivery head;
unmeasured checks remain `UNKNOWN` rather than being inferred from source
inspection.
