---
name: task-preflight
description: Reconstruct current Pixelfed/VinylHub authority before non-trivial work, classify the change, establish exact source/runtime identity and fail closed on drift.
---

# Task preflight

Use this before non-trivial Pixelfed repository decisions or execution. It
reconstructs current authority and identity; it does not choose Product scope
or architecture.

## Reconstruct current truth

Fresh-read only the authority needed for the task:

- current fork `dev` and upstream `dev` identities;
- this `AGENTS.md` and Pixelfed's `CONTRIBUTING.md` and `CODEOWNERS`;
- the current owner Issue, linked app contract and program Issue;
- relevant open owner PRs when present;
- current build, migration, CI, branch/release and compiled-asset conventions;
- implementation and tests for behavior not resolved by higher authority.

Treat closed Issues, merged PRs, old branches and conversation history as
context unless current authority promotes a conclusion. If current authority
or source/runtime identity conflicts, stop and report the conflict.

## Record the task frame

```text
CURRENT_DEFAULT = <branch and exact SHA>
UPSTREAM_DEV = <exact SHA>
CHANGE_CLASSIFICATION = IMPLEMENTATION_ONLY | REPOSITORY_BEHAVIOR | ARCHITECTURE
MODE = SPIKE | DELIVERY
EXECUTOR = CONTROLLER_GITHUB | MACHINE_LOCAL | OTHER_AUTHORIZED_HOST
REQUIRED_SKILLS = <names or NONE>
AUTHORITY_DRIFT = NONE | BLOCKED
```

Use the mode stated by current authority. A difficult DELIVERY is not a SPIKE.
Establish the exact candidate source/tree and runtime inputs before calling a
baseline admitted.

## Fail closed

Set `AUTHORITY_DRIFT = BLOCKED` and stop before execution when fork/upstream
`dev` moves unexpectedly, the candidate needs an architecture change, the
required source/runtime identity cannot be established, the checkout is
dirty/untracked/ownership-unknown, another writer is active, or execution
would silently substitute an owner, framework, branch model or public
contract.

Do not reset, stash, clean, regenerate protected assets or invent fallback
authority to bypass a blocker.
