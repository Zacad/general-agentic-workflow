---
name: decompose-work
description: Split an item into the next useful children only where each part has an independently acceptable outcome.
---

# Decompose work

**Inputs:** current scope/acceptance, exploration evidence, dependencies and
candidate owned paths. Follow root `AGENTS.md` for staged approval and sizing
policy; use this procedure to choose boundaries, not to add process gates.

## Procedure

1. Compare plausible boundaries, including **no split**. A child needs an
   observable outcome that can be accepted/rejected without finishing siblings,
   its own scope/path and a meaningful failure boundary. Files, phases and
   meetings are not independently useful outcomes by themselves. Apply the
   boundedness test before drafting children: can a verifier check this child's
   acceptance in one focused worker-and-verifier pass, and does it bundle more
   than one independently acceptable outcome? If either fails, propose the
   finer split or record the explicit broad-item reason in the rationale —
   pipeline nodes are steps of one deliverable; separately acceptable modules
   (delivery, CLI, history) are each deliverables. An over-bundled task
   defaults to subtasks; only the explicit broad-item reason keeps it whole.
2. Separate missing evidence from deliverables. Assign one bounded explorer
   question per independent dimension, with sources, stop condition and a
   repository-relative `.workflow/items/<ID>/outputs/` target. Discovery may run
   on the epic; propose a discovery child only if its answer is useful as its
   own deliverable. Do not invent children merely to host role runs.
3. From evidence, place the scope map in **epic Design only**: candidate children,
   acceptance coverage, dependencies, ownership, integration and unknowns.
   Child designs reference that map and add only local decisions/checks.
4. Draft the next useful children and use `depends_on` for ordering. Default to
   subtasks: each independently acceptable part under a task becomes a subtask
   with its own scope/path; keep one deliverable under the task itself only
   when no finer split exists or the pieces would write the same paths, and
   record that reason.
5. Record, per child, why these boundaries beat the finer alternative
   considered, why subtasks were/weren't useful, and explicit triggers for
   deferred work. Unexplored is not deferred. Apply AGENTS' sizing signals as
   judgment, not hard thresholds.
6. Present exact new child records after evidence. Later evidence may revise
   the map; identify affected scope changes for review without reapproving
   unchanged records. Do not rewrite completed history to fit a new map.

## Sizing cues

Providing small, clear and well scoped wor items with clear boundries is very **important**.
Default decomposition should be ecpi->tasks->subtasks. Sub tasks help focus on simple goal and parallelize work.
Opt-out from decompose into subtasks must be explicitely justify.
Prefer the smallest child that still has one independently acceptable outcome;
avoid paragraph-sized fragments and multi-question bundles. A child should be
completable in one focused session. These are advisory signals for a sizing
review, not gates:

- more than about three independent questions or one independent research pass;
- more than one deliverable, or an artifact above about 2,000 words;
- an explorer needing more than about ten searches;
- a worker needing dozens of edits or a second full draft;
- inability to name one artifact outline and a small set of checks.

More than one deliverable inside one task is the subtask trigger, not only a
sizing review.

Split by dimension or deliverable. When the pieces would all write the same
file, keep one writer and add explorer questions instead. If an independently
acceptable outcome does not fit the current parent's scope, propose a standalone
task with its required reason rather than inventing a parent. A broad item is
allowed when the reason is explicit.

## Output

One parent Design scope map and exact proposed children, with coverage,
dependency order, decomposition rationale and bounded role assignments.
