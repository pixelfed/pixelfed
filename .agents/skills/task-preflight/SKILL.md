---
name: task-preflight
description: Reconstruct current Pixelfed/VinylHub authority before non-trivial work, classify the change, establish exact source/runtime identity and fail closed on drift.
---

# Task preflight

Use this before non-trivial Pixelfed repository decisions or execution. It
reconstructs current authority and identity; it does not choose Product scope
or architecture.

## Canonical routing

Repository-owned repeatable Skills are canonical under
`.agents/skills/<skill>/SKILL.md`; `.codex/` is tool-private/local unless a
future repository contract explicitly says otherwise. The Controller/GitHub
surface reconstructs current authority and maintains the Issue/PR/review
surfaces. The machine-local Executor writes repository files within the
authorized handoff and returns exact validation evidence.

## Reconstruct current truth

Fresh-read only the authority needed for the task:

- current fork `dev` and upstream `dev` identities;
- this `AGENTS.md` and Pixelfed's `CONTRIBUTING.md` and `CODEOWNERS`;
- the current owner Issue, linked app contract and program Issue;
- relevant open owner PRs when present;
- current build, migration, CI, branch/release and compiled-asset conventions;
- implementation and tests for behavior not resolved by higher authority.

Classify the requested claim as `T0 STATIC`, `T1 OWNER TEST`, `T2 OWNER
RUNTIME / OWNER INTEGRATION` or `T3 APP COMPOSITION`. Dispatch
`.agents/skills/test-environment/SKILL.md` for every runtime-dependent T1/T2
command before that command is run. T0 is static evidence only. T3 is owned by
`mirrorforce/vinyl-catalog-app`; Pixelfed may provide owner requirements and
identities but cannot admit the composed environment.

Treat closed Issues, merged PRs, old branches and conversation history as
context unless current authority promotes a conclusion. If current authority
or source/runtime identity conflicts, stop and report the conflict.

## Record the task frame

```text
PROGRAM_ISSUE = <current program or linked app Issue>
OWNER_ISSUE = <current repository owner Issue>
REPOSITORY = <owner/repository>
CURRENT_DEFAULT_SHA = <default branch exact SHA>
UPSTREAM / ADMITTED_BASELINE = <upstream exact SHA or admitted source/tree/runtime inputs>
CHANGE_CLASSIFICATION = IMPLEMENTATION_ONLY | REPOSITORY_BEHAVIOR | ARCHITECTURE
MODE = SPIKE | DELIVERY
AUTHORIZED_RESULT = <bounded result from current authority>
AUTHORIZED_WRITESET = <paths or NONE>
NON_SCOPE = <explicit exclusions>
REQUIRED_SKILLS = <names or NONE>
VALIDATION = <minimum sufficient validation>
STOP_CONDITIONS = <authority, state or scope blockers>
PR_EXPECTATION = <branch, review and merge constraints>
INTEGRATION_GATE = <linked downstream gate or NONE>
SUBAGENTS = PROHIBITED
EXECUTOR = CONTROLLER_GITHUB | MACHINE_LOCAL | OTHER_AUTHORIZED_HOST
ENVIRONMENT_ADMISSION = NOT_REQUIRED (T0) | PASS | BLOCKED (T1/T2)
AUTHORITY_DRIFT = NONE | BLOCKED
```

Use the mode stated by current authority. A difficult DELIVERY is not a SPIKE.
For a machine-local handoff, preserve the fields above from the current
Issue/program contract rather than copying volatile task scope into this Skill.
Establish the exact candidate source/tree and runtime inputs before calling a
baseline admitted. For T1/T2, the test-environment record must include the
exact source SHA/tree, runner, dependency identity, working directory,
canonical command, required service identities and bounded readiness evidence.
Do not run or promote a runtime-dependent command while admission is
`BLOCKED` or unrecorded.

## Fail closed

Set `AUTHORITY_DRIFT = BLOCKED` and stop before execution when fork/upstream
`dev` moves unexpectedly, the candidate needs an architecture change, the
required source/runtime identity cannot be established, the checkout is
dirty/untracked/ownership-unknown, another writer is active, or execution
would silently substitute an owner, framework, branch model or public
contract.

If a material T1/T2 prerequisite cannot be proven, set
`ENVIRONMENT_ADMISSION = BLOCKED` and stop only the dependent execution; do
not patch Pixelfed source or weaken runtime semantics to manufacture PASS.

Do not reset, stash, clean, regenerate protected assets or invent fallback
authority to bypass a blocker.
