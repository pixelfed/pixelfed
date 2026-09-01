---
name: architecture-conformance
description: Compare an architecture-sensitive Pixelfed delivery with current owner authority before editing, including Community ownership, Laravel runtime composition and persistence boundaries.
---

# Architecture conformance

Use this as a low-freedom pre-edit comparison. Current owner authority and
Pixelfed's native conventions define WHAT and WHERE; this method defines only
the comparison procedure.

## Determine the mode

Read the current owner Issue and linked app contract first. In `SPIKE`, return
observations and unresolved questions. In `DELIVERY`, implement only within
the accepted boundary. If an architecture-sensitive DELIVERY lacks an
accepted boundary, stop and report the missing authority.

## Extract the bounded contract

For this repository, compare only applicable dimensions:

- Pixelfed Community ownership versus Product, Core/Catalog and Identity;
- Laravel/PHP/Composer, npm/Mix, Docker and native `dev`/release workflow;
- Pixelfed MySQL/Redis/media/status/feed/moderation lifecycle boundaries;
- public API and migration ownership, including generated/compiled asset
  provenance;
- explicit M0 exclusions, especially account-edge work in app #72 and durable
  Status publication operation-key work in app #76.

Do not turn omitted or irrelevant dimensions into new requirements.

## Compare before implementation

Compare planned file placement, dependencies, commands, runtime behavior,
persistence changes, public surface and generated artifacts with the bounded
contract.

For a conforming architecture-sensitive DELIVERY report exactly:

```text
ARCHITECTURE_CONFORMANCE = PASS
```

For a mismatch or missing authority report:

```text
ARCHITECTURE_CONFORMANCE = BLOCKED
DEVIATION = <specific mismatch or missing authority>
AUTHORITY = <specific current Issue/PR/document fact>
```

Never replace an accepted Pixelfed topology, branch model, dependency
direction, framework, persistence strategy or public contract because another
solution is easier.
