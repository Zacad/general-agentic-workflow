---
name: fresh-session-handoff
description: Resume or hand off work from the records alone, with current authority, artifacts and one next action.
---

# Fresh-session handoff

**Inputs:** the project root and the `workflow` binary.

## Procedure

1. Run `workflow status`. If the next item is unclear, run `workflow ready`.
2. Open the selected item's `workflow brief <id>`.
3. Confirm:
   - the item ID, status, phase and parent;
   - whether approval is current (and re-approve only if scope changed);
   - dependency state and owned paths;
   - the artifacts and upstream artifacts that matter now;
   - the last receipt and any open decision.
4. Record a handoff only when asked: workspace, item, approval scope, current
   artifacts, limits and one bounded next action.
5. A handoff is a snapshot, not approval or proof. The recipient re-reads the
   brief and checks current state before acting.

## Output

Either a resumed work session or a short pasteable handoff. Do not load full
reports or history for a routine resume; the brief is the handoff.
