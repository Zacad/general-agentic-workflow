---
name: clear-communication
description: Write coherent, useful prose and explain unfamiliar terms in context. Use for every workflow assignment, including orchestration.
---

# Clear communication

**Inputs:** the reader's current question or problem, available audience and
expertise context, requested depth, definitions already visible to that reader,
and the facts, evidence and exact strings the answer must preserve. Use linked
reader context when supplied; do not assume an internal worker report was seen.

**Two equal requirements:** good writing and understandable terms. Check each
independently. Familiar words cannot rescue muddled writing; elegant prose cannot
rescue an essential unexplained term.

**Procedure:**
1. Start with the answer, finding, recommendation or blocker the reader needs.
   Choose a logical order for the explanation that follows. Give each paragraph
   a coherent purpose; connect its ideas instead of listing unrelated fragments.
2. Name concrete actors, actions and referents. Make clear what words such as
   “it” or “this” refer to. Include a reason, consequence or example when it helps
   the reader understand the answer or act on it. Keep enough substance to answer
   the actual question, including requested detail and material exceptions.
3. Remove staged introductions, filler, repeated background and padded closings.
   Use headings, lists or emphasis when they help the reader follow the content.
   Vary sentence length naturally; do not turn explanations into telegraphic
   fragments or impose rigid grammar or punctuation rules.
4. Prefer familiar, specific words. Keep an exact technical term when accuracy,
   searchability or reference to code makes it useful. At its first visible use
   in this problem, explain an essential unfamiliar term briefly in familiar
   words, including what it means here. An acronym expansion or link alone may
   still leave the reader unable to understand the concept.
5. Use a consistent name for the same thing. Reuse definitions already available
   to this reader in the current problem without repeating a tutorial. A new
   problem or standalone document needs its own relevant first-use meanings.
   Respect explicit expert context and requests for depth; plain language does
   not mean childlike writing. If reader context is missing, give a brief useful
   meaning where needed rather than inventing expertise or known terms.
6. Before sending user-facing output or finishing source prose, silently check
   both dimensions and truthful fidelity. Revise material failures. Do not show
   the checklist, drafts or editing rationalizations, or add a ceremonial closing.

**Small examples:**

- Writing: “The check finished. It affects that, so this can proceed.” →
  “The verifier found no missing source files. Production can proceed because
  the approved inputs are now available.” Use this wording only if those facts
  are supported; clearer writing must not invent a result or approval.
- Terms: “Use idempotency (IDEMP) for retries.” → “Make retries idempotent:
  repeating the same request must not charge the customer twice.” The meaning,
  not a new abbreviation, explains why the property matters here.
- Context: after that definition was visible in this payment discussion,
  “Reuse the request ID when retrying so the service recognizes the same charge”
  advances the explanation. A standalone payment guide must introduce the
  relevant meaning again; a private report's definition does not count.

**Output:** the requested answer or artifact, with the main point easy to find,
coherent reasoning and understandable necessary terms. Internal reports still
need sufficient proof, organized concisely with links to sources and results.
Keep compact receipts (fixed-field worker handoffs) in their required format;
keep full answers in their assigned artifacts and preserve the required actual
read/relay route. Do not replace an answer with a receipt or a writing review.

**Limits:** preserve meaning, evidence distinctions, uncertainty, full required
coverage, code, commands, IDs, paths, quotes, numbers and status strings. Do not
shorten away a qualification or redefine a technical term inaccurately. This
skill adds no authority or permission and replaces no role or specialist method.
There is no hard word ceiling, universal reading grade, required glossary
tracker, handbook dependency or copyediting agent for every sentence.

**Sources and selected patterns:** original local synthesis informed by
[Writing Clearly and Concisely](https://raw.githubusercontent.com/obra/the-elements-of-style/main/skills/writing-clearly-and-concisely/SKILL.md)
for paragraph composition and concrete language;
[Humanizer](https://raw.githubusercontent.com/blader/humanizer/main/SKILL.md)
for selective removal of filler and repeated staging, not punctuation bans,
exposed drafts or removal of genuine uncertainty; Google's
[jargon](https://developers.google.com/style/jargon) and
[words](https://developers.google.com/tech-writing/one/words) guidance for
audience-aware meanings and consistent names. These links are references, not
additional required loads or evidence of improved reader comprehension.
