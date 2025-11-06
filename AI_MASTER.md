# AI Master Instructions

This document contains universal instructions and guidelines for all AI assistants (Claude, Gemini, and Codex) working in this project.

## Project Overview

This is the `dealroom` project located at `/home/snova/projects/dealroom`.

## General Guidelines

### Communication
- Be concise and clear in all responses
- Provide code references with `file_path:line_number` format when applicable
- Use proper markdown formatting for readability

### Code Standards
- Maintain consistency with existing code style
- Write clear, self-documenting code
- Include comments for complex logic
- Follow best practices for the language being used

### File Operations
- Always read files before editing to understand context
- Prefer editing existing files over creating new ones
- Use appropriate tools for file operations (Read, Write, Edit)

### Version Control
- Check git status before making commits
- Write clear, descriptive commit messages
- Follow the repository's commit message style
- Don't commit sensitive information (.env, credentials, etc.)

### Task Management
- Break down complex tasks into smaller steps
- Track progress systematically
- Complete one task fully before moving to the next
- Verify changes after implementation

### Collaboration
- Multiple AIs may be working in this directory simultaneously
- Check for recent changes before making modifications
- Communicate clearly about what you're working on
- Avoid conflicting changes

## AI-Specific Instructions

Each AI has a dedicated instruction file:
- **Claude**: See `CLAUDE.md`
- **Gemini**: See `GEMINI.md`
- **Codex**: See `CODEX.md`

## Session Startup Protocol

**⚠️ MANDATORY**: At the start of every new session, follow the **SESSION_STARTUP_PROTOCOL.md**:

1. **Pull latest GitHub updates first** (before reading any files)
2. Review session history and recent changes
3. Check current state (version, dependencies, commits)
4. Verify development environment
5. Confirm understanding with user

See `SESSION_STARTUP_PROTOCOL.md` for complete step-by-step instructions.

## Plugin Build Protocol

**⚠️ MANDATORY**: Before creating any plugin zip file, follow the **PLUGIN_BUILD_CHECKLIST.md**:

1. Verify Composer dependencies are installed
2. Verify frontend build exists (React admin dashboard)
3. Validate PHP syntax (no errors)
4. Verify database migrations
5. Check plugin version consistency
6. Run pre-build tests

See `PLUGIN_BUILD_CHECKLIST.md` for complete checklist and troubleshooting.

## Important Notes

- Always reference this master file for universal guidelines
- Always follow SESSION_STARTUP_PROTOCOL.md at the start of each session
- Always follow PLUGIN_BUILD_CHECKLIST.md before building plugin zips
- AI-specific files may override or extend these instructions
- When in doubt, prioritize code quality and user clarity
