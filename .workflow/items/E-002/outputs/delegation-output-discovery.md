# Delegation and coordination-output discovery

## Finding and evidence status

Keep projections small and role-aware; fix literal output and path comparison without changing stored item schemas or approval identity. This synthesis checked the supplied source references, not prior tests. The earlier explorer's in-memory ItemScope run reported four tests/12 assertions; it was not rerun here.

## Checked defects

- `cli/src/Console/Command/BriefCommand.php:52-64` serializes `Item::toArray()` for every role. That includes `last_result` and arbitrary extra metadata (`cli/src/Domain/Item.php:110-136`). The text branch omits the receipt for verifiers at `BriefCommand.php:163-174`; JSON does not.
- `cli/src/Console/Command/BaseCommand.php:34-36` sends JSON through Console's normal formatter. Strings containing Console-style tags can be altered even though JSON encoding succeeded. JSON requires `OutputInterface::OUTPUT_RAW`.
- `ReviewCommand.php:132-165` and `ApproveCommand.php:56-73` interpolate user values and join lists through `cli/src/Support/Text.php:24-26`. Distinct arrays can render identically; markup, multiline and empty values are not represented exactly. `ApproveCommand.php:37-54` captures only hash/status under mutation and reloads scope afterward, so displayed values need not be the values approved under the lock.
- `ReviewCommand.php:21-25` lacks JSON despite the public read contract (`README.md:154`, `.workflow/PRD.md:139`). Adding JSON is bounded if it reuses the same exact scope packet.
- `StatusCommand.php:87-150` retains hard section caps even with `--all`. Its JSON branch at lines 61-80 never includes terminal items. Default text caps are useful and must stay.
- `ItemValidator.php:449-460` normalizes slash direction and boundary slashes only. Equivalent `./`, repeated separators and dot segments can evade overlap detection. `cli/src/Read/ProjectView.php:132-139` already calls this shared comparator.

## Selected deliverables

**delegation:** Build a small allowlisted packet inside BriefCommand and use it for text and JSON. Preserve identity/scope/acceptance, approval, Plan/Design, parent design, dependencies, decisions, artifacts and deliverable paths. Include the last receipt only for appropriate non-verifier roles. Verifiers must never receive receipt/progress/unknown metadata through either branch. Keep persistence unchanged; a generic redaction/serialization framework is unnecessary. Use raw JSON output centrally. New explorer artifact targets use repository-relative `.workflow/items/<ID>/outputs/` paths.

This is field-level blinding, not semantic censorship of approved design or decisions. A user can put conclusions in narrative content; the CLI cannot promise to detect that. Workers still freeze targets and verifiers independently derive expectations.

**scope-output:** Use one small exact scope projection and literal renderer shared by Review and Approve. Distinguish list boundaries, empty arrays, empty strings, null and multiline strings; escaped JSON-valued fields or equivalently unambiguous literal blocks are suitable. Preserve Console tag text under decorated and undecorated output. Include kind, parent, title, outcome, scope-in/out, acceptance and dependencies, with owned paths alongside rather than added to the approval hash. Preserve `Item.php:139-161` hash semantics. Approve returns actual scope plus hash from the same locked mutation for text and JSON; Review JSON projects the same tree and Plan/Design information. Do not teach Item persistence about presentation.

**coordination:** Treat complete coordination visibility as one projection outcome: bounded defaults plus exhaustive `status --all`, and matching overlap findings in ready/validate. `--all` removes every section cap and includes done/cancelled JSON groups. Preserve existing default JSON behavior rather than inventing a new pagination contract.

Ownership matching is lexical for literal project-relative files/directories, including future paths. Normalize slash direction, repeated separators and dot segments; distinguish project root from an absent path. Compare equality/ancestry on component boundaries, so `src/a` does not overlap `src/ab`. No globs, realpath, symlink resolution, existence checks or stored-path rewriting. Retain dependency-aware ownership warnings.

## Integration and verification

Delegation owns BaseCommand/BriefCommand; scope-output depends on delegation for raw JSON and on codec for reliable literal fixtures. Coordination follows lifecycle with the same writer for shared ItemValidator; ProjectView keeps using one comparator. All use new focused test files, not concurrent edits to CommandTest.

Tests should seed producer-claim sentinels in receipts/progress/unknown metadata, compare all roles and both formats, round-trip exact scope values including tags and list boundaries, and generate more than each status cap in every section. Pure path tables catch lexical boundaries cheaply; temporary-project ready/validate tests prove shared integration. Full CLI snapshots alone are brittle and insufficient for privacy/exactness checks. No new readiness, approval or phase gate is introduced.
