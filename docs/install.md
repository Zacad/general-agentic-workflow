# Install the workflow

The kit is:

```
AGENTS.md
.agents/roles/
.agents/skills/
cli/                 source only; vendor/ is installed per machine
docs/
```

## Prerequisites

- PHP 8.4 or newer with the mbstring extension.
- Composer 2.
- `symfony/yaml`, `symfony/console`, `symfony/filesystem`, `symfony/finder`
  and `symfony/lock`, installed by Composer.

## Install

1. **Read the target's rules first.** Inspect its root and nested `AGENTS.md`,
   harness config, `.agents/` and any existing `.workflow/`.

2. **Copy the kit.** You can use the CLI:

   ```sh
   /path/to/kit/cli/bin/workflow install /path/to/target
   ```

   Or copy manually: exclude `cli/vendor/` and `cli/tools/`, and do not
   overwrite an existing `AGENTS.md`; merge it instead.

3. **Merge, do not replace.** If the target already has an `AGENTS.md`, merge
   the workflow rules into it. Keep stricter local rules and deliberate
   exceptions. Hold same-name collisions for review.

4. **Install dependencies and initialize.**

   ```sh
   cd /path/to/target/cli
   composer install
   cd ..
   ./cli/bin/workflow --project /path/to/target init
   ./cli/bin/workflow --project /path/to/target validate
   ```

5. **Register the agents.** For OpenCode V2, copy
   [opencode.example.json](opencode.example.json) to the target root as
   `opencode.json`, or merge its `agents` entries into an existing config. Keep
   it at the project root because `{file:...}` paths resolve from the config
   file. The example:

   - uses V2 fields (`agents`, `system`, ordered `permissions`);
   - omits `model`, so each agent inherits the session model — add
     `"model": "provider/model"` to pin one;
   - sets `default_agent` to `workflow-orchestrator`, so new sessions start
     there — remove it to choose per session;
   - restricts the orchestrator to the three workflow subagents, and denies
     subagents the ability to launch more agents.

   Do not use the V1 `agent`, `prompt`, `permission`, `bash` or `task` fields.
   Skills under `.agents/skills/` are discovered automatically, so no `skills`
   entry is needed. Check the result with:

   ```sh
   cd /path/to/target
   opencode debug agents
   ```

   Restart the harness after prompt or config changes.

   Make the CLI easy to call:

   ```sh
   export PATH="/path/to/target/cli/bin:$PATH"
   workflow --project /path/to/target validate
   ```

6. **Verify.** `validate` should report `OK`. Create one throwaway item, approve
   it, read its brief, record a result, and run `board`. Then delete the
   throwaway item directory and run `validate` again.

## Update

Copy the changed kit files, merge `AGENTS.md` if its rules changed, refresh
`opencode.json` if `docs/opencode.example.json` changed, run `composer install`
if dependencies changed, and run `validate`. Restart the harness after prompt
or config changes. Do not copy the kit's `.workflow/`, `board.html` or
`.locks/` directory.

## What not to copy

- The source project's `.workflow/` records, board or lock.
- `cli/vendor/` and `cli/tools/`.
- Any historical archives or per-attempt directories.
