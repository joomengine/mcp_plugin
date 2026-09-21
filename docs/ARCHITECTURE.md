# Console plugin architecture

## Identity and repository boundary

`plg_console_joomengine_mcp`: element `joomengine_mcp`, group `console`, namespace `VDM\Plugin\Console\JoomEngineMcp`. Root manifest/installer/services/src/language/update files form a Joomla-native, JCB-aligned plugin project. Do not nest it in another installable plugin root.

The plugin adapts the real Joomla console to the shared `com_joomengine_mcp` runtime. It owns neither HTTP webservices routing nor an external Composer client. HTTP routing glue belongs to the component; external client/remote stdio belongs to `joomengine/mcp_client`. No server-to-client package dependency is permitted.

## Local execution and wire framing

The native Joomla console lifecycle registers lazy `joomla:mcp:serve`, `describe`, `dispatch`, `self-test`, `cli-inventory` and `jcb-sync` adapters. Invocation verifies the real console application/CLI SAPI and resolves the component's typed ConsoleRuntimeProviderInterface/ConsoleRuntimeInterface. Missing component dependencies produce a command failure without eagerly breaking unrelated commands.

Local server ownership is the requested authority boundary: no API token or row viewing permission is needed, but schema validation, explicit effects/grants/plans, verification and recovery remain. The authority cannot be requested from remote JSON, database content or tokens. A remote stdio-to-HTTP client is a different product and remains API-ACL-restricted.

MCP stdout contains JSON-RPC only. Legacy command JSON/NDJSON framing, input bounds, EOF and nonzero outcomes are preserved independently. Isolate Joomla banners, ANSI messages and native command diagnostics; do not convert warnings/partial mutation into an empty success envelope.

## Required JCB integration

Full JCB API and CLI support is a first-class server objective; this plugin supplies its local console adaptation. See JCB-INTEGRATION.md and the canonical component roadmap. JCB's own command plugin remains responsible for registering native compiler/package commands. Inventory actual registered names/aliases/arguments/options after all relevant plugins load; do not duplicate command registrations or invent names from entity counts.

Shared component services implement JCB provider/schema/action/binding/target resolution, reviewed native/API adapters, durable jobs/artifacts and verification. The plugin must support invoking those bindings while preserving native compiler/global/environment/file/dependency/output semantics. No separate catalogue or JCB compiler copy belongs here.

Job workers need explicit authority provenance. A job requested over HTTP must retain the initiating Joomla user's permission limits, even when processed by a local worker; only jobs requested through the trusted local track have unrestricted server-owner authority. State/identity/input/factory isolation and cancellation/reconciliation are part of the handler contract.

## Distribution and acceptance

Require compatible Joomla/PHP and component dependencies. Preserve installation enablement/settings through updates, provide a standalone PHP-built plugin archive and join the component's .octojpack package assembly. Versioned update feeds may reference only published archives. Retain original notices.

Validate native DI/event/namespace/manifest contracts and actual legacy/stdio command execution in disposable Joomla. Extend that matrix to JCB absent/disabled/installed/upgraded; registered compiler/package operations, persisted entity/repository changes, output archives, partial failures and cleanup. Source/packaging tests alone are not installed-runtime certification. Track current evidence and remaining work in IMPLEMENTATION.md.
