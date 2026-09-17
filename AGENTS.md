# Agent contract — console plugin

Complete the migration of the original PHP companion and CLI track from `joomengine/joomla-mcp@2cff50f4f6b440da3c684f9995a77efad32e1a36` without dropping commands, schemas, supported native operations, dry-run/plan, approval, verification or recovery behaviour. Share the database-driven component runtime rather than duplicating its catalogue, protocol, policy or audit implementation.

Use the existing `feature/jcb-mcp-runtime` branch and PR #1. Push cohesive commits, update `docs/IMPLEMENTATION.md` with exact outcomes/remaining work, and keep the PR draft until runtime implementation and required tests are complete. Do not create replacement branches, force-push, merge, publish releases or edit the original TypeScript repository.

Use element `joomengine_mcp`, group `console`, extension `plg_console_joomengine_mcp`, namespace `VDM\Plugin\Console\JoomEngineMcp`. The dependency is `com_joomengine_mcp`, namespace `VDM\Component\JoomEngineMcp`. HTTP webservices glue belongs to the component distribution, not this console plugin.

Joomla 6 native plugin/event/DI/console contracts are authoritative. Follow the JCB generated plugin-root layout: manifest, installer, `services/provider.php`, `src/Extension`, `src/Console`, language files, changelog and update metadata. The PHP style authority is https://github.com/extension-builder/joomla/blob/main/docs/development/php-code-style.md: tabs, LF, Allman braces, explicit typed properties/constructor injection, meaningful docblocks, no closing PHP tags or isolated strict_types/property promotion. Preserve Joomla inherited signatures. This is hand-authored JCB-aligned code, not an already imported JCB blueprint.

Only the real Joomla console application under CLI may create the privileged local execution context. JSON input, headers, tokens and database rows may not manufacture it. Local execution does not require a Joomla token/row view-level check, but still validates inputs and handler bindings and retains audit/verification/recovery. Never expose this path via an HTTP controller or spawn arbitrary row-supplied shell commands.

Stdio stdout is exclusively newline-delimited JSON-RPC. Send diagnostics to stderr and preserve nonzero failures. Bound input size/time and handle malformed messages/EOF without corrupting the next message. Missing/incompatible component dependencies should produce a clear command failure without breaking unrelated Joomla console commands.

Tests must cover service registration, installation/dependency checks, original companion contract parity, stdio protocol, malformed input, real Joomla reads/writes/read-back/cleanup, and local-vs-HTTP authority separation. Do not report mocked or syntax tests as live passes. Package versions align with the component; update feeds must not advertise unpublished artifacts. Retain source licences and notices.
