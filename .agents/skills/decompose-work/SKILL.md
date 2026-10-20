---
name: decompose-work
description: Split an item into the next useful children only where each part has an independently acceptable outcome.
---

# Decompose work

**Inputs:** the current item, its exploration artifacts, acceptance criteria
and dependencies; the child paths that will be written.

## Procedure

1. List plausible boundaries, including **no split**.
2. A candidate becomes a child only if all hold:
   - it has its own observable, independently acceptable outcome;
   - someone could verify it without completing its siblings;
   - it has its own scope, owned paths and failure boundary.
   Files, layers, phases or meetings without an independent outcome are not
   children; they are steps inside one item.
3. Prefer fewer, larger children. A child should be completable in one focused
   session. If a candidate is still too broad, split again only if the parts
   meet the same test.
4. Express ordering with `depends_on`, not with a new hierarchy level.
5. Draft each child with outcome, scope, acceptance criteria, dependencies and
   owned paths. Do not approve them; present their exact IDs and revisions.
6. Record a deferred or blocked part with its reason and the trigger that
   would revisit it. Unexplored is not deferred.

## Research-heavy work

Research is not the same as implementation. When an item needs evidence
before it can be written, split the evidence into **explorer questions**
before splitting the deliverable:

- one explorer answers one independent dimension (route, base, access,
  heritage, practicalities, prior art, feasibility);
- each writes one focused artifact under `items/<id>/outputs/`;
- a worker then synthesizes the deliverable from the plan and those artifacts;
- the worker does not repeat the research, only checks a specific fact the
  plan or a criterion requires.

Use a **subtask** when the part has its own independently acceptable outcome
and its own path. Use an **explorer** when the part is evidence for one
deliverable. Do not create two writers for the same file.

## Sizing check

A task is too broad for one worker when any of these is true:

- it needs more than one independent research pass;
- it produces more than one deliverable;
- a worker would need dozens of edits or a second full draft;
- the plan cannot name a single artifact outline and a small set of checks.

Split by dimension or deliverable. If the pieces would all write the same
file, keep one writer and add explorer questions instead.

## Output

The next useful child items, their dependency order, a short sizing note, and
the role split: which questions go to explorers, which deliverable goes to a
worker, and what is verified.
