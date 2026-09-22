# Required JCB console integration

## Canonical scope

The authoritative cross-repository roadmap and planning inventory live in the component:

- https://github.com/joomengine/mcp_component/blob/feature/jcb-mcp-runtime/docs/integrations/JCB.md
- https://github.com/joomengine/mcp_component/blob/feature/jcb-mcp-runtime/docs/integrations/jcb-surface.json

JCB source is pinned to `extension-builder/joomla@5ee658dd07eb749dca43ed4722f6cca7eb8208cf`; CLI documentation to `joomengine/jcb-documentation@ecd3670232d344295fc4f673b2d3dc40a64b3bf6`, english/CLI-Command-Suite.md. Full JCB support is mandatory alongside Joomla core. The shared component implements installed discovery, planning and job execution. This page defines the plugin contract; IMPLEMENTATION.md describes verification layers, and the [component acceptance checklist](https://github.com/joomengine/mcp_component/pull/1#issuecomment-5732685349) records current compiler/package/job results and native limitations.

## Native registration and invocation

JCB's package identifies a separate `ComponentBuilderCommands` console plugin. Keep its command ownership intact. Capture the installed registry after registration: exact names, aliases, InputDefinitions, implementation identity, arguments, defaults and option modes. The documented pattern is componentbuilder:<action>:<area>, with componentbuilder:compile:component explicitly documented, but the 45-entity factory map does not prove that every verb/area combination is registered. Additional actual commands are also in scope.

Let the component's JCB handler/provider and database targets select reviewed command objects or native services. Do not add a plugin hard-coded duplicate list, arbitrary class resolver or generic process/shell endpoint. Command discovery alone does not establish execution parity. Preserve unrelated Joomla/core commands when JCB is missing or disabled.

## Families and semantics to preserve

Cover compiler and every actually registered get/init/pull/push/reset package command. Package get synchronizes definitions/dependencies and can write; it is not a normal read-only entity getter. Init/pull/reset have their own initialization/overwrite/tracking/dependency semantics. Push publishes configured remote definition graphs synchronously and can have partial external effects despite a command returning normally.

Retain GUID/identifier validation, CSV/newline/JSON, native local @file/--items-file forms, repository/force/resolve options where supported, global/environment fallbacks and exact defaults. Do not silently discard an option that needs an explicitly designed HTTP counterpart. Never forward a workstation token or URL as authority to run unrestricted local operations.

Compiler parity includes selectors, options bundles, backup/local repository export, placeholders/debug/minify/powers/power repository, target Joomla 3/4/5/6, indentation/build dates, and compile-install. Host Joomla compatibility and output target compatibility differ. Omission preserves native GLOBAL behaviour; freeze the reviewed effective options/environment and reject stale plans.

## Output, jobs and state isolation

The compiler deliberately separates machine paths on stdout from human diagnostics on stderr. Preserve that distinction, all native exit codes and per-component results; do not let either stream corrupt MCP framing. Verify output/archive existence and hashes through the component's artifact service. Redact credential-bearing repository messages and sensitive paths for remote job consumers.

JCB factories, message buses and dependency queues contain mutable operation state. Resolve related services from the same factory, reset them correctly or use isolated workers, and restore Joomla identity/input after invocation. Test consecutive requests for state leakage.

Long work uses the component-owned durable execution/job/lease/artifact protocol. A client disconnect is not a rollback or permission to replay. Cancellation/restart/expired leases need honest partial/uncertain outcomes. HTTP-originated jobs retain the requester's Joomla ACL and scoped consent; local worker execution must not turn them into server-owner jobs. No blanket HTTP-to-CLI privilege bridge is allowed.

## Plugin implementation and acceptance

The implemented lazy adapters forward native input/output and exit status to the component without shadowing JCB's command plugin. Native and installed tests cover registration order, global options, framing/EOF/bounds, nonzero exits and output restoration; the explicit `jcb-sync` entrypoint delegates inventory and persistence to the component.

The component's disposable Joomla matrix exercises real package/compiler workflows, persisted read-back, artifacts, cleanup and original options/environment semantics. Its acceptance contract also covers missing/disabled JCB behavior, dependency handling, state isolation, principal/grant boundaries, concurrent jobs, cancellation and recovery. Exact component/JCB/plugin revisions and current results belong to the linked acceptance checklist and run artifacts, including explicit inherited native limitations.

The external `joomengine/mcp-client` only discovers and consumes the server contract. This plugin must never depend on its client package or duplicate its remote bridge.
