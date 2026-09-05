---
name: delivery-lifecycle
description: Keep the Pixelfed owner Issue, linked VinylHub contract, PR, milestone and commit state synchronized through one coherent reviewed delivery.
---

# Delivery lifecycle

Use this for durable owner-repository delivery. It owns delivery HOW; it does
not choose scope, architecture or Community semantics.

## Establish ownership

Fresh-read the current owner Issue and linked app contract. Confirm that the
delivery PR belongs to the Pixelfed owner repository and carries the expected
owner milestone. A missing or mismatched milestone blocks review/merge.

## Keep mutable surfaces current

- owner Issue = current repository scope, dependencies and acceptance;
- PR = current delivery contract for the actual diff, reviewed head and
  evidence;
- reviews/comments = actionable evidence tied to a concrete delivery state.

Keep exact source/tree/runtime identities, protected resources, validation,
meaningful non-changes and real unknowns synchronized. Do not make a moving
`dev` branch or `latest` image stand in for an admitted VinylHub baseline.

## PR and merge discipline

Use one task branch and one coherent owner PR for one repository transition.
Preserve Pixelfed's native branch model and compiled-asset rules. Do not merge,
close or terminalize beyond current Controller/Human authority. The linked app
contract is accepted only after Controller review of this owner delivery.

## Report

```text
ISSUE_STATE
PR_STATE
EXPECTED_MILESTONE
ISSUE_MILESTONE
PR_MILESTONE
REVIEWED_HEAD / MERGED_DEFAULT
TERMINAL_RECORD = UPDATED | NOT_APPLICABLE | BLOCKED
STALE_AUTHORITY = NONE | <exact item>
DELIVERY_LIFECYCLE = PASS | BLOCKED
```
