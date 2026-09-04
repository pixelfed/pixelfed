---
name: test-environment
description: Admit the exact Pixelfed test or owner-runtime environment before T1/T2 execution and prevent lower-tier or incomplete readiness evidence from being promoted.
---

# Test environment admission

Use this Skill whenever a Pixelfed task proposes a test or runtime-dependent
command. It is a hard admission contract, not advisory setup guidance. The
current owner Issue, current Pixelfed CI, current runtime conventions and the
task's explicitly admitted artifacts define the identities; this Skill does
not create a permanent version matrix.

## Select one validation tier

Classify the claim before selecting a command:

```text
T0 STATIC
  formatting, lint, syntax, static analysis or configuration inspection
  no runtime/service claim

T1 OWNER TEST
  the current native Pixelfed CI/unit/integration test environment

T2 OWNER RUNTIME / OWNER INTEGRATION
  the exact owner-supported Pixelfed runtime needed by the claimed behavior

T3 APP COMPOSITION
  whole-environment composition or cross-owner Product integration
  NOT PIXELFED-OWNED
```

T0 may be used for bounded static evidence only. Do not describe a T0 result
as test, migration, service, worker, media or runtime evidence.

Pixelfed owns T1 and T2 evidence. Pixelfed may state exact owner requirements
and artifact identities for an App handoff, but T3 admission and composition
belong to `mirrorforce/vinyl-catalog-app`. Do not add App orchestration,
cross-owner coordination or T3 semantics to this Skill.

## Admit before execution

For every runtime-dependent T1/T2 command, complete the following record
before the first command that depends on the environment. Values must be exact,
current and safe to retain; do not record tokens, passwords, private keys,
proxy URLs or private response bodies.

```text
ENVIRONMENT_ADMISSION = PASS | BLOCKED
VALIDATION_TIER        = T1 OWNER TEST | T2 OWNER RUNTIME / OWNER INTEGRATION
RUNNER_PLATFORM        = exact OS/architecture and execution platform
RUNNER_IDENTITY        = exact CI runner/workflow run or authorized host identity
SOURCE_SHA             = exact Pixelfed source commit
SOURCE_TREE            = exact source tree for SOURCE_SHA
DEPENDENCY_IDENTITY    = current lockfile identity and installed dependency/dev-dependency evidence
TEST_SOURCE_AVAILABLE  = exact test source/fixture availability, when material
DEV_TEST_DEPS_AVAILABLE = exact dev dependency availability, when material
CWD                    = exact working directory
CANONICAL_COMMAND      = exact command intended to run
REQUIRED_SERVICES      = only services material to the claim
SERVICE_IDENTITIES     = exact admitted image/artifact/service identities
SERVICE_HEALTH         = bounded readiness result for every required service
DATABASE_MODE          = exact database shape and identity, when material
MIGRATION_READINESS    = actual migration/state evidence, when material
QUEUE_WORKER_READINESS = actual worker/Horizon/queue evidence, when material
STORAGE_MOUNT_READINESS = cross-process storage evidence, when material
MEDIA_TOOLCHAIN_READINESS = actual codec/FFmpeg/tooling evidence, when material
CREDENTIAL_PRECONDITIONS = presence/scope/readiness only, when material
```

Set `ENVIRONMENT_ADMISSION = PASS` only when every material field is proven
and every required service is ready. A service being started, an HTTP 200, a
successful process launch or an exit code of zero is not readiness evidence by
itself. Set `ENVIRONMENT_ADMISSION = BLOCKED` with the exact missing or
mismatched field when admission cannot be proven. Do not execute the dependent
T1/T2 command to rediscover a known mismatch, and do not promote any result
obtained while admission is blocked.

## T1 — owner test admission

Fresh-read the current native Pixelfed CI workflow at execution time. Derive
the runner/runtime, test configuration, persistence shape, required services,
generated/bootstrap prerequisites, working directory and repository-native test
command that it currently requires. Prove those requirements for the selected
source and dependencies; do not copy volatile workflow state into durable
`AGENTS.md` guidance or this Skill.

The admission record must prove, as applicable to the current workflow:

