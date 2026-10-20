---
name: verify-outcome
description: Independently check a frozen outcome against its acceptance criteria, including executable checks and honest limits.
---

# Verify an outcome

**Inputs:** the item's verifier brief (`workflow brief <id> --for verifier`:
acceptance criteria, scope, design, decisions), the frozen artifact paths, and
the verifier's own result artifact.

## Procedure

1. Use the verifier brief, which omits the producer claim. Before reading any
   worker conclusion, derive expected behavior from the acceptance criteria,
   design and primary sources.
2. Confirm the target is frozen and matches the item. A changing target cannot
   be verified.
3. Judge both the acceptance criteria and the design. If the design is
   missing, record that as a limitation; do not invent one.
4. State whether this is a full verification or a targeted recheck. A recheck
   of named fixes does not prove the rest of the artifact.
5. Check the artifact itself, not the existence of a report. Run the commands
   or inspections that could reveal failure:
   - normal, error, recovery and boundary cases;
   - the affected regression surface, not the whole world;
   - for code: execute it with the project's real runtime and dependencies;
   - for prose: check exact facts, quantities, dates and sources;
   - for UI: rendered states, contrast, focus, keyboard and reflow where
     claimed.
6. For each criterion record: expected, observed, method, environment,
   result (`pass`, `fail`, `unrun`, `blocked`) and limits.
7. Distinguish a wrong result from an unrun check. Missing tools or
   prerequisites leave the criterion unverified; do not install or repair.
8. Preserve the failed target. A repair needs a new named target and a new
   check.
9. Return a verifier receipt. The orchestrator records it with
   `--role verifier` before closing the item.

## Output

A per-criterion result artifact and a receipt. The receipt carries only the
overall status, a short finding, artifact paths, any decision and the next
action.
