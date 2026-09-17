# Console plugin architecture

## Identity and responsibility

`plg_console_joomengine_mcp`, element `joomengine_mcp`, group `console`, namespace `VDM\Plugin\Console\JoomEngineMcp`. Joomla-native plugin-root source uses `joomengine_mcp.xml`, installer, `services/provider.php`, `src/Extension`, `src/Console` and language/update/changelog files. Do not wrap it inside another plugin directory.

The plugin is the local CLI adapter to `com_joomengine_mcp`. It is not the component's HTTP webservices adapter. The component owns database catalogue/schema/binding resolution, protocol processing, reusable native and API handlers, durable plans/grants/idempotency/locks, verification and audit. No duplicated action catalogue or alternative policy engine belongs here.

## Execution boundary

Joomla console application boots enabled console plugins through its native event lifecycle. The plugin registers named commands using Joomla Console/Symfony Console contracts. Command execution verifies that the application is Joomla's console application and PHP is running in CLI before creating trusted-local context. Owning the server is the user's requested authority boundary; no API token or Joomla row view-level checks are required for this track. HTTP cannot select it.

The privileged track still validates all schemas and declarative handler bindings, uses registered native actions/stock command mappings, bounds input and execution, retains explicit destructive-action semantics and records local provenance. It does not run arbitrary PHP, SQL, class names or shell supplied by database rows. A remote PHP stdio-to-HTTP bridge remains an HTTP-token-restricted client and is not trusted CLI.

## Compatibility

Inventory and migrate every original companion entry point and action contract from `companion/plugin` at `2cff50f4f6b440da3c684f9995a77efad32e1a36`. Preserve the existing `joomla:mcp:describe`, `joomla:mcp:dispatch`, `joomla:mcp:self-test` and `joomla:mcp:cli-inventory` interfaces where confirmed in the pinned source; add a direct MCP stdio command without requiring the TypeScript process. Keep aliases explicit and tested. Preserve native-model events, filters, dry-run/preflight, partial-apply diagnostics and structured result/error shapes.

Stdio emits only JSON-RPC frames to stdout; banners/notices/logs go to stderr or are captured as diagnostics. Requests are bounded newline-delimited UTF-8 JSON. Errors cannot silently become successful empty results. The shared engine reads installed database definitions, so extensions installed by rows become available to CLI without editing this plugin.

## Installation and distribution

Require a compatible Joomla 6/PHP baseline and installed compatible component. Installation/update preserves enabled state and existing settings. Detect missing dependencies on invocation without crashing unrelated Joomla commands. Provide standalone plugin archive and join the component's `.octojpack` package assembly. Component/plugin versions are pinned together for release. Ship changelog/update metadata but no feed entry for an unpublished release. Preserve original licence notices during migration.

## Acceptance

Test namespace/autoload/manifest and DI/event contracts, old companion request/result compatibility, actual console command execution and protocol framing. On disposable Joomla, exercise native reads/writes with persisted read-back and cleanup, CLI inventory, error/partial-apply behaviour and inability for an HTTP request to manufacture local context. Record real commands/results and missing evidence in `docs/IMPLEMENTATION.md`; syntax/unit tests alone are not live certification.

## References

- Component plan: https://github.com/joomengine/mcp_component/pull/1
- Original companion: https://github.com/joomengine/joomla-mcp/tree/2cff50f4f6b440da3c684f9995a77efad32e1a36/companion/plugin
- JCB source/style: https://github.com/extension-builder/joomla/blob/main/docs/development/php-code-style.md
