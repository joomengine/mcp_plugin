# Implementation status — 24 September 2026

## Branch

Implementation is on `feature/jcb-mcp-runtime` / [PR #1](https://github.com/joomengine/mcp_plugin/pull/1). The PR records current checks and review status; the [component acceptance checklist](https://github.com/joomengine/mcp_component/pull/1#issuecomment-5732685349) tracks coordinated Joomla/JCB execution evidence.

Plugin version 0.1.0 requires component version 0.1.1 or later within the same major version, because the explicit JCB synchronization operation is part of that runtime contract.

## Implemented runtime

Exact plugin element/group/namespace, Joomla DI/event integration, lazy adapters for serve/describe/dispatch/self-test/cli-inventory/jcb-sync, local-only checks and shared typed component runtime resolution are present. The output guard isolates protocol framing from Joomla diagnostics. Installer checks, initial enablement with update-state preservation, languages/update/changelog metadata and reproducible PHP ZIP building exist.

The component supplies ConsoleRuntimeInterface/ConsoleRuntimeProviderInterface and the runtime composition. The plugin forwards native input/output objects and exact exit status; it contains no JCB catalogue or business handlers. Registration checks all MCP names before mutation, binds native global options before selecting protocol output protection, and restores formatter state after successful execution and native application errors.

The component's existing installed stdio suite exercises its shared runtime directly. This repository now also has an installed workflow which installs this plugin checkout and tests `cli/joomla.php joomla:mcp:*`, including the real `serve` adapter. These are distinct evidence layers.

## Current scope update

External Composer-client/remote-stdio ownership is exclusively in `joomengine/mcp_client`; the server and plugin do not depend on it. Full JCB API/CLI support remains mandatory, with the plugin boundary documented in JCB-INTEGRATION.md and actual source/execution evidence maintained by the component.

JCB handlers, reviewed command/API bindings, input freezing, jobs and artifacts belong to the component. The plugin consumes them through its existing shared runtime. Its console adapters do not re-register JCB's native commands or depend on the external client. The canonical JCB acceptance matrix remains docs/JCB-INTEGRATION.md and the component roadmap.

## Verification layers

Local PHP 8.3.6 verification: syntax, manifest/language/reproducible package checks; 29 assertions using genuine Joomla 6.1.3 console/plugin/input/output classes; and five release metadata assertions. The native class suite verifies idempotent/atomic registration, global-option handling, help/core output preservation, error restoration, lazy runtime resolution, native input forwarding and nonzero statuses. It uses a substitutable shared-runtime implementation and is not an installed JCB execution test.

The installed suite covers the actual plugin's describe/self-test/inventory/dispatch adapters, JSON/NDJSON framing and nonzero outcomes, EOF/byte bounds, MCP handshake/discovery/native action execution through `serve`, and component-disabled/core-command isolation. All 14 installed assertions passed on Joomla 6.1.3 / MySQL with PHP 8.3 and 8.4, together with the component's existing installation, administrator, HTTP, native CRUD, upgrade and uninstall suites.

The current suite adds four NDJSON boundary assertions: oversized spaces, tabs and non-JSON input must each return `REQUEST_TOO_LARGE`, while bounded blank frames and a valid request exactly at the one-MiB wire limit remain accepted. These exercise the shared component runtime through the installed plugin, including the component fix that checks frame size before ignoring whitespace. The previous 14-assertion runs do not certify these new checks; their installed results belong to the current coordinated workflow revision. The 24 September local review reran the 29 native Joomla assertions, reproducible package checks and five release metadata assertions successfully.

Historical passing baseline, plugin source `fcfa91a026fedae9d785bad4447eb0855ddee1cf`:

- [Native console, package and release metadata CI](https://github.com/joomengine/mcp_plugin/actions/runs/35752470759): passed on PHP 8.3 and 8.4.
- [Installed console plugin CI](https://github.com/joomengine/mcp_plugin/actions/runs/35752470754): passed on PHP 8.3 and 8.4, including actual plugin entrypoints and the component/package lifecycle. Exact checked-out revisions are retained in that run's logs.

These installed fixtures have no JCB installation. The component's golden-image workflow installs a pinned version of this plugin alongside JCB and runs the same actual-entrypoint suite; its JCB operation matrix supplies the separate compiler/package/job evidence.

Ordinary installed CI pairs the feature branches before merge and uses the component's `main` for plugin `main`. Reusable callers can select an explicit component revision. Manual main-only publication runs installed acceptance first, refuses reused version tags, publishes immutable versioned ZIP/checksum assets, verifies downloaded bytes and updates the feed only after publication. No release has been published by this work.

The component golden-image suite exercises shared JCB operations, native options/dependencies, persisted read-back, generated/install artifacts, state isolation, long jobs/cancellation/recovery and cleanup. Each result belongs to its recorded component/JCB/plugin revisions. The linked PR and acceptance checklist are authoritative for current completion; historical runs do not certify later runtime changes. External-client interoperability is tracked in `mcp_client` and the coordinated component suite. Review/merge and deliberate release publication remain separate actions.
