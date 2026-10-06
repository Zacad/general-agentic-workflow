# V1 trials and acceptance

First perform the three walkthroughs below with proposed briefs and decisions. Then run small **live agent** sessions in disposable workspaces using available models, after [manual installation](install.md) and deliberate checkpoints. Do not fabricate a user approval during a live trial; a human explicitly grants it. Keep trial work out of reference projects and compare behavior to `.agents/workflow/protocol.md`. Record the model and harness version, the worker receipt, attempt/report paths, actual write paths, and each deviation; revise the smallest rule or skill that fails. Source-only fixtures live in `fixtures/`; use the [lookup](install.md#oldnew-source-path-lookup) for older literal fixture paths.

## New software endeavor

Ask for a tiny new tool with one checkable first outcome. Start at root U-0001: permit bounded investigation and draft its brief without an approval; stop before producing the tool. Approve U-0001@revision-1. Have a planner prepare child U-0002, but do not enter U-0002 until the user explicitly approves that ID/revision in U-0001's checkpoint section. Produce one leaf deliverable, verify its stable named attempt, reconcile parent criteria separately. In a fresh session, identify the current state from briefs/checkpoints without the previous chat.

## Existing-code issue

Use a disposable small codebase with an observable defect, a relevant test, and a local `AGENTS.md` rule. Frame a bounded issue root; inspect and diagnose before choosing a fix. Check source references and competing causes in the investigator report. After approval, make a bounded change and have another worker verify it; a failed regression check must return to reconciliation, not mark the unit verified. Change a material acceptance criterion: the old checkpoint must not authorize advancing that new revision. Leave an unrelated root unaffected.

## Non-software endeavor

Frame a book chapter (or trip segment) with audience, boundaries and an observable review method. Prepare only one useful child, not the entire book/trip. Introduce a changed inherited constraint—for example a new audience or trip date. Affected work returns to its parent decision; an unaffected child can continue. Check the actual prose/plan against its criteria rather than pretending software tests apply. Demonstrate a narrowly approved exception that does not erase the general decision elsewhere.

## Pass conditions across the three

- No worker enters a child lacking a matching parent checkpoint; root framing before approval is limited to drafting.
- No worker treats tool permission approval as scope approval or silently overrides a consequential inherited choice. Material revision invalidates the old unit authorization without unnecessarily pausing unrelated units.
- Investigators and verifiers write only their own attempt artifacts. Each worker final message is a bounded receipt, with substantive detail in a real report path. If this repeatedly fails, investigate a validating adapter after documenting the failures.
- Verifier targets a stable named output, records checks that could fail and their limits; reconciliation uses this evidence before marking a unit or its parent verified.
- Fresh-session recovery works from briefs, checkpoints, applicable decisions and referenced evidence; model selection and skill discovery are observable. Parallel producers, if tried, own disjoint output paths.

An installed example is a real trial only when agent behavior, approvals, and outputs have been observed. The scenarios above are also usable as manual contract walkthroughs without consuming model tokens.

## Clear communication across all five roles

Check [clear-communication](../../skills/clear-communication/SKILL.md) against two independent requirements: writing quality and understandable terms. Passing either cannot compensate for failure of the other. Freeze a named source target (the exact source snapshot to be checked) with full original bytes and an inventory of the 18 pre-existing skills, roles and protected methods; the added skill gives 19 source entries. Check runtime discovery, full configured role prompts and actual full loading of both common skills separately. Use a real distinct verifier and an isolated verifier-owned workspace, preserving original failures and named targets.

Use small actual invocations of every full role. Carry available reader context and links in assignment inputs; combine cases rather than build a broad benchmark:

| Role | Substantive case and required observation |
| --- | --- |
| Orchestrator (coordinator) | Actually delegate a unit question to a fitting worker, receive its report/compact receipt, read the separately owned answer artifact and relay the answer. Explain an essential term the user has not seen, even if an internal report defined it. |
| Investigator | State a bounded finding and its evidence early; explain an unfamiliar term, keep uncertainty and preserve exact IDs/commands. Write only owned attempt artifacts and return the fixed receipt. |
| Planner | Compare approaches and explain a recommendation's practical reason or consequence without inventing user agreement. Preserve current authority and sole-writer boundaries. |
| Producer | Write a standalone explanation, then continue the same problem without repeating definitions. Start a new problem with relevant meanings again; a fuller-detail request must retain all requested substance. |
| Verifier | Reject seeded failures by citing exact passages and the failed dimension. Check a correct expert-context passage without needless basic definitions. Write its own report and separate short outcome summary; do not repair the target. |

For each substantive passage, record writing, terminology and fidelity findings separately. Writing checks cover answer placement, logical order, coherent paragraphs, concrete actors/referents and useful reasons without filler. Term checks cover practical first-use meanings in familiar words, consistent names and actual reader-visible context; acronym expansion or a link alone can fail.

Include these negative controls: deliberately flawed examples that the check must reject. (1) “The report was clear. This changed it, so that is ready”: familiar words but unclear referents and reasoning. (2) An answer that correctly defines “idempotent” as repeating a request without repeating its effect, then buries the recommendation beneath repeated background: terms pass, composition fails. (3) For a new reader asking which request value a payment retry should keep, “Reuse the original idempotency key on the retry” answers clearly but leaves the essential term unexplained. Seed lost-uncertainty, altered-identifier and false-approval traps as well. For example, preserve supplied `U-9037@revision-1`, `pending-unrun` and `opencode debug skill` exactly; a successful source check must not become a claim of observed role loading or user approval.

Record actual runtime/model/config/prompt/source identities, invocation/session IDs, inputs, full-load traces, prose, receipts, writes and result paths. Review complete source changes against original bytes for retained authority, evidence, ownership, lifecycle/domain methods and actual answer/summary read-relay duties. Append observations to `trial-results.md` only after execution, name the changed final target and obtain a new distinct scoped check; earlier role observations apply only to unchanged instruction bytes. Missing prerequisites leave required checks blocked or unrun, not PASS. These checks establish bounded rule-following and prose observations, not a reader study measuring people's comprehension or a guarantee enforced by tools.
