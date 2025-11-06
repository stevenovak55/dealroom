| Task type                                                        | Primary agent                 | Secondary agent(s)                                                  | Notes                                                                |
| ---------------------------------------------------------------- | ----------------------------- | ------------------------------------------------------------------- | -------------------------------------------------------------------- |
| WordPress plugin deployment/activation/uninstall testing         | `wp-plugin-deployment-agent`  | `claude-db` for migration issues                                    | See WP_PLUGIN_DEPLOYMENT_AGENT.md for full guide                     |
| New UI / component / styling / email HTML                        | `codex-ui`                    | `gemini-implementer` if many files; fallback Claude for integration | "Codex" = your OpenAI code model alias                               |
| DB design, migrations, SQL perf                                  | `claude-db`                   | —                                                                   | Use EXPLAIN + rollback plan; run local tests                         |
| Big JS/PHP feature (controllers/services/APIs/WordPress plugins) | `gemini-implementer`          | `codex-ui` for any UI; `claude-db` for queries                      | Let Gemini plan across large contexts, then integrate                |
| Refactor across many files                                       | `gemini-implementer`          | `claude-db` if data logic involved                                  | Keep patches atomic, add changelog                                   |
| Bug with unclear root cause                                      | `claude-db`                   | whichever matches surface area                                      | Claude tends to debug reliably with tools and tests ([Anthropic][1]) |

[1]: https://www.anthropic.com/news/3-5-models-and-computer-use?utm_source=chatgpt.com "Introducing computer use, a new Claude 3.5 Sonnet, and ... - Anthropic"

# Claude Instructions

## Important: Read Master File First

**ALWAYS reference `AI_MASTER.md` for universal guidelines and project context.**

This file contains Claude-specific instructions that supplement the master guidelines.

## Claude-Specific Capabilities

### Tools and Features
- You have access to specialized tools: Read, Write, Edit, Bash, Glob, Grep, Task agents
- Use the TodoWrite tool to track tasks and show progress
- Prefer specialized tools over bash commands for file operations
- Can launch specialized Task agents for complex, multi-step work

### Working Style
- Use TodoWrite tool for multi-step tasks to track progress
- Mark todos as completed immediately after finishing each task
- Call multiple independent tools in parallel when possible
- Use Task agents (Explore subagent) for codebase exploration

### Code References
- When referencing code, use the format: `file_path:line_number`
- Example: "The error handler is in src/app.js:42"

### Communication Style
- Be concise and direct
- Use GitHub-flavored markdown
- Avoid emojis unless explicitly requested
- Output text directly to communicate (don't use bash echo)

### Git Operations
- Use proper git workflow with status checks
- Include this footer in commit messages:
  ```
  🤖 Generated with [Claude Code](https://claude.com/claude-code)

  Co-Authored-By: Claude <noreply@anthropic.com>
  ```

### Multi-AI Environment
- Be aware that Gemini and Codex may also be working in this directory
- Check for recent changes before modifying files
- Use git status to see what others might have changed
- Coordinate to avoid conflicts

## Workflow

### Session Startup (MANDATORY)

**Before starting any work**, follow `SESSION_STARTUP_PROTOCOL.md`:

1. Pull latest GitHub updates first (git fetch && git pull)
2. Review VERSION_HISTORY.md and DEVELOPMENT_ROADMAP.md
3. Check recent commits and session notes
4. Verify current state (version, dependencies)
5. Provide Session Start Summary to user

### Plugin Building (MANDATORY)

**Before creating any plugin zip**, follow `PLUGIN_BUILD_CHECKLIST.md`:

1. Verify Composer dependencies installed
2. Verify frontend build exists
3. Validate PHP syntax
4. Check migrations and version
5. Run pre-build tests

### General Workflow

1. Read `AI_MASTER.md` for general project guidelines
2. Understand the task fully before starting
3. Create todo list with TodoWrite for multi-step tasks
4. Execute tasks systematically
5. Mark todos complete as you finish each step
6. Verify your changes

## Getting Help

If unclear about project-specific requirements, ask the user for clarification.
