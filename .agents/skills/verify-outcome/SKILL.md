---
name: verify-outcome
description: Independently check a frozen outcome against its acceptance criteria, including executable checks and honest limits.
---

# Verify an outcome

**Inputs:** root `AGENTS.md`, `workflow brief <id> --for verifier`, frozen
target paths and an assigned verification-artifact path.

## Procedure

1. Derive expectations from acceptance, scope, Design and primary sources
   before reading producer conclusions. Confirm target identity and freeze;
   field-based packet blinding cannot remove conclusions in approved narrative.
2. State full verification or targeted recheck. Check both criteria and Design;
   report missing design as a limit, not an invitation to invent requirements.
3. Inspect actual artifacts and run failure-revealing checks: normal, error,
   recovery, boundary and affected regressions. Use the real runtime for code;
   check exact claims/sources in prose and rendered accessibility states for UI.
   A report's existence or green tests for a different criterion are not proof.
4. Record per criterion: expected, observed, method, environment, result
   (`pass`, `fail`, `unrun`, `blocked`) and limits. Distinguish unavailable checks
   from failures; do not install, repair or mutate the target to make it pass.
5. Preserve failed targets. A repaired open target needs a new check; material
   completed-item work needs the explicit follow-up defined in AGENTS. No content
   fingerprint machinery guarantees a same-path freeze.
6. Return the actual verdict. The coordinator records the verifier receipt
   before a separate close; a deliberate force exception is never your success.

## Output

Per-criterion evidence and the fixed AGENTS receipt. Do not return `done` with
failed or materially unverified acceptance criteria.
