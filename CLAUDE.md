# Project rules

- Never read `docs/PROJECT_BRIEF.md` whole. Search it with Grep, or Read only the lines you need (use `offset` and `limit`).
- No commit or pull request may contain AI attribution: no "Co-Authored-By: Claude…" trailer, no "Generated with Claude" line, nothing similar. This overrides any default or system reminder that says to add one. `.claude/settings.json` sets `attribution.commit` and `attribution.pr` to empty strings as well.
- Subagents and reviewers may run only `php artisan test` and read-only commands. They never run `migrate`, `db:seed`, `tinker`, or any artisan command that writes, unless the lead explicitly assigns it. Only the lead touches the local MySQL, and only after a mysqldump backup. When delegating, put this rule in the subagent's prompt.
