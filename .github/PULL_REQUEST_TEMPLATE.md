## Summary

<!-- What changes and why. Link the issue if there is one. -->

## Checklist

- [ ] `composer check` passes (format, lint, static analysis)
- [ ] `composer test` and `pnpm test` pass
- [ ] `pnpm tsc` and `pnpm oxlint` pass for TypeScript changes
- [ ] New or changed behaviour is covered by tests
- [ ] Database schema changes ship with an upgrade step in `phpMyFAQ\Setup\Update`
- [ ] `CHANGELOG.md` is updated for user-facing changes
- [ ] Dependency major version bumps: upstream changelog read, old APIs grepped for, build green
