#!/usr/bin/env bash
# Exercises the design-worker solution-design flow against the real workflow CLI
# across several scenarios. Throwaway project; nothing here mutates the kit.
# Run from anywhere: bash scripts/design-worker-scenarios.sh
set -uo pipefail

kit="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
export PATH="$kit/cli/bin:$PATH"
mkdir -p "${TMPDIR:-/tmp/opencode}"
proj="$(mktemp -d "${TMPDIR:-/tmp/opencode}/dw-scenario.XXXXXX")"
mkdir -p "$proj/src"
wf() { workflow --project "$proj" "$@"; }

pass=0; fail=0
ok()   { echo "  PASS: $1"; pass=$((pass+1)); }
bad()  { echo "  FAIL: $1"; fail=$((fail+1)); }
# want_ok <desc> <cmd...>
want_ok()  { local d="$1"; shift; if "$@" >/dev/null 2>&1; then ok "$d"; else bad "$d (expected success)"; fi; }
# want_refused <desc> <needle> <cmd...>  — expects non-zero exit AND needle in output
want_refused() { local d="$1" needle="$2"; shift 2; local out rc
  out="$("$@" 2>&1)"; rc=$?
  if [ "$rc" -ne 0 ] && printf '%s' "$out" | grep -q -- "$needle"; then ok "$d"; else bad "$d (rc=$rc; want refusal + '$needle')"; fi; }
# want_contains <desc> <needle> <cmd...>  — expects zero exit AND needle in output
want_contains() { local d="$1" needle="$2"; shift 2; local out rc
  out="$("$@" 2>&1)"; rc=$?
  if [ "$rc" -eq 0 ] && printf '%s' "$out" | grep -q -- "$needle"; then ok "$d"; else bad "$d (rc=$rc; want '$needle')"; fi; }

echo "throwaway project: $proj"
wf init --name "Design worker scenarios" >/dev/null
cd "$proj"

# --------------------------------------------------------------------------
echo; echo "S1 happy path: design worker -> record -> design verifier -> user approval -> implement"
wf new "Ship widget" --kind task --standalone-reason "Scenario needs one task" \
  --outcome "Widget ships behind a flag." --owns src/widget.php >/dev/null
wf set T-001 'scope_in=Ship the widget' 'scope_out=Rollout tooling' \
  'acceptance=Flagged widget builds and a smoke check passes' >/dev/null
wf plan T-001 --text "Fixed scope; smoke check; stop when build passes." >/dev/null
wf approve T-001 --by user >/dev/null   # todo, no design approval yet

# Before any design approval the *implementation* worker packet must be refused.
want_refused "worker brief refused pre-design (design not approved)" "design not approved" \
  wf brief T-001 --for worker
# But a verifier packet (design verifier) is NOT gated.
want_ok "verifier brief allowed pre-design (design verifier)" wf brief T-001 --for verifier

# The design worker authors a design file and the orchestrator records it unapproved.
mkdir -p "$proj/.workflow/items/T-001/outputs"
cat > "$proj/.workflow/items/T-001/outputs/design.md" <<'EOF'
Ship the widget behind an existing feature flag; reuse the build script; add a smoke check.
No subtasks: one independently acceptable outcome. Open risk: flag default value.
EOF
want_ok "orchestrator records authored design (no approval)" \
  wf design T-001 --file .workflow/items/T-001/outputs/design.md --phase design

# Still refused until the user approves.
want_refused "worker brief still refused after record, before approval" "design not approved" \
  wf brief T-001 --for worker

# User approves; orchestrator records design approval in the same mutation.
want_ok "design approval recorded with confirmation" \
  wf design T-001 --file .workflow/items/T-001/outputs/design.md --approve --by user --confirmation "Approve this design"

# Now the implementation worker packet opens, and readiness appears.
want_ok "worker brief allowed after design approval" wf brief T-001 --for worker
want_contains "ready lists T-001 after approval" "T-001" wf ready
wf set T-001 status=in-progress phase=implement >/dev/null
printf 'flag: widget\n' > "$proj/src/widget.php"
wf result T-001 --role worker --status done --finding "Widget shipped behind flag." --artifacts src/widget.php >/dev/null
wf set T-001 phase=verify >/dev/null
wf result T-001 --role verifier --status done --finding "Build + smoke pass." --artifacts src/widget.php >/dev/null
want_ok "close succeeds with current verifier proof" wf close T-001

# --------------------------------------------------------------------------
echo; echo "S2 stale design: post-approval design rewrite re-locks the implementation gate"
wf new "Patch logger" --kind task --standalone-reason "Scenario needs one task" \
  --outcome "Logger rotates daily." --owns src/logger.php >/dev/null
