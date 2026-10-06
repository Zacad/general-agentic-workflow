---
name: diagnose-issue
description: Establish and compare plausible causes of an existing software failure. Use when an issue needs reproduction or root-cause evidence before a fix.
---

# Diagnose an issue

**Inputs:** reported symptom, unit boundaries, codebase map, available reproduction and logs, attempt path.

**Procedure:**
1. Establish actual versus expected behavior with the least costly reliable reproduction. If reproduction is unavailable, record the observable evidence and its limits.
2. Trace the relevant path and compare at least two plausible hypotheses using discriminating observations or focused checks. Check whether existing changes explain the symptom.
3. Record the most supported cause, residual uncertainty, and the smallest fix boundary; recommend acceptance criteria that would catch a regression. Do not modify product code as an investigator.

**Output:** reproduction steps/results, tested hypotheses, source references and fix-scope recommendation in the attempt report; compact receipt per `.agents/workflow/protocol.md`.

**Stop/escalate:** can't establish behavior, required environment unavailable, diagnosis implies changed approved scope or inherited decision. Label unknowns rather than present a guess as a fact.
