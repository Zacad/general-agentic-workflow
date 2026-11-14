# Browser-rendered verification

This is a decision procedure for the acting role, not a permission detector or
enforcement mechanism. Follow the root and nested project instructions, host
policy, user authority and any applicable run limits. Human review remains the
default; design, implementation and verification remain separate roles. An
installer, agent catalog or generic shell allowance does not establish browser
availability or permission.

## Decide before acting

1. Identify the required rendered states and any actual moving-playback
   criterion, frozen target and revision, active project location, client,
   session and assigned role. Read applicable instructions, host policies,
   user/run authority and this role's available tools and permissions. Record
   catalog observations separately from actual rejections.
2. Classify restrictions narrowly without assuming intent. An absent desktop
   attachment or catalog has multiple possible causes; it is neither a general
   browser ban nor an authorization. An effective `browser` action deny on
   `*` denies the desktop namespace; an effective `skill` deny on `browser`
   forbids loading that skill. Neither alone establishes a general prohibition
   or permission for shell, MCP or browser processes. A matching host, project
   or user instruction, policy or permission binds within its actual scope.
   Never retry a denied action through shell, MCP, another role or changed
   configuration to evade it, and never change permissions for a fallback.
3. Resolve unknown provenance, intent or coverage before choosing a fallback.
   Preserve a redacted exact rule or response and its location, agent and session
   if available; a listed rule is not an observed runtime rejection. Ask the
   responsible user or host policy owner whether the restriction is limited to
   the desktop capability and whether the specific local action and effects are
   permitted. Without authoritative evidence or an answer, return `blocked`,
   naming the missing authority and proposed action. Silence, an absent rule,
   a generic shell allow and another role's permissions are not consent. A known
   capability-only restriction is different from a broad browser-execution ban,
   which blocks browser execution. Do not probe a restricted action for a deny.
4. Check the exact proposed route, only after scope, intent and relevant
   authority are established. Check this role's applicable ordered rules,
   managed policy, prompts and any external-directory checks for the concrete
   command or tool. An `ask` needs an affirmative answer from the appropriate
   authority; a denial, unavailable check or unanswered prompt stops that
   operation. Check that a compatible runner and browser are already available;
   do not presume their versions. Account for loopback listening, process launch,
   profile and temporary files, output writes, network requests, project
   boundaries and other effects under the applicable limits. Do not install a
   browser, execute downloaded code, disable browser sandboxing or change
   permissions as a workaround. Block with the missing fact and next decision
   if any necessary capability, effect or authorization is unavailable.
5. If independently permitted and available, prefer the existing project-local
   browser automation suite or a small project-local Playwright/WebDriver-style
   harness with its existing compatible browser. Only approved commands and
   effects may run; no generic command is intrinsically authorized. Record the
   actual command, runner and browser versions, headless or headed mode, frozen
   target, viewport, interactions, output locations, relevant network/console
   errors and limitations. Label it local automation, never the unavailable
   desktop tool. Another independent role or human may inspect evidence only
   when their actual action is independently authorized, not to defeat a denial.
6. The separate verifier independently inspects browser-rendered output of the
   frozen actual target against each criterion. Raw HTML, text, producer claims
   and automated checks alone do not prove visual review. If continuous
   playback is required, an independent reviewer must actually watch moving
   playback of the frozen target or a faithful frozen recording. Screenshots,
   sampled frames, frame extraction, timing counters and the mere existence of
   a recording do not prove continuity. If no authorized reviewer can watch it,
   mark that criterion `blocked`, not passed. Record target identity,
   renderer/mode, views, viewport, inspection method and applicable browser/GPU,
   interaction, accessibility and playback limits. Existing approval, role
   separation, verification and review gates are unchanged.

## Classification examples

These examples describe outcomes, not permission grants or runtime checks.

- **Absent catalog:** desktop browser catalog is absent, cause unknown: do not
  infer denial, a broad ban or permission. Establish authority for the proposed
  route; if unresolved, `blocked`.
- **Unknown denial provenance:** effective `browser`/`*` and `skill`/`browser`
  denies are listed, but intent and coverage are unclear: `blocked` pending
  authoritative clarification; do not claim an unobserved rejection.
- **Capability-only restriction:** the responsible authority establishes the
  restriction is desktop-only, the exact local action and effects are separately
  authorized for this role, and a compatible runner is available: use the
  permitted local route and label it local automation.
- **Explicit broader ban:** browser execution is forbidden by an applicable
  instruction or the proposed command is denied by a matching policy: `blocked`;
  no alternate route or role may bypass that prohibition.
- **Unanswered or unavailable:** an `ask` is unanswered: `blocked` for missing
  authority. If permission is confirmed but the runner is unavailable: `blocked`
  for missing capability; do not install one as a workaround.
- **Playback:** independently inspected stills or an unplayable recording may
  support a still-image criterion, but continuous playback remains `blocked`
  until an authorized independent reviewer actually watches it.
- **Recovery after clarification:** an authoritative clarification limits the
  original restriction to the desktop namespace and independently permits the
  concrete local action and effects. Recheck the assigned role's permissions
  and availability, then use the permitted route without changing permissions.

## Blocked handoff

For an explicit ban or unresolved permission, give the authority a bounded
question containing the exact proposed tool or command, role, frozen target,
effects and evidence of the restriction. Request clarification or separately
applicable permission, never a policy workaround. Distinguish missing authority
from a missing runner and identify the next decision. A host denial remains
binding even if another tool technically works. Do not claim a browser run,
visual review or playback that did not occur.

## Sources

- [OpenCode browser tool](https://opencode.ai/docs/tools#browser)
- [OpenCode skill permissions](https://opencode.ai/docs/skills#permissions)
- [OpenCode permissions](https://opencode.ai/docs/permissions)
- [OpenCode policies](https://opencode.ai/docs/policies#tools)
