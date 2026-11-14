#!/usr/bin/env bash
# Static/distribution and isolated CLI fixtures; synthetic receipts are not
# independent agent verification and these checks do not test host policy.
set -euo pipefail
kit="$(cd "$(dirname "$0")/.." && pwd)"
base="${TMPDIR:-/tmp/opencode}"
mkdir -p "$base"
fixture="$(mktemp -d "$base/workflow-autonomous.XXXXXX")"
echo "Fixtures retained at $fixture"

check_filesystem_disclosure() {
  local file="$1"
  grep -Fxq 'Path and filesystem restrictions in instructions do not themselves confine arbitrary shell commands or child-agent processes.' "$file"
  grep -Fxq 'The workflow kit and CLI do not enforce those restrictions and are not a filesystem sandbox.' "$file"
  grep -Fxq 'Those restrictions and stricter host rules must still be obeyed.' "$file"
  grep -Fxq 'If hard filesystem confinement is required, separately provide and verify host-enforced controls; the workflow kit does not provide them.' "$file"
}

check_network_contract() {
  python3 - "$1" <<'PY'
import pathlib
import sys

root = pathlib.Path(sys.argv[1])
def text(path):
    return ' '.join((root / path).read_text().split())
def contains(path, *phrases):
    content = text(path)
    for phrase in phrases:
        assert phrase in content, f'{path}: missing {phrase!r}'

contains('AGENTS.md', 'Human-reviewed mode is the default',
         'usable, unexpired authorized run',
         'ordinary public, non-chargeable network access necessary for its goal',
         'fetching public dependencies or assets',
         'without additional user approval solely for network use',
         'Potentially chargeable actions, destructive actions, publication, deployment, private accounts',
         'if cost, privacy, effects, scope or authority is uncertain, stop and seek a decision',
         'stop on denied or unavailable permission', 'never bypass a denial',
         'independently verify the frozen integrated goal',
         'never call automated approval a human attestation')
contains('docs/autonomous-runs.md', 'Human-reviewed operation remains the default',
         'Three.js dependency or asset needed for the goal needs no extra network-only approval',
         'Potentially chargeable actions, destructive actions, publication, deployment, private accounts',
         'if cost, privacy, effects, scope or authority is uncertain, stop and seek a decision',
         'a host network prohibition still applies',
         'stop if permission is denied or unavailable',
         'not a CLI network enforcement mechanism')
contains('.agents/skills/install-workflow/SKILL.md',
         'neither grant host permissions nor enable autonomous mode',
         'Preserve stricter host rules')
for role in ('orchestrator', 'worker', 'verifier'):
    contains(f'.agents/roles/{role}.md', 'AGENTS.md')
PY
}

check_filesystem_disclosure "$kit/AGENTS.md"
check_filesystem_disclosure "$kit/docs/autonomous-runs.md"
check_network_contract "$kit"
grep -Fq 'Human review is the default.' "$kit/README.md"
grep -Fq 'private accounts remain gated' "$kit/README.md"
grep -Fq 'Human-reviewed mode is the default' "$kit/AGENTS.md"
grep -Fq 'Autonomous branch (per run only)' "$kit/AGENTS.md"
grep -Fq 'every recorded run criterion' "$kit/AGENTS.md"
for flag in authorize-autonomous goal criterion action root expires-at max-roots; do
  "$kit/cli/bin/workflow" run --help | grep -Fq -- "--$flag"
  grep -Fq -- "--$flag" "$kit/docs/autonomous-runs.md"
done

"$kit/cli/bin/workflow" install "$fixture/clean" > /dev/null
cmp "$kit/AGENTS.md" "$fixture/clean/AGENTS.md"
cmp "$kit/docs/autonomous-runs.md" "$fixture/clean/docs/autonomous-runs.md"
cmp "$kit/.agents/skills/install-workflow/SKILL.md" "$fixture/clean/.agents/skills/install-workflow/SKILL.md"
check_network_contract "$fixture/clean"
check_filesystem_disclosure "$fixture/clean/AGENTS.md"
check_filesystem_disclosure "$fixture/clean/docs/autonomous-runs.md"
cmp "$kit/.agents/roles/orchestrator.md" "$fixture/clean/.agents/roles/orchestrator.md"
test -f "$fixture/clean/opencode.json"
printf '\nHost rule survives refresh: public network access is prohibited.\n' >> "$fixture/clean/AGENTS.md"
python3 - "$fixture/clean/AGENTS.md" <<'PY'
import pathlib, sys
path = pathlib.Path(sys.argv[1])
content = path.read_text()
old = 'ordinary public,\nnon-chargeable network access'
assert old in content
path.write_text(content.replace(old, 'STALE NETWORK RULE', 1))
PY
cp "$fixture/clean/opencode.json" "$fixture/config-before"
"$kit/cli/bin/workflow" install "$fixture/clean" --force > /dev/null
check_filesystem_disclosure "$fixture/clean/AGENTS.md"
check_filesystem_disclosure "$fixture/clean/docs/autonomous-runs.md"
check_network_contract "$fixture/clean"
! grep -Fq 'STALE NETWORK RULE' "$fixture/clean/AGENTS.md"
grep -Fq 'Host rule survives refresh: public network access is prohibited.' "$fixture/clean/AGENTS.md"
cmp "$kit/.agents/skills/install-workflow/SKILL.md" "$fixture/clean/.agents/skills/install-workflow/SKILL.md"
for role in orchestrator worker verifier; do
  cmp "$kit/.agents/roles/$role.md" "$fixture/clean/.agents/roles/$role.md"
done
cmp "$fixture/config-before" "$fixture/clean/opencode.json"

mkdir -p "$fixture/host"
printf 'Conflicting host rules require review.\n' > "$fixture/host/AGENTS.md"
printf '{"host":"preserve"}\n' > "$fixture/host/opencode.json"
cp "$fixture/host/AGENTS.md" "$fixture/host-before"
cp "$fixture/host/opencode.json" "$fixture/host-config-before"
"$kit/cli/bin/workflow" install "$fixture/host" --force > /dev/null
cmp "$fixture/host-before" "$fixture/host/AGENTS.md"
cmp "$fixture/host-config-before" "$fixture/host/opencode.json"

# These repository fixtures use isolated projects and synthetic verifier receipts.
# They exercise real CLI subprocesses and command/domain matrices, not live roles.
cd "$kit/cli"
vendor/bin/phpunit --filter 'RunCommandTest|RunApprovalCommandTest|RunDesignMatrixTest|AutomatedEventsTest|RunAcceptanceMatrixTest|InstallScaffoldTest|InstallHandoffTest'
echo 'Static, installed-copy and synthetic CLI checks passed; no live host tested.'