wf set T-002 'scope_in=Daily rotation' 'scope_out=Retention UI' \
  'acceptance=Rotation runs on schedule' >/dev/null
wf plan T-002 --text "Reuse cron; stop when rotation verified." >/dev/null
wf approve T-002 --by user >/dev/null
mkdir -p "$proj/.workflow/items/T-002/outputs"
printf 'Rotate via cron; no subtasks: single outcome.\n' > "$proj/.workflow/items/T-002/outputs/design.md"
wf design T-002 --file .workflow/items/T-002/outputs/design.md --approve --by user --confirmation "Approve this design" >/dev/null
want_ok "worker brief allowed while design approval current" wf brief T-002 --for worker
# A material rewrite (content only, no --approve) stales the approval.
printf 'Rotate via logrotate; changed approach.\n' > "$proj/.workflow/items/T-002/outputs/design.md"
wf design T-002 --file .workflow/items/T-002/outputs/design.md >/dev/null
want_refused "worker brief refused after stale design" "stale design approval" \
  wf brief T-002 --for worker
want_ok "re-approve clears the stale gate" \
  wf design T-002 --file .workflow/items/T-002/outputs/design.md --approve --by user --confirmation "Approve revised design"
wf close T-002 --status cancelled --reason "Scenario cleanup" >/dev/null

# --------------------------------------------------------------------------
echo; echo "S3 needs-decision: design worker asks for exploration; explorer is never gated"
wf new "Add caching" --kind task --standalone-reason "Scenario needs one task" \
  --outcome "Reads hit a cache." --owns src/cache.php >/dev/null
wf set T-003 'scope_in=Add cache layer' 'scope_out=Invalidation UI' \
  'acceptance=P95 read latency drops' >/dev/null
wf plan T-003 --text "Need current store shape; explore first." >/dev/null
wf approve T-003 --by user >/dev/null
# Design worker finds evidence insufficient and requests bounded exploration.
wf result T-003 --role worker --status needs-decision \
  --finding "Need to know the current read path before designing." \
  --decision "Which store the reads go through?" >/dev/null
# Explorer packet is allowed even with no design approval.
want_ok "explorer brief allowed with no design approval" wf brief T-003 --for explorer
mkdir -p "$proj/.workflow/items/T-003/outputs"
printf 'Reads go through Store.get(); cache there.\n' > "$proj/.workflow/items/T-003/outputs/exploration.md"
wf result T-003 --role explorer --status done --finding "Store.get is the seam." --artifacts .workflow/items/T-003/outputs/exploration.md >/dev/null
printf 'Cache in front of Store.get; no subtasks.\n' > "$proj/.workflow/items/T-003/outputs/design.md"
want_ok "design recorded after exploration evidence" \
  wf design T-003 --file .workflow/items/T-003/outputs/design.md --approve --by user --confirmation "Approve cache design"
wf close T-003 --status cancelled --reason "Scenario cleanup" >/dev/null

# --------------------------------------------------------------------------
echo; echo "S4 too broad: design worker proposes a split; orchestrator frames a subtask (parent design is the map)"
wf new "Build report" --kind task --standalone-reason "Scenario needs one task" \
  --outcome "Report is generated." --owns src/report.php >/dev/null
wf set T-004 'scope_in=Fetch, compose, render a report' 'scope_out=Export formats' \
  'acceptance=Report renders with real data' >/dev/null
wf plan T-004 --text "Compose report; decompose if fetch/render are independent." >/dev/null
wf approve T-004 --by user >/dev/null
# Design worker signals the task bundles independently acceptable outcomes.
mkdir -p "$proj/.workflow/items/T-004/outputs"
printf 'Task bundles fetch + render: recommend split into two subtasks.\n' > "$proj/.workflow/items/T-004/outputs/design.md"
wf result T-004 --role worker --status needs-decision \
  --finding "Two independently acceptable outcomes under one writer." \
  --decision "Split into fetch and render subtasks?" >/dev/null
want_ok "orchestrator frames a subtask under the task" \
  wf new "Fetch data" --kind subtask --parent T-004 --outcome "Data is fetched." --owns src/fetch.php
# Subtask still needs its own design approval before its worker packet opens.
wf set S-001 'scope_in=Fetch raw rows' 'scope_out=Rendering' 'acceptance=Rows returned' >/dev/null
wf approve S-001 --by user >/dev/null
want_refused "subtask worker brief refused without its design approval" "design not approved" \
  wf brief S-001 --for worker

# --------------------------------------------------------------------------
echo; echo "Final validate:"
wf validate 2>&1 | tail -3

echo; echo "==== $pass passed, $fail failed ===="
echo "project left at: $proj"
[ "$fail" -eq 0 ]
