---
name: test-environment
description: Admit the exact Pixelfed OWNER TESTS or OWNER RUNTIME environment before runtime-dependent execution and prevent incomplete evidence from being promoted.
---

# Test environment admission

Use this Skill whenever a Pixelfed task proposes a test or runtime-dependent
command. It is a hard admission contract, not advisory setup guidance. The
current owner Issue, current Pixelfed CI, current source and the proven VinylHub
owner-runtime profile below define the admissible identities. A later current
Human-approved owner Issue may supersede the profile explicitly; upstream
defaults do not supersede it merely by moving.

## Select the evidence class

Classify the claim before selecting a command:

```text
STATIC CHECKS
  formatting, lint, syntax, static analysis or configuration inspection
  no runtime/service claim

OWNER TESTS
  the current native Pixelfed automated test environment

OWNER RUNTIME
  the proven VinylHub Pixelfed owner Docker runtime needed by the claimed behavior

LOCAL INTEGRATION
  whole-environment composition or cross-owner Product integration
  NOT PIXELFED-OWNED
```

Pixelfed owns OWNER TESTS and OWNER RUNTIME evidence. Pixelfed may state exact
owner requirements and artifact identities for an App handoff, but LOCAL
INTEGRATION admission and composition belong to
`mirrorforce/vinyl-catalog-app`. A local integration topology must not silently
replace the owner runtime profile.

## Canonical local OWNER TESTS environment

The VinylHub local OWNER TESTS environment uses one reproducible PHP 8.5
Docker image. The native GitHub workflow may retain PHP 8.4 and 8.5 for
upstream compatibility coverage; that compatibility matrix is separate from
the canonical local environment.

```text
SOURCE
  exact current Pixelfed task SHA/tree

BASE IMAGE
  serversideup/php:8.5-cli@sha256:057168be5fc8304ee8a86cf049d4eb9bd605a250b588ab7bd32499bfc794b05a

SERVICES
  Redis using the exact admitted digest in docker-compose.test.yml
  no MySQL and no Typesense

PERSISTENCE
  SQLite / pdo_sqlite according to the native suite

PREPARATION
  .env.testing copied to .env inside the disposable container
  php artisan key:generate
  disposable Passport test keypair generated inside the container

COMMAND
  vendor/bin/pest --compact
```

The repository-owned entrypoint performs preparation and the canonical Pest
command. It does not bind-mount the checkout, copy private keys to the host or
retain generated `.env` and Passport state after the disposable container is
removed.

### OWNER TESTS entrypoint

Run from the repository root with exact source markers supplied at build time.
In PowerShell, derive them from the checked-out source before rendering Compose:

```powershell
$env:OWNER_TEST_SOURCE_SHA = (git rev-parse HEAD).Trim()
$env:OWNER_TEST_SOURCE_TREE = (git rev-parse 'HEAD^{tree}').Trim()
$env:OWNER_TEST_COMPOSER_LOCK_SHA = (Get-FileHash -Algorithm SHA256 composer.lock).Hash.ToLowerInvariant()
```

The Compose file rejects missing markers; do not replace them with `unknown`.

Then run:

```text
docker compose -f docker-compose.test.yml config
docker compose -p pixelfed-owner-tests -f docker-compose.test.yml up -d --wait redis
docker compose -p pixelfed-owner-tests -f docker-compose.test.yml build owner-tests
docker compose -p pixelfed-owner-tests -f docker-compose.test.yml run --rm --no-deps owner-tests
docker compose -p pixelfed-owner-tests -f docker-compose.test.yml down -v
```

The test Dockerfile and its Dockerfile-specific ignore file intentionally keep
`tests/` and `.env.testing` available to OWNER TESTS while leaving the
production Dockerfile and production ignore rules unchanged.

## Current VinylHub OWNER RUNTIME Docker profile

Use the exact current task source and this profile for OWNER RUNTIME unless a
later current Human-approved owner Issue explicitly requalifies it.

### Exact source and application image

```text
SOURCE
  exact current Pixelfed task SHA/tree

APPLICATION IMAGE
  build the exact task source with the repository Dockerfile
  record the resulting immutable image ID and manifest/repository digest separately

DOCKERFILE BASE
  serversideup/php:8.5-frankenphp@sha256:c8e9d95cd6b83180662f63de646937f3b304041ac4edfbd95ff8bd684467d035

PROVEN RUNTIME
  PHP 8.5.9
  required extensions/tooling include pdo_mysql, redis, gd/imagick/vips and FFmpeg
```