```text
workflow-selected runner/runtime identity
workflow-selected test configuration and setup
workflow-selected persistence shape and exact test service identities/readiness
current source SHA/tree is the checked-out source
current lockfile and installed dependencies match that source
test source and required dev dependencies are present
workflow-required generated/bootstrap prerequisites are ready
workflow-selected working directory and canonical test command
```

The current workflow is the authority for its runner and dependency details,
not a remembered version or topology. A host lacking the workflow-required
runtime, extensions or tooling is `RUNNER_NOT_ADMITTED`; do not run the test
once merely to rediscover that fact. A container or runtime image is not
automatically a T1 runner: prove that the exact source, tests, dependencies and
workflow-required runtime inputs are present before using it.

T1 evidence remains T1 evidence. It cannot be represented as T2 owner-runtime
or T2 migration proof, regardless of the persistence or service shape selected
by the current workflow.

## T2 — owner runtime/integration admission

Use the exact source/image and runtime inputs admitted for the current task.
Never infer T2 identity from T1 CI, moving upstream defaults or an unpinned
`latest` image. The admission record must prove the material items below:

```text
exact Pixelfed source/image identity
exact admitted database identity and connection readiness
exact admitted Redis identity and readiness
required PHP/runtime extensions
required fresh/upgrade/repeat migration state
application process and owner health/readiness
queue/Horizon/scheduler readiness when the behavior depends on them
shared owner storage across web/worker/scheduler when media crosses processes
Passport key mount when authentication depends on it
FFmpeg/codec/media tooling when image/video behavior is claimed
credential/bootstrap preconditions without retaining secret values
```

Use the smallest native readiness check for each material component: native
service health, a database connection, the actual migration/state command, the
owner health/process check, an appropriate worker/queue check, a cross-process
storage canary, or a bounded media-processing/readback check. Preserve the
exact observed result and identity. Do not use application HTTP readiness as a
substitute for migrations, workers, shared storage or media tooling.

After a PASS, retain the disposable owner environment across adjacent focused
iterations where practical. Restart only the affected process/service and
re-admit if a material identity or readiness condition changes. An ordinary
source or business failure after admission is development input; classify it
separately from environment admission.

## Mandatory hard vetoes

The following are blockers, not suggestions:

```text
moving/latest/default image used as an exact admitted T2 identity
upstream/default database substituted for a downstream-admitted database
without current authority explicitly admitting it
T1 SQLite result represented as MySQL migration/runtime PASS
ad-hoc database/Redis/image version change after startup failure
without current authority admitting the substitution
intentionally unreachable or dummy required service
application HTTP 200 represented as migration PASS
application HTTP 200 represented as Horizon/worker/media-processing PASS
container-local media file represented as cross-process shared-storage PASS
unavailable codec/FFmpeg/tooling represented as image/video PASS
App harness failure represented as an owner source defect without
owner-equivalent reproduction and provenance
```

Use these execution-oriented classifications for a blocked admission or its
provenance:

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

For T3 or another owner boundary, stop the Pixelfed lane after recording the
exact T1/T2 requirements and identities that the App must compose. Do not call
that handoff a Pixelfed T3 PASS. For any T1/T2 claim, retain this minimum
packet:

```text
VALIDATION_TIER
ENVIRONMENT_ADMISSION
SOURCE_SHA / SOURCE_TREE
RUNNER_PLATFORM / RUNNER_IDENTITY
DEPENDENCY_IDENTITY
REQUIRED_SERVICES / SERVICE_IDENTITIES / SERVICE_HEALTH
DATABASE_MODE and material readiness fields
CWD / CANONICAL_COMMAND
PROOF_PERFORMED / OBSERVED_RESULT
AUTHORIZED_MUTATION
FORBIDDEN_SIDE_EFFECTS
PROTECTED_RESOURCES
UNKNOWN
DISPOSITION = PASS | BLOCKED | PARTIAL
```

If `ENVIRONMENT_ADMISSION = BLOCKED`, the disposition for the dependent tier
is `BLOCKED`, even if an attempted command happened to exit successfully. Do
not claim a stronger evidence level than the admitted tier supports.
