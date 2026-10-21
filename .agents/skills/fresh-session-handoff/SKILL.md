---
name: fresh-session-handoff
description: Resume or hand off work from the records alone, with current authority, artifacts and one next action.
---

# Fresh-session handoff

**Inputs:** project root, root `AGENTS.md`, workflow binary and assigned role.

## Procedure

1. Load the canonical root rules; surface missing/conflicting host merges.
   Run `workflow status`, then `ready` only if choosing work is unclear.
2. Run the selected item's explicit role brief. Confirm identity, scope approval,
   dependencies/ownership, Plan/Design, parent Design and relevant artifact paths.
   Do not load full reports/history for a routine resume.
3. Non-verifier roles may use the last receipt/open decision. Verifiers derive
   expectations without producer conclusions. Exact epic frame review uses
   `workflow review <epic>`, not a handoff summary as approval.
4. If asked for a handoff, state workspace, item, current authority, named
   target, limits and one bounded next action. Completed-history work follows
   AGENTS' explicit follow-up policy.

## Output

A resumed session or short pasteable snapshot. A handoff is neither approval
nor proof; the recipient checks current records before acting.
