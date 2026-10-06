---
name: implement-approved-change
description: Make a bounded software change following an approved unit and local repository conventions. Use only when a code change has a matching checkpoint.
---

# Implement an approved change

**Inputs:** approved unit/revision and criteria, codebase findings, inherited decisions, owned source/test paths, attempt path.

**Procedure:**
1. Confirm matching checkpoint, inspect current edits and applicable repo guidance; avoid overwriting concurrent user or worker changes.
2. Change the smallest coherent code path within scope. Add or adjust meaningful regression coverage when it tests behavior rather than mirroring the implementation; use existing project practices instead of imposing TDD universally.
3. Run focused checks suitable for the change, document results, and leave a stable output/attempt for the independent verifier. Preserve unrelated files and keep the exact changed-path list in the report.

**Output:** bounded code/test change and attempt report with checks, limitations and paths; compact receipt per `.agents/workflow/protocol.md`.

**Stop/escalate:** failing prerequisites, unrelated preexisting changes that would be overwritten, material design change, or unmet acceptance criteria requiring broader scope.
