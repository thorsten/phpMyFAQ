#!/usr/bin/env sh
#
# Claude Code PostToolUse hook: format the file that was just written or edited
# with the project's own formatter, so agent edits never fail the pre-commit
# format check. Reads the hook JSON from stdin, is silent for unsupported file
# types or missing tools, and never blocks the edit.

file=$(node -e '
  let data = "";
  process.stdin.on("data", (chunk) => (data += chunk)).on("end", () => {
    try {
      const input = JSON.parse(data);
      process.stdout.write(input.tool_input?.file_path ?? "");
    } catch {
      /* not JSON: print nothing */
    }
  });
')

[ -n "$file" ] && [ -f "$file" ] || exit 0

case "$file" in
  *.php)
    [ -x phpmyfaq/src/libs/bin/mago ] && phpmyfaq/src/libs/bin/mago format "$file" >/dev/null 2>&1
    ;;
  *.ts|*.js|*.mjs|*.json|*.yml|*.yaml|*.html)
    pnpm exec oxfmt "$file" >/dev/null 2>&1
    ;;
  *.scss)
    pnpm exec stylelint --fix "$file" >/dev/null 2>&1
    ;;
esac

exit 0
