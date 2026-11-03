# Workflow Enhancements - Implementation Summary

## Overview

Three major enhancements have been implemented to address workflow pain points:

1. **review status** - Handle tasks requiring additional review before closure (PR reviews, orchestrator review, iteration)
2. **Change classification** - Distinguish material vs non-material changes for smarter approval
3. **Hierarchical structure** - Organize items in nested directories with migration support

## 1. review Status

### Problem
Tasks were closing too quickly when work required additional review or iteration (e.g., PR reviews, orchestrator feedback). New tasks had to be created for follow-up work.

### Solution
Added a new `review` status that tasks can enter after verification when additional review is needed. Use progress notes to document what's pending.

### Usage

```bash
# Set task to review after verification
workflow set T-001 status=review

# Document what's pending using progress notes
workflow log T-001 "Awaiting PR review on #123"
workflow log T-001 "Need orchestrator feedback on design approach"

# When review feedback is received, return to in-progress
workflow set T-001 status=in-progress
workflow log T-001 "Addressed PR comments, ready for final verification"

# Or close directly if no further work needed
workflow close T-001 --reason "Review completed, all feedback addressed"
```

### Status Display
The `workflow status` command now shows a separate section for `review` items:

```
active (2)
  T-001    worker    2h   Implement feature X

review (1)
  T-002    1h   Review API design
```

### Implementation Details
- New status: `Status::Review`
- No explicit dependency tracking - use progress notes instead
- Simpler and more general than tracking external dependencies
- Works for both external (PR reviews) and internal (orchestrator review) scenarios

## 2. Change Classification

### Problem
Small additive changes required full re-approval of the entire scope/design, even when changes were non-material.

### Solution
Implemented change detection and classification. Changes are now categorized as:
- **Material**: Require full re-approval (outcome changes, scope reduction, acceptance removal, etc.)
- **Non-material**: Can use delta approval (scope additions, dependency additions, acceptance additions)

### Usage

```bash
# Check what changed since last approval
workflow change T-001

# Output example:
# Changes for T-001 since last approval:
#
# Non-material changes (delta approval sufficient):
#   • added 2 acceptance criterion/criteria
#   • added 1 item(s) to scope.in
#
# Recommendation: Run "workflow approve T-001 --delta" for delta approval.

# Delta approval (non-material changes only)
workflow approve T-001 --delta

# Full approval (material changes)
workflow approve T-001
```

### Classification Rules

**Material changes** (require full re-approval):
- Outcome changed
- Acceptance criteria removed
- Scope reduced (items removed from scope.in or added to scope.out)
- Dependencies removed
- Owned paths changed (added or removed)
- Title changed
- Parent changed

**Non-material changes** (delta approval sufficient):
- Acceptance criteria added
- Scope expanded (items added to scope.in or scope.out)
- Dependencies added

### Implementation Details
- New field: `last_approved_scope` stores snapshot of approved state
- New class: `ChangeDetector` compares current vs approved state
- New command: `change` shows what changed and classification
- Updated: `approve --delta` validates no material changes before approving
- Snapshot saved automatically on every approval

## 3. Hierarchical Structure

### Problem
All items lived in flat `.workflow/items/` directory, making navigation difficult as projects grew.

### Solution
Items are now stored in nested directories based on parent relationships:
```
.workflow/items/
  E-001/
    item.md
    T-001/
      item.md
      ST-001/
        item.md
      ST-002/
        item.md
    T-002/
      item.md
  E-002/
    item.md
  T-099/              # standalone task
    item.md
```

### Migration

For existing installations with flat structure:

```bash
# Check what would be migrated (dry run)
workflow migrate --dry-run

# Perform migration (creates backup)
workflow migrate

# Force migration without confirmation
workflow migrate --force
```

The migration:
1. Creates backup at `.workflow/items.backup/`
2. Moves items to nested structure based on parent relationships
3. Preserves all item data and history
4. Provides rollback if migration fails

### Backward Compatibility
- Old flat structure continues to work until migration
- New items automatically use nested structure
- `itemPath()` falls back to flat path if nested doesn't exist
- Existing scripts and workflows continue to function

### Implementation Details
- Modified: `ItemStore::itemPath()` computes nested paths
- Modified: `ItemStore::ids()` recursively scans nested directories
- New: `ItemStore::relativeItemPath()` computes path from parent chain
- New: `ItemStore::parentChain()` traverses parent hierarchy
- New command: `migrate` for flat-to-nested migration

## Testing

All existing tests pass (742 tests, 5889 assertions).

Key test updates:
- `CoordinationProjectionTest` updated to include `review` in JSON schema
- All structural tests pass with backward compatibility

## Migration Guide

### For New Projects
No action needed. New projects automatically use:
- Nested directory structure
- review status available
- Change classification on approval

### For Existing Projects

1. **Test the migration** (optional but recommended):
   ```bash
   workflow migrate --dry-run
   ```

2. **Run the migration**:
   ```bash
   workflow migrate
   ```

3. **Verify the migration**:
   ```bash
   workflow status
   ls .workflow/items/
   ```

4. **Start using new features**:
   ```bash
   # Use review status for tasks needing additional review
   workflow set T-001 status=review
   workflow log T-001 "Awaiting PR review on #123"
   
   # Check changes before approval
   workflow change T-002
   workflow approve T-002 --delta
   ```

## Files Changed

### Core Domain
- `cli/src/Domain/Status.php` - Added Review case
- `cli/src/Domain/Item.php` - Added last_approved_scope field

### Store Layer
- `cli/src/Store/ItemStore.php` - Nested path computation, recursive ID collection, backward compatibility

### Commands
- `cli/src/Console/Application.php` - Registered new commands
- `cli/src/Console/Command/SetCommand.php` - Updated status validation
- `cli/src/Console/Command/ApproveCommand.php` - Added --delta flag, saves approved scope
- `cli/src/Console/Command/StatusCommand.php` - Shows review section

### New Commands
- `cli/src/Console/Command/ChangeCommand.php` - Detect and classify changes
- `cli/src/Console/Command/MigrateCommand.php` - Migrate flat to nested structure

### Support Classes
- `cli/src/Support/ChangeDetector.php` - Classifies changes as material/non-material

### Tests
- `cli/tests/Console/CoordinationProjectionTest.php` - Updated for new JSON schema

## Future Enhancements

Potential improvements:
1. **Change preview**: Show diff of what changed before approval
2. **Selective migration**: Migrate specific epics instead of all at once
3. **Batch delta approval**: Approve multiple non-material changes at once

## Rollback Plan

If issues arise:
1. Hierarchical structure: Backup exists at `.workflow/items.backup/`
2. review status: Can set status back to in-progress
3. Change classification: Old approvals still valid; new field is additive

To fully rollback:
```bash
# Restore flat structure
rm -rf .workflow/items
mv .workflow/items.backup .workflow/items

# Revert code changes
git checkout HEAD -- cli/
```

## Summary

These enhancements address the three pain points:
1. ✅ Tasks no longer close prematurely when additional review is needed
2. ✅ Items organized hierarchically for easier navigation
3. ✅ Small additive changes don't require full re-approval

All changes are backward compatible and include migration paths for existing installations.
