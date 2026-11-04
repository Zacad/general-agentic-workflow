---
name: code-architecture
description: Choose a software design and testing strategy from traced requirements, comparing feasible alternatives rather than defaulting to a pattern.
---

# Code architecture

Two routes: **route 1 — architecture design** for a new product before code
exists, and **route 2 — task solution design** for a bounded change to
existing code. Both end in a user-approved design before implementation.

**Inputs:** traced requirements and scenarios, existing code and tests,
constraints, and the artifact path for the comparison.

## Procedure

1. Trace each requirement and quality driver to its source and current
   agreement. Name the three most important characteristics and why.
2. **Route 1 — new product:** compare complete viable shapes — client, state,
   storage, API, identity, hosting, runtime, build, recovery — for the same
   flows. Include a smaller reversible baseline.
3. **Route 2 — existing code:** trace actual callers, data flow, error paths,
   dependencies and tests. Compare the smallest change, an observed seam, and
   an abstraction only where real consumers and changing rules earn it.
   Before comparing changes, check boundedness: independently acceptable
   outcomes inside one task default to a subtask split (decompose-work),
   not one monolithic design.
4. Compare approaches against the same requirements and characteristics.
   For each: best case, failure surfaces, cost, migration burden and
   reversibility.
5. For material behavior, compare at least two test strategies for the same
   normal, error and recovery scenarios: confidence, setup and maintenance
   cost, feedback speed, determinism and prerequisites.
6. Recommend one approach, state its limits, and name the signal that would
   reverse the choice.
7. **Author → verify → present → gate:** on a workflow item this is route 2 and
   the author is a **design worker**, not the orchestrator. The design worker
   produces the comparison and recommendation as an artifact and returns it; it
   does not approve its own design. The orchestrator records the Design with
   `workflow design <id> --file ...` (no `--approve`), an independent verifier
   checks the design, and only then does the orchestrator present it to the user
   and discuss it. On the user's approval the orchestrator re-records with
   `workflow design <id> --approve` in the same mutation that writes the approved
   `## Design`; the CLI refuses the implementation worker dispatch without it, and
   a material design change requires re-approval.

## Output

A concise comparison artifact with a recommendation, presented to the user
for approval before implementation. Do not provision services, change
hosting or commit to paid resources from a draft.
