---
name: requirements
description: Discover and review what software or nonsoftware work must achieve, including scenarios, constraints, failure cases and open questions.
---

# Requirements

Use for substantial or materially changing work. A tiny, exact change needs
only a concise sourced acceptance statement.

**Inputs:** the actual goal and request, current reviewed material, applicable
decisions, and the artifact path where the review will live.

## Procedure

1. Recover the goal in the requester's terms. Compare consequential
   interpretations and ask the owner to resolve them.
2. Trace each material statement to its source and status: requested, sourced
   constraint, proposed, agreed, open, declined or excluded. Preserve
   conflicting sources.
3. Cover the dimensions the work actually has:
   - **software:** actors and permissions, entry and preconditions, core
     actions and visible results, error/cancel/abandon, retries and duplicate
     effects, saves/history/recovery, identity, privacy, accessibility,
     devices, latency, durability, operations;
   - **nonsoftware** (course, guide, catalogue, trip): audience, purpose,
     content depth, examples, comparisons, constraints, exceptions,
     actionable detail, sources and freshness.
4. Make concrete examples: starting state → action or exception → observable
   result. Use realistic numbers, dates and quantities.
5. Separate product behavior from storage, runtime and hosting choices.
   Surface visual or brand context early when the work has an interface.
6. Present the exact version for review and retain the actual response.
   Adequacy, presentation, agreement and readiness are different things.
7. Trace each material requirement to an outcome, a failure-detecting check,
   and a gap or deferral with an owner. Missing agreement blocks only the
   affected work.

## Output

A concise requirements or review artifact plus a report: what is agreed, what
is open, and the next bounded question.