The resulting Pixelfed application-image digest is task-source specific; never
reuse a prior-lane image merely because its PHP base matches.

### Canonical local OWNER RUNTIME entrypoint

Run from the repository root with the task-owned disposable environment:

```text
docker compose -f docker-compose.yml config
docker compose -f docker-compose.yml build pixelfed
docker compose -f docker-compose.yml up -d --no-build --wait db redis pixelfed horizon scheduler
```

The Compose file is the single local VinylHub OWNER RUNTIME entrypoint. It
builds the current source with `Dockerfile`, uses the exact database/cache
identities below, and shares `/var/www/html/storage` across the material
Pixelfed processes. Do not substitute the native OWNER TESTS Compose file for
this path.

### Database and Redis

```text
DATABASE
  MySQL Community Server 8.4.12
  image = container-registry.oracle.com/mysql/community-server@sha256:7dcc4add9183664de3a214daf85a50c3ba6cccfd7534f700b6561bf5b41885be

REDIS
  image = redis@sha256:298e5b3bc566bade82f46ad5511777a4a07a294097ce16ada2f6a42be5239df5
  proven server = Redis 8.10.1
```

The local `docker-compose.yml` is required to use these exact identities. Do
not patch migrations, switch database versions, or use MySQL 9 to manufacture
current VinylHub evidence unless a later Human-approved owner explicitly
authorizes that transition.

SQLite/Pest evidence remains OWNER TESTS evidence. SQLite never satisfies a
MySQL migration or OWNER RUNTIME claim.

### Owner processes and runtime prerequisites

Start/admit only the owner processes material to the claim, while preserving
native Pixelfed lifecycle boundaries:

```text
Pixelfed web/API
Horizon            when queue/background behavior is material
scheduler          when scheduled behavior is material
shared storage     when media crosses web/worker/scheduler processes
Passport keys/client bootstrap when authenticated API behavior is material
FFmpeg/media toolchain when image/video behavior is material
```

For shared-storage claims, the accepted owner prerequisite is one shared
`/var/www/html/storage` lifecycle across the material Pixelfed processes. When
Passport is required, its key material is a protected runtime input under the
owner storage lifecycle; prove key readability and client/bootstrap readiness
without retaining private key bytes or credentials in evidence.

## Admit before execution

For every runtime-dependent OWNER TESTS or OWNER RUNTIME command, complete the
following record before the first dependent command. Values must be exact,
current and safe to retain; do not record tokens, passwords, private keys,
private endpoints or private response bodies.

```text
ENVIRONMENT_ADMISSION = PASS | BLOCKED
EVIDENCE_CLASS        = OWNER TESTS | OWNER RUNTIME | LOCAL INTEGRATION
RUNNER_PLATFORM
RUNNER_IDENTITY
SOURCE_SHA
SOURCE_TREE
APPLICATION_IMAGE_IDENTITY
DEPENDENCY_IDENTITY
TEST_SOURCE_AVAILABLE
DEV_TEST_DEPS_AVAILABLE
CWD
CANONICAL_COMMAND
REQUIRED_SERVICES
SERVICE_IDENTITIES
SERVICE_HEALTH
DATABASE_MODE
MIGRATION_READINESS
QUEUE_WORKER_READINESS
SCHEDULER_READINESS
STORAGE_MOUNT_READINESS
PASSPORT_READINESS
MEDIA_TOOLCHAIN_READINESS
CREDENTIAL_PRECONDITIONS
```

Set `ENVIRONMENT_ADMISSION = PASS` only when every material field is proven
and every required service is ready. A service being started, a successful
process launch, an HTTP 200 or an exit code zero is not readiness evidence by
itself. Set admission to `BLOCKED` with the exact missing or mismatched field
when admission cannot be proven. Do not execute the dependent command merely
to rediscover a known mismatch.

## OWNER TESTS admission

Fresh-read the current native Pixelfed CI workflow at execution time. Derive
the runner/runtime, test configuration, persistence shape, required services,
generated/bootstrap prerequisites, working directory and repository-native
test command it currently requires. Prove those requirements for the selected
source and dependencies; do not copy moving workflow state into durable
guidance.

The admission record must prove, as applicable:

```text
workflow-selected runner/runtime identity
workflow-selected test configuration and setup
workflow-selected persistence/service identity and readiness
current source SHA/tree is the checked-out source
current lockfile and installed dependencies match that source
test source and required dev dependencies are present
workflow-required generated/bootstrap prerequisites are ready
workflow-selected working directory and canonical test command
```

OWNER TESTS evidence remains OWNER TESTS evidence. It cannot be represented as
OWNER RUNTIME MySQL migration, worker, storage, media or authenticated-API
evidence.

