# Session Startup Protocol

This document defines the **mandatory startup protocol** that all AI assistants (Claude, Gemini, Codex) must follow at the beginning of every new session.

**Purpose**: Ensure continuity, avoid working on outdated code, and streamline development workflow.

---

## Overview

When starting a new session in the `stevenovak55/dealroom` repository, you must complete these steps **IN ORDER** before beginning any new work:

1. Pull Latest GitHub Updates
2. Review Session History
3. Check Current State
4. Verify Development Environment
5. Confirm Understanding

---

## Step 1: Pull Latest GitHub Updates

**⚠️ CRITICAL**: Always pull the latest updates from GitHub FIRST, before reading any files or making any changes.

### Why This Matters

- The user can only see what's on GitHub (not the local working directory)
- Multiple sessions may have modified files since your last session
- Working on outdated code causes merge conflicts and wasted effort

### Commands to Run

```bash
# Check current branch
git branch

# Fetch latest changes from remote
git fetch origin

# If on a feature branch (claude/*)
git pull origin <current-branch-name>

# Also pull from main to see what's new
git pull origin main

# Check for any changes
git status
```

### Expected Output

- Either "Already up to date" or files updated
- Note any conflicts or uncommitted changes
- Document what changed since last session

---

## Step 2: Review Session History

After pulling updates, review the latest documentation to understand what was worked on recently.

### Files to Read (in order)

