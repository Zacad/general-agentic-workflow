# General agentic workflow

A small, Markdown-first kit for coordinating LLM agents. Item records preserve
scope, decisions and evidence; a project-local CLI provides compact delegation
views, dependency/ownership checks and a generated board. Explorers investigate,
workers deliver, and independent verifiers check frozen outcomes.

[AGENTS.md](AGENTS.md) is the operating contract. Roles load it explicitly;
skills supply methods rather than another protocol. See the
[command contract](.agents/skills/workflow-cli/SKILL.md) and
[installation guide](docs/install.md).

## Quickstart

Requires **PHP >=8.4.1, Composer 2 and iconv**. From `cli/`, run
`composer install --no-dev` for runtime use; mbstring is supplied by a polyfill.
For development prerequisites and the full install, see below.

Run this walkthrough from the kit root. It creates an isolated project outside
the kit and retains its history. Approval comments are real human checkpoints.
Worker/verifier sections demonstrate separate role runs: in agent use, delegate
them independently and record only receipts from checks actually performed.

<!-- protocol-example: quickstart -->
```sh
kit="$PWD"
export PATH="$kit/cli/bin:$PATH"
mkdir -p "${TMPDIR:-/tmp/opencode}"
demo="$(mktemp -d "${TMPDIR:-/tmp/opencode}/workflow-demo.XXXXXX")"
workflow --project "$demo" init --name "Greeting demo"
cd "$demo"

# Record product truth before framing the first epic:
cat > .workflow/PRD.md <<'EOF'
# Greeting demo — product requirements

Purpose: demo the record flow with one fixed greeting note.
Goal: the note contains exactly hello and a newline.
Non-goals: application code, templating.
EOF

workflow new "Greeting example" --kind epic \
  --outcome "One greeting note is delivered and independently checked."
workflow set E-001 'scope_in=One greeting note' 'scope_out=Application code' \
  'acceptance=The note contains exactly hello and a newline'
workflow plan E-001 --text "Check the requested literal greeting; no exploration needed for this fixed example."
workflow review E-001
# After the user confirms this exact epic/discovery scope:
workflow approve E-001 --by user

workflow new "Write greeting" --kind task --parent E-001 \
  --outcome "A greeting note contains exactly hello and a newline." \
  --owns .workflow/items/T-001/outputs/greeting.txt
workflow set T-001 'scope_in=The greeting note' 'scope_out=Other files' \
  'acceptance=The note contains exactly hello and a newline'
workflow plan T-001 --text "Use the fixed greeting requirement; no exploration needed; stop when exact bytes pass."
# Record system truth when recording the epic Design:
cat > .workflow/Architecture.md <<'EOF'
# Greeting demo — architecture

The CLI owns the records; the owned output note is the sole deliverable.
EOF
workflow design E-001 --text "Scope map: T-001 owns .workflow/items/T-001/outputs/greeting.txt, covers the sole criterion, has no dependencies; integrate by checking exact bytes. One task, no subtasks: one independently acceptable note. No open risks."
workflow design T-001 --text "Follow parent Design; write hello plus newline at the owned path; check with cmp. No open risks." --phase design
workflow review E-001
# After the user confirms the exact new child scope (epic scope is unchanged):
workflow approve T-001 --by user
workflow ready

# Coordinator starts work, then the worker reads its brief and writes the note:
workflow set T-001 status=in-progress phase=implement
workflow brief T-001 --for worker
mkdir -p .workflow/items/T-001/outputs
printf 'hello\n' > .workflow/items/T-001/outputs/greeting.txt
# Coordinator records the worker's returned receipt:
workflow result T-001 --status done --finding "Greeting note written." \
  --artifacts .workflow/items/T-001/outputs/greeting.txt --next "Independent verification"

# Coordinator dispatches verification; a separate verifier reads and checks:
workflow set T-001 phase=verify
workflow brief T-001 --for verifier
printf 'hello\n' | cmp - .workflow/items/T-001/outputs/greeting.txt
# Coordinator records the actual verifier receipt, then closes:
workflow result T-001 --role verifier --status done --finding "cmp confirmed exact greeting bytes." \
  --artifacts .workflow/items/T-001/outputs/greeting.txt --next close
workflow close T-001

# Coordinator dispatches an epic integration verifier for child completion/note:
workflow set E-001 status=in-progress phase=verify
workflow brief E-001 --for verifier
workflow status --all
printf 'hello\n' | cmp - .workflow/items/T-001/outputs/greeting.txt
# Coordinator records that verifier's receipt and closes the epic:
workflow result E-001 --role verifier --status done --finding "Child completion and integrated greeting checked." \
  --artifacts .workflow/items/T-001/outputs/greeting.txt --next close
workflow close E-001
workflow validate
printf 'Demo retained at %s\n' "$demo"
```

Commands find the nearest `.workflow/`; `--project /absolute/path` selects a
different root. This kit's own records already exist: do not run the example
against them. The CLI does not authenticate role independence or human consent.

Scaffolding does not register agents. OpenCode registration is a required,
manual and unverified follow-up; see the [installation guide](docs/install.md)
for clean-project copy, existing-config merge, reload and catalog checks.

## Records and limits

`.workflow/PRD.md` holds requirements, `Architecture.md` holds design, and each
`items/<ID>/item.md` holds YAML state and narrative. Optional `outputs/` holds
real evidence/deliverables; `board.html` is derived. No database or per-attempt
project copies are needed.

Approval tracks scope only. Verification is separately invalidated by tracked
target changes. Completed records stay historical; new work gets an explicitly
approved follow-up. Same-path artifact edits rely on freezing and honest receipts,
not content hashes. Installation is scaffold copying and manual merging, not a
managed updater. See [historical design notes](docs/design-notes.md) for context.

## Development

Run full `composer install` in `cli/`, then `composer test`. Development needs
native **dom, filter, json, libxml, mbstring, phar, tokenizer and xmlwriter**
extensions in addition to runtime requirements. Composer's platform checks are
authoritative. Tests use isolated temporary projects; they do not prove real LLM
startup or role independence.
