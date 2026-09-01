# AGENTS.md

## VinylHub downstream overlay

This file adds only the durable VinylHub execution rules. Pixelfed's native
`CONTRIBUTING.md`, `CODEOWNERS`, Laravel/PHP conventions, migration practices,
CI/build workflow, branch model and compiled-asset policy remain authoritative.

### Authority and ownership

- Fresh-read the current owner Issue, the linked VinylHub app contract and the
  current program Issue before non-trivial work. Current code, tests,
  migrations and runtime configuration are implementation truth.
- The public `mirrorforce/pixelfed` fork owns bounded downstream Community
  behavior. Product, Catalog/Core, Identity and cross-owner orchestration stay
  with their named owners; do not add their persistence or business logic here.
- `dev` continues to track Pixelfed's upstream `dev` according to native
  Pixelfed practice. A VinylHub admitted baseline is an explicit exact source
  commit/tree plus runtime inputs recorded in the admission evidence or PR;
  moving `dev`, `latest` or an unpinned image tag is not an admitted release.

### Delivery and safety

- One repository, one writer, one task branch and one coherent owner PR. Do
  not write directly to the default branch, rewrite upstream history or
  normalize Pixelfed's branch names. Codex sub-agents are prohibited unless
  current authority explicitly changes that rule.
- Never commit secrets, real `.env` files, private user data, durable runtime
  state, credentials or generated/compiled assets prohibited by upstream.
  Use examples, disposable fixtures and task-scoped scratch outside the
  checkout. Do not reset, clean, stash or overwrite unknown local state.
- Classify work as implementation-only, repository behavior or architecture.
  Ownership, persistence lifecycle, dependency direction, public contracts or
  runtime-topology changes fail closed until current authority explicitly
  admits them. M0 must not implement app #72 or app #76.
- Preserve exact evidence: reviewed source/tree/runtime identities, relevant
  worktree state, observable smoke results, authorized mutations, protected
  resources and unresolved unknowns. Do not claim a stronger evidence level
  than was actually observed.

### Required execution methods

For non-trivial work use the repository-local Skills in this order as
applicable:

```text
task-preflight
  -> architecture-conformance  architecture-sensitive DELIVERY
  -> acceptance-evidence        completion, safety or runtime claims
  -> delivery-lifecycle         Issue/PR/Milestone/commit delivery
```

Skills describe repeatable HOW only. They do not replace current Issues,
architecture authority, supported Pixelfed commands or runtime identity.

After creating or changing this file, restart the Codex execution session
before subsequent non-trivial source work so the instruction chain is loaded.
