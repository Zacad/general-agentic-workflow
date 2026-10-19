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

## Output

The next useful child items, their dependency order, and a short sizing note.
