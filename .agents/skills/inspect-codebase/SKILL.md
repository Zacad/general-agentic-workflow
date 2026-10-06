---
name: inspect-codebase
description: Map relevant code, conventions, tests, and boundaries for a bounded software unit. Use during an existing-code issue or before approved code changes.
---

# Inspect a codebase

**Inputs:** issue/unit question, relevant codebase, existing instructions, assigned attempt path.

**Procedure:**
1. Read applicable repository rules and inspect the working tree for concurrent changes. Trace only the code paths relevant to the question, then locate adjacent tests and established patterns.
2. Identify behavior, interfaces, dependencies, change boundaries and reliable commands for focused checks. Verify claims from source, not names alone.
3. Record file/line references, unknowns, and likely affected areas; distinguish evidence from hypotheses. Avoid a repo-wide summary unless the bounded question needs it.

**Output:** source-based map and suggested next question in the report; compact receipt per `.agents/workflow/protocol.md`.

**Stop/escalate:** unclear ownership, conflicting project rules, or material changes outside scope. Investigators modify only their attempt artifacts.