## OWNER RUNTIME admission

Use the concrete Docker profile above and exact current task source. Before the
first bounded owner-runtime/API proof, establish the material items below in
one claim-relevant environment:

```text
exact current source SHA/tree
exact current-source-built Pixelfed image ID and manifest/repository digest
Dockerfile/base identity matches current source
MySQL 8.4.12 exact admitted image healthy and reachable
Redis exact admitted image healthy and reachable
fresh migration PASS
repeat migration PASS
upgrade migration PASS when a migration transition is material
application process + owner health/readiness PASS
Passport bootstrap/key readability/client readiness PASS when auth is material
Horizon/worker readiness PASS when queue behavior is material
scheduler readiness PASS when scheduled behavior is material
shared-storage cross-process canary PASS when media crosses processes
FFmpeg/codec/media tooling PASS when image/video behavior is claimed
credential/bootstrap preconditions PASS without retaining secret values
```

Use the smallest native readiness check for each material component: container
health plus actual DB connection, the real migration/state command, owner
health, Horizon/queue status, scheduler health, a cross-process storage canary,
Passport bootstrap/key read, or bounded media-processing/readback. Application
HTTP readiness never substitutes for migration, worker, scheduler, storage,
Passport or media readiness.

After a PASS, retain the disposable owner environment across adjacent focused
iterations where practical. Restart only the affected process/service and
re-admit if a material identity or readiness condition changes. Ordinary source
or business failures after valid admission are development evidence, not an
environment-admission failure.

## Mandatory hard vetoes

```text
an unadmitted moving MySQL image used as VinylHub OWNER RUNTIME database authority
moving/latest/default image used as exact admitted OWNER RUNTIME identity
prior-lane Pixelfed application image reused without exact current-source proof
OWNER TESTS SQLite result represented as MySQL migration/runtime PASS
ad-hoc database/Redis/image version change after startup failure
application HTTP 200 represented as migration PASS
application HTTP 200 represented as Horizon/worker/scheduler/media/Passport PASS
container-local media file represented as cross-process shared-storage PASS
unavailable codec/FFmpeg/tooling represented as image/video PASS
missing Passport keys/client bootstrap represented as authenticated API PASS
App LOCAL INTEGRATION represented as Pixelfed OWNER RUNTIME without owner-equivalent proof
App harness failure represented as an owner source defect without owner-equivalent reproduction
secret/private key value retained in evidence
```

Use these bounded classifications when supported:

```text
PLATFORM_MISMATCH
RUNNER_NOT_ADMITTED
ARTIFACT_IDENTITY_MISMATCH
SERVICE_NOT_READY
SERVICE_HOST_INCOMPATIBLE
MIGRATION_NOT_READY
STORAGE_TOPOLOGY_NOT_READY
WORKER_OR_MEDIA_PREREQUISITE_NOT_READY
CREDENTIAL_PRECONDITION_MISSING
SOURCE_TEST_FAILURE
OWNER_RUNTIME_FAILURE
UNKNOWN
```

## Required handoff and evidence

For LOCAL INTEGRATION or another owner boundary, stop the Pixelfed lane after
recording the exact OWNER TESTS and OWNER RUNTIME requirements and identities
that the App must compose. Do not call that handoff a Pixelfed LOCAL
INTEGRATION PASS. For every claim retain:

```text
EVIDENCE_CLASS
ENVIRONMENT_ADMISSION
SOURCE_SHA / SOURCE_TREE
APPLICATION_IMAGE_IDENTITY
RUNNER_PLATFORM / RUNNER_IDENTITY
DEPENDENCY_IDENTITY
REQUIRED_SERVICES / SERVICE_IDENTITIES / SERVICE_HEALTH
DATABASE_MODE / MIGRATION_READINESS
QUEUE_WORKER_READINESS / SCHEDULER_READINESS
STORAGE_MOUNT_READINESS / PASSPORT_READINESS / MEDIA_TOOLCHAIN_READINESS
CWD / CANONICAL_COMMAND
PROOF_PERFORMED / OBSERVED_RESULT
AUTHORIZED_MUTATION
FORBIDDEN_SIDE_EFFECTS
PROTECTED_RESOURCES
UNKNOWN
DISPOSITION = PASS | BLOCKED | PARTIAL
```

If `ENVIRONMENT_ADMISSION = BLOCKED`, the dependent evidence class is
`BLOCKED`, even if an attempted command happened to exit successfully. Never
claim stronger evidence than the admitted environment supports.
