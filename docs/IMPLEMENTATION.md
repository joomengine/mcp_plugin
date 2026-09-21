# Implementation status — 21 September 2026

## Branch

Continue `feature/jcb-mcp-runtime`, draft PR #1. Preserve this branch and its component-owned shared runtime contract.

## Implemented runtime

Exact plugin element/group/namespace, Joomla DI/event integration, lazy adapters for serve/describe/dispatch/self-test/cli-inventory, local-only checks and shared typed component runtime resolution are present. The output guard isolates protocol framing from Joomla diagnostics. Installer checks, initial enablement with update-state preservation, languages/update/changelog metadata and reproducible PHP ZIP building exist.

The component supplies ConsoleRuntimeInterface/ConsoleRuntimeProviderInterface and the runtime composition. The plugin forwards native input/output objects and exact exit status; it contains no JCB catalogue or business handlers. Registration checks all MCP names before mutation, binds native global options before selecting protocol output protection, and restores formatter state after successful execution and native application errors.

The component's existing installed stdio suite exercises its shared runtime directly. This repository now also has an installed workflow which installs this plugin checkout and tests `cli/joomla.php joomla:mcp:*`, including the real `serve` adapter. These are distinct evidence layers.

## Current scope update

External Composer-client/remote-stdio ownership is exclusively in `joomengine/mcp_client`; the server and plugin do not depend on it. Full JCB API/CLI support is now mandatory in README, AGENTS and architecture, with concrete plugin tasks in JCB-INTEGRATION.md linked to the component's pinned roadmap/inventory.

JCB handlers, reviewed command/API bindings, input freezing, jobs and artifacts belong to the component. The plugin consumes them through its existing shared runtime. Its console adapters do not re-register JCB's native commands or depend on the external client. The canonical JCB acceptance matrix remains docs/JCB-INTEGRATION.md and the component roadmap.

## Evidence and remaining work

Local PHP 8.3.6 verification: syntax, manifest/language/reproducible package checks; 26 assertions using genuine Joomla 6.1.3 console/plugin/input/output classes; and five release metadata assertions. The native class suite verifies idempotent/atomic registration, global-option handling, help/core output preservation, error restoration, lazy runtime resolution, native input forwarding and nonzero statuses. It uses a substitutable shared-runtime implementation and is not an installed JCB execution test.

The installed suite covers the actual plugin's describe/self-test/inventory/dispatch adapters, JSON/NDJSON framing and nonzero outcomes, EOF/byte bounds, MCP handshake/discovery/native action execution through `serve`, and component-disabled/core-command isolation. CI runs this against PHP 8.3/8.4 and MySQL using the component's disposable installation and upgrade/teardown fixture. Record actual CI outcomes before treating this newly added suite as passed.

Manual main-only publication runs installed acceptance first, refuses reused version tags, publishes immutable versioned ZIP/checksum assets, verifies downloaded bytes and updates the feed only after publication. No release has been published by this work.

Complete coordinated installed JCB operations through the shared runtime, including native options/dependencies, persisted read-back, generated/install artifacts, state isolation, long jobs/cancellation/recovery and cleanup. Confirm current installed workflow results and record exact component/JCB/plugin sources before marking this PR ready. Client interoperability is tracked in mcp_client.
