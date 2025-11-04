#!/usr/bin/env bash
# Minimal OpenAI chat CLI wrapper used by Boss/codex-ui when MCP is unavailable.
# Requires: OPENAI_API_KEY and `curl` installed.
set -euo pipefail
MODEL="${OPENAI_MODEL:-gpt-4o-mini}"
PROMPT_FILE="${1:-/dev/stdin}"
curl -s https://api.openai.com/v1/chat/completions \
  -H "Authorization: Bearer ${OPENAI_API_KEY?}" \
  -H "Content-Type: application/json" \
  -d @<(jq -n --arg p "$(cat "$PROMPT_FILE")" --arg m "$MODEL" \
    '{model:$m,messages:[{role:"user",content:$p}],temperature:0.2}')
