---
name: acceptance-evidence
description: Match Pixelfed/VinylHub completion and safety claims to minimum sufficient source, build, migration and runtime evidence with exact identities.
---

# Acceptance evidence

Use this whenever a delivery claims completion, preserved Community behavior,
source admission, workspace safety, integration or runtime support. Start from
the claim, not from commands that happen to be available.

## State the claim and boundary

Classify the strongest evidence required:

```text
static / unit
  -> bounded supported-interface integration
  -> real local environment
  -> terminal / production
```

Capture only relevant identities: reviewed source and PR head, fork/upstream
and release references, source tree, runtime image/input digests, lockfiles,
worktree state and protected resources. Exit code `0` alone is not proof;
record the observable invariant or output.

## Choose minimum sufficient proof

Use supported Pixelfed commands and interfaces:

- PHP/application behavior -> focused Pest tests and `php artisan` checks;
- dependency/build behavior -> Composer/npm lock-aware checks and native build;
- migration/startup -> owner-native Docker/Artisan flow against disposable
  resources only;
- Community preservation -> focused Status/media/feed/moderation smoke;
- repository admission -> diff review, exact identity review and clean/known
  worktree state.

Do not run destructive migrations, production actions, broad builds or asset
regeneration merely for ceremony. Do not claim production or terminal evidence
from static, unit or local proof.

## Record mutations and unknowns

```text
AUTHORIZED_MUTATION = <what changed or none>
FORBIDDEN_SIDE_EFFECTS = <observed zero/non-zero set>
PROTECTED_RESOURCES = <untouched or exact authorized change>
UNKNOWN = NONE | <exact unresolved item>
```

Preserve unknowns instead of converting an unmeasured criterion into PASS.
Separate observed facts from interpretation against acceptance criteria.

## Evidence packet

```text
CLAIM
EVIDENCE_LEVEL
CODE / DELIVERY IDENTITY
ENVIRONMENT / SOURCE / RUNTIME IDENTITY
PROOF PERFORMED
OBSERVED RESULT
AUTHORIZED MUTATION
FORBIDDEN SIDE EFFECTS
PROTECTED RESOURCES
REPLAYABILITY = NOT_REQUIRED | PASS | NOT_RETAINED | UNKNOWN
UNKNOWN
DISPOSITION = PASS | BLOCKED | PARTIAL
```
