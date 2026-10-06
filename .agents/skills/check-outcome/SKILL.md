---
name: check-outcome
description: Independently verify a stable work-unit outcome against its acceptance criteria. Use after production or child preparation, before reconciliation.
---

# Check an outcome

**Inputs:** unit/revision/authorization, criteria and check method, stable named deliverable or attempt, inherited decisions, own attempt path.

**Procedure:**
1. Confirm the target is the named stable output; otherwise request a new snapshot/attempt. Read the brief's intent and criteria independently of the producer's conclusion.
2. Choose checks capable of revealing noncompliance, including relevant edge cases. Inspect primary output and run domain-appropriate checks; for code use `verify-code` when appropriate.
3. Report each criterion as supported, contradicted, or unverified with evidence and limitations. If Git cannot diff untracked files, compare a trustworthy pre-work inventory/hashes with the stable named output and current files, and disclose the attribution limit rather than automatically treating an unavailable Git diff as a scope failure. Do not change the deliverable, brief, or decision.

   Apply the protocol's independent outcome proof contract: actual distinct invocation/report/receipt, exact approved criterion/review/target/variant and own investigator identities; expectations from approved authority and version-primary evidence, independent of producer/implementation oracle. For every material criterion retain failure hypothesis/method, relevant environment/versions/prerequisites, cwd/exact command/input or domain check, expected versus actual observed result/exit/artifact, execution status and evidence support, limits/owner/action. Passing partial inspection or attempt completion does not verify a required unrun criterion. Missing required runtime/framework/browser/result stays pending/blocked and unverified; do not install automatically or substitute static/build/HTTP/mocks/screenshots.

   Route any code-bearing published lesson/example/exercise/solution to `verify-code`, including Markdown, and execute student reconstruction/actual solution edits there. For pure prose/manuscripts inspect exact passages against appropriate primary sources, audience, terminology, scenario and continuity; for trips independently recompute budget/time/transfer feasibility using dated applicable source/quantity/currency facts. Retain concrete comparisons, not merely “reviewed.” Stale extracts prove no live availability, document inspection no executed trip or reader study. Separate technical/executable correctness from learner/user effectiveness; actual appropriate participant/task/method/results support only the applicable approved effectiveness claim. Tiny checks may use terse sourced prose and focused counterexamples, without software stages or a universal suite.

4. Write the separately owned concise user outcome summary required by the assignment: exact target/unit/revision, actual investigator/checker identities, what ran, material status, primary artifact/report links, limits and next action. Return its path with the own report/artifacts in the unchanged compact receipt. The orchestrator must actually read/relay this exact successful artifact before outcome state update; do not claim that presentation happened unless observed. Missing promised summary/report/result requires distinct worker recovery or a blocker, never an expanded receipt or hidden PASS. Reconciliation remains separate; investigators/verifiers only write their own attempts.

**Output:** verifier-owned report with methods, results and gaps; compact receipt per `.agents/workflow/protocol.md`. Reconciliation—not the verifier—updates unit status.

Also deliver the assigned own result artifacts and separate concise user outcome summary under the protocol's proof/visibility contract; a `done` check receipt can honestly report contradicted/unverified criteria.

**Stop/escalate:** authorization mismatch, unstable output, check cannot be performed, or consequential conflict; report `blocked`/`needs-decision` rather than guessing success.
