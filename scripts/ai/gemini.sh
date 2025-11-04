#!/usr/bin/env bash
# Minimal Gemini chat CLI wrapper used by Boss/gemini-implementer when MCP is unavailable.
# Requires: GOOGLE_API_KEY and `curl`.
set -euo pipefail
MODEL="${GEMINI_MODEL:-gemini-2.5-pro}"
PROMPT_FILE="${1:-/dev/stdin}"
curl -s "https://generativelanguage.googleapis.com/v1beta/models/${MODEL}:generateContent?key=${GOOGLE_API_KEY?}" \
  -H 'Content-Type: application/json' \
  -d @<(jq -n --arg p "$(cat "$PROMPT_FILE")" '{contents:[{parts:[{text:$p}]}]}')
