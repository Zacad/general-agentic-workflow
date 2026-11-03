# Workflow Enhancements - Implementation Summary

## Overview

Three major enhancements have been implemented to address workflow pain points:

1. **waiting-external status** - Handle tasks with external dependencies (PR reviews, stakeholder approvals)
2. **Change classification** - Distinguish material vs non-material changes for smarter approval
3. **Hierarchical structure** - Organize items in nested directories with migration support

## 1. waiting-external Status

### Problem
Tasks were closing too quickly when work had external dependencies (e.g., PR reviews). New tasks had to be created for follow-up work.

### Solution
Added a new `waiting-external` status that tasks can enter after verification when external dependencies exist.

### Usage

```bash
# Set task to waiting-external after verification
workflow set T-001 status=waiting-external

# Add external dependencies
workflow external-deps T-001 add --type pr-review --description "Code review on PR #123"
workflow external-deps T-001 add --type stakeholder --description "Product owner sign-off"

# List dependencies
workflow external-deps T-001 list

# Update dependency status
workflow external-deps T-001 update --index 1 --status satisfied

# Remove dependency
workflow external-deps T-001 remove --index 2

# When all dependencies satisfied, return to in-progress or close
workflow set T-001 status=in-progress
workflow close T-001 --reason "All external dependencies satisfied"
```

### Status Display
The `workflow status` command now shows a separate section for `waiting-external` items:

```
active (2)
  T-001    worker    2h   Implement feature X

waiting-external (1)
  T-002    1h   Review API design  (2 pending)
```

### Implementation Details
- New status: `Status::WaitingExternal`
- New field: `external_dependencies` in item YAML
- New command: `external-deps` for managing dependencies
- Dependencies have: type, description, status (pending/satisfied/waived)

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
- `CoordinationProjectionTest` updated to include `waiting_external` in JSON schema
- All structural tests pass with backward compatibility

## Migration Guide

### For New Projects
No action needed. New projects automatically use:
- Nested directory structure
- waiting-external status available
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
   # Use waiting-external for tasks with PR reviews
   workflow set T-001 status=waiting-external
   workflow external-deps T-001 add --type pr-review --description "PR #123"
   
   # Check changes before approval
   workflow change T-002
   workflow approve T-002 --delta
   ```

## Files Changed

### Core Domain
- `cli/src/Domain/Status.php` - Added WaitingExternal case
- `cli/src/Domain/Item.php` - Added external_dependencies and last_approved_scope fields

### Store Layer
- `cli/src/Store/ItemStore.php` - Nested path computation, recursive ID collection, backward compatibility

### Commands
- `cli/src/Console/Application.php` - Registered new commands
- `cli/src/Console/Command/SetCommand.php` - Updated status validation
- `cli/src/Console/Command/ApproveCommand.php` - Added --delta flag, saves approved scope
- `cli/src/Console/Command/StatusCommand.php` - Shows waiting-external section

### New Commands
- `cli/src/Console/Command/ExternalDepsCommand.php` - Manage external dependencies
- `cli/src/Console/Command/ChangeCommand.php` - Detect and classify changes
- `cli/src/Console/Command/MigrateCommand.php` - Migrate flat to nested structure

### Support Classes
- `cli/src/Support/ChangeDetector.php` - Classifies changes as material/non-material

### Tests
- `cli/tests/Console/CoordinationProjectionTest.php` - Updated for new JSON schema

## Future Enhancements

Potential improvements:
1. **Auto-detect waiting-external**: Suggest waiting-external status when PR review is mentioned
2. **Change preview**: Show diff of what changed before approval
3. **Selective migration**: Migrate specific epics instead of all at once
4. **External dependency templates**: Predefined dependency types (pr-review, stakeholder, etc.)
5. **Batch delta approval**: Approve multiple non-material changes at once

## Rollback Plan

If issues arise:
1. Hierarchical structure: Backup exists at `.workflow/items.backup/`
2. waiting-external: Can set status back to in-progress
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
1. ✅ Tasks no longer close prematurely when external dependencies exist
2. ✅ Items organized hierarchically for easier navigation
3. ✅ Small additive changes don't require full re-approval

All changes are backward compatible and include migration paths for existing installations.
