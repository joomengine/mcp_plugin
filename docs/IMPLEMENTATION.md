# Implementation status — 18 September 2026

## Branch

Continue `feature/jcb-mcp-runtime`, draft PR #1. The implementation baseline inspected for this documentation update is `fb6a72a515859d198b47acb6fdac932e5dae92ab`; do not replace the existing runtime branch.

## Implemented runtime

Exact plugin element/group/namespace, Joomla DI/event integration, lazy adapters for serve/describe/dispatch/self-test/cli-inventory, local-only checks and shared typed component runtime resolution are present. The output guard isolates protocol framing from Joomla diagnostics. Installer checks, initial enablement with update-state preservation, languages/update/changelog metadata and reproducible PHP ZIP building exist.

The component now supplies ConsoleRuntimeInterface/ConsoleRuntimeProviderInterface and the runtime composition; older statements that those interfaces have not yet been written are superseded. Complete combined installation and live command execution still need verification.

## Current scope update

External Composer-client/remote-stdio ownership is exclusively in `joomengine/mcp_client`; the server and plugin do not depend on it. Full JCB API/CLI support is now mandatory in README, AGENTS and architecture, with concrete plugin tasks in JCB-INTEGRATION.md linked to the component's pinned roadmap/inventory.

This update documents the extension work; it does **not** implement new JCB handlers, active target rows or compiler/package job execution. Installed JCB command registration and actual API routes still need exact inventories before executable bindings are enabled.

## Evidence and remaining work

The inspected baseline's CI passed PHP 8.3/8.4 syntax and manifest/reproducible-package contracts. Consult the current PR head's checks for this documentation update. Neither those tests nor a CLI inventory proves installed Joomla/JCB execution.

Finish all plugin responsibilities in JCB-INTEGRATION.md and the component's complete installed-server acceptance: actual core/JCB operations, options/dependencies, stdio output/exit behaviour, persisted read-back, generated/install artifacts, state isolation, long jobs/cancellation/recovery and cleanup. Record exact source/runtime versions and evidence before marking this PR ready. Client interoperability and independent release work are tracked in mcp_client.
