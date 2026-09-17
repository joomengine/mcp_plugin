# Implementation status

## Branch and review

The existing `feature/jcb-mcp-runtime` branch is retained. PR #1 has now actually been created; earlier documentation incorrectly assumed that it already existed.

## Runtime implemented

- Exact plugin element/group/namespace, Joomla console lifecycle and dependency-injected provider.
- Lazy command adapters for `joomla:mcp:serve`, `describe`, `dispatch`, `self-test` and `cli-inventory`.
- Local-only entry checks; missing component failures do not eagerly break unrelated Joomla commands.
- Original companion protocol framing delegated to the component-owned implementation, with PHP MCP stdio as a separate mode.
- Installer dependency checks, first-install enabling with upgrade state preservation, language/update/changelog metadata.
- Reproducible PHP-only ZIP builder and manifest/package contracts on PHP 8.3 and 8.4.

## Verification

CI results must be inspected for the current commit. The end-to-end Joomla runtime is tested with the coordinated component implementation; package tests alone are not proof of runtime parity or production readiness. The component's `ConsoleRuntimeInterface` and `ConsoleRuntimeProviderInterface` are required before these commands can execute.