1. **VERSION_HISTORY.md** - Check current version and recent changes
2. **DEVELOPMENT_ROADMAP.md** - Review current priorities and next steps
3. **docs/CHANGELOG.md** - Latest feature changes and bug fixes
4. **docs/archive/session-notes/** - Check most recent session notes (sorted by date)

### Key Questions to Answer

- What was the last major change?
- What version are we on?
- Were there any critical issues recently fixed?
- What's the next priority on the roadmap?

### Example Review Process

```bash
# Read current version
cat VERSION_HISTORY.md | head -50

# Check latest session notes
ls -lt docs/archive/session-notes/ | head -5

# Read the most recent session note
cat docs/archive/session-notes/SESSION_2025-11-XX.md
```

---

## Step 3: Check Current State

Verify the current state of the codebase and development environment.

### System Checks

```bash
# 1. Check WordPress plugin version
grep "Version:" ma-deal-room/ma-deal-room.php

# 2. Check if dependencies are installed
ls -la ma-deal-room/vendor/ | head -10

# 3. Check for any uncommitted changes
git status

# 4. Check recent commits
git log --oneline -10

# 5. List current production files
ls -lh *.zip 2>/dev/null || echo "No zip files in root"
```

### Database Migration Status

```bash
# Check latest migration number
ls ma-deal-room/database/migrations/ | tail -5
```

### Documentation State

```bash
# Verify documentation structure
ls -lh *.md
ls docs/
```

---

## Step 4: Verify Development Environment

Ensure all prerequisites are in place for the type of work being requested.

### For Plugin Development

Check these prerequisites:

```bash
# Check PHP version (need 8.0+)
php --version

# Check Node.js version (need 18+)
node --version

# Check npm version
npm --version

# Check if Composer is available
composer --version
```

### For Plugin Building/Testing

Run the **PLUGIN_BUILD_CHECKLIST.md** protocol (see that file for details):

- Verify vendor dependencies
- Check frontend build status
- Validate migrations
- Ensure no syntax errors

---

## Step 5: Confirm Understanding

Before proceeding with the user's request, create a brief summary demonstrating you understand:

### Summary Template

```
**Session Start Summary**

Current State:
- Branch: <branch-name>
- Version: <current-version>
- Last Update: <date-of-last-commit>
- Latest Changes: <brief-summary>

Recent Work:
- <what-was-worked-on-recently>

Current Priorities (from DEVELOPMENT_ROADMAP.md):
- <next-priority-items>

Ready to proceed with: <user-request>
```

### Example

```
**Session Start Summary**

Current State:
- Branch: claude/pull-latest-updates-011CUqv7vepEdkRMkda8aVXa
- Version: 2.0.0 Production
- Last Update: November 5, 2025
- Latest Changes: Fixed cache invalidation, transaction model improvements

Recent Work:
- Cache invalidation system overhaul
- Fresh installation testing
- Production deployment verification

Current Priorities (from DEVELOPMENT_ROADMAP.md):
- V2.1.0: Email template enhancements
- V2.2.0: Advanced reporting features

Ready to proceed with: Adding new vendor notification feature
```

---

## Quick Start Checklist

Use this checklist at the start of every session:

- [ ] Run `git fetch origin && git pull origin <branch>`
- [ ] Run `git status` to check for changes
- [ ] Read `VERSION_HISTORY.md` (first 50 lines)
- [ ] Read `DEVELOPMENT_ROADMAP.md` to understand priorities
- [ ] Check recent session notes in `docs/archive/session-notes/`
- [ ] Verify WordPress plugin version matches VERSION_HISTORY.md
- [ ] Check if vendor dependencies exist (`ls ma-deal-room/vendor/`)
- [ ] Review last 10 commits with `git log --oneline -10`
- [ ] If building plugin: Run PLUGIN_BUILD_CHECKLIST.md protocol
- [ ] Provide Session Start Summary to user
- [ ] Ask clarifying questions if anything is unclear

---

## Common Issues and Solutions

### Issue: Local changes conflict with remote

**Solution:**
```bash
# Stash local changes
git stash

# Pull latest
git pull origin <branch>

# Re-apply local changes
git stash pop

# Resolve conflicts if any
```

### Issue: Branch doesn't exist on remote

**Solution:**
```bash
# Fetch all branches
git fetch --all

# Check available branches
git branch -a

# Pull from main instead
git pull origin main
```

### Issue: Don't know what was worked on last

**Solution:**
```bash
# Check git log
git log --oneline -20

# Read most recent session notes
ls -lt docs/archive/session-notes/ | head -5
cat docs/archive/session-notes/<most-recent-file>

# Check DEVELOPMENT_ROADMAP.md for completed items
```

### Issue: Dependencies missing when trying to build

**Solution:**
- Follow **PLUGIN_BUILD_CHECKLIST.md** protocol
- Install missing dependencies before proceeding
- Document the issue for future reference

---

## Integration with Other Protocols

This protocol works together with:

- **PLUGIN_BUILD_CHECKLIST.md** - Run this when building/testing plugins
- **WP_PLUGIN_DEPLOYMENT_AGENT.md** - Follow this for deployment testing
- **DEPLOYMENT_CHECKLIST.md** - Use this for production deployments
- **AI_MASTER.md** - Universal guidelines for all AI assistants
- **CLAUDE.md / GEMINI.md / CODEX.md** - Agent-specific instructions

---

## Notes for Users

As a user, you can help ensure smooth sessions by:

1. **Pushing your latest changes to GitHub** before starting a new session
2. **Updating VERSION_HISTORY.md** when making significant changes
3. **Adding session notes** to `docs/archive/session-notes/` when finishing complex work
4. **Updating DEVELOPMENT_ROADMAP.md** to reflect current priorities

---

## Enforcement

This protocol is **mandatory** for all AI assistants working in this repository.

If an AI assistant begins work without following this protocol:

1. The user should remind them to follow SESSION_STARTUP_PROTOCOL.md
2. The assistant should pause current work
3. The assistant should complete all protocol steps
4. The assistant should confirm understanding before resuming

---

## Version

**Protocol Version**: 1.0
**Last Updated**: November 6, 2025
**Maintained By**: MA Deal Room Development Team

---

## Feedback

If this protocol needs updates or improvements, document suggestions in:
- GitHub Issues
- Session notes in `docs/archive/session-notes/`
- Comments in AI_MASTER.md
