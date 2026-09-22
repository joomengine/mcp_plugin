# JoomEngine MCP console plugin

PHP-only local Joomla console integration for `com_joomengine_mcp`.

Requires the built component version **0.1.1 or later in the same major version**, including its explicit JCB synchronization runtime.

**Element:** `joomengine_mcp`  
**Group:** `console`  
**Extension:** `plg_console_joomengine_mcp`  
**Namespace:** `VDM\Plugin\Console\JoomEngineMcp`

The plugin connects Joomla's console lifecycle to the component-owned database catalogue and execution engine. It provides the `joomla:mcp:serve`, `describe`, `dispatch`, `self-test`, `cli-inventory` and `jcb-sync` adapters. It does not contain a second MCP catalogue or an HTTP webservices plugin.

## Three repository boundaries

- [`mcp_component`](https://github.com/joomengine/mcp_component): installed server, database definitions, HTTP authentication/ACL/routing, administrator application, API/native handlers, durable plans/jobs and verification.
- This repository: trusted local console entry, typed command/runtime integration, protocol output isolation and plugin distribution.
- [`mcp_client`](https://github.com/joomengine/mcp_client): external Composer client `joomengine/mcp-client` and remote stdio bridge. Neither component nor plugin depends on it.

Direct local server stdio is not the remote bridge. A client talking over HTTP remains restricted by its Joomla API token regardless of whether it speaks stdio to an AI application on the workstation.

## Required JCB coverage

The server and this plugin must support **all actual Joomla Component Builder API and registered CLI capabilities**, alongside Joomla core. For this plugin that includes correct discovery/invocation of JCB's compiler and package get/init/pull/push/reset commands, all registered entity/area variants, native options, output/exit semantics and long-operation handling through the shared component.

Read [JCB integration responsibilities](docs/JCB-INTEGRATION.md). JCB handlers, database synchronization and durable jobs are implemented in the component and consumed by this plugin. The [component acceptance checklist](https://github.com/joomengine/mcp_component/pull/1#issuecomment-5732685349) records installed compiler/package/job results and native limitations; command inventory alone is not proof of successful execution. The plugin does not copy JCB's compiler or register duplicate `componentbuilder:*` commands.

After installing or upgrading JCB, the server owner runs `php cli/joomla.php joomla:mcp:jcb-sync` to synchronize reviewed installed JCB definitions through the component. The plugin only forwards this explicit local operation; schema discovery, identity validation and persisted catalogue updates remain component-owned.

## Status and local authority

Implementation is on `feature/jcb-mcp-runtime` / [PR #1](https://github.com/joomengine/mcp_plugin/pull/1). The native provider, lazy command adapters, output guard, installer and PHP-only package builder are implemented. Native Joomla console tests cover registration, global options, typed runtime delegation and output restoration; installed workflows exercise this checkout through the actual Joomla CLI and the shared JCB runtime. The PR records current check results and review status; [implementation evidence](docs/IMPLEMENTATION.md) describes the verification layers.

Local execution uses the genuine Joomla console application under CLI SAPI, without a Joomla API token or row-viewing-level restriction. Input validation, explicit action semantics, grants/plans, bounded output, audit, verification and recovery still apply. HTTP requests and database values cannot manufacture this local privilege.

Original migration source: `joomengine/joomla-mcp@2cff50f4f6b440da3c684f9995a77efad32e1a36`, especially companion/plugin. Preserve licences and all supported request/result/command behaviours. The source repository is unchanged.

## Verification and release

Run `php tests/run.php` and `php tests/release.php` for packaging and publication metadata checks. With a full Joomla distribution in `JOOMLA_ROOT` and the component checkout in `MCP_COMPONENT_SOURCE`, run `php tests/native.php` for actual Joomla class contracts. Installed acceptance requires the component's disposable fixture and `MCP_PLUGIN_SOURCE` pointing to this checkout; its runner installs the plugin and executes `tests/installed.php` before teardown.

Release publication is an explicit manual workflow on `main`, after merge and review. It runs installed acceptance against the component's `main`, refuses an existing version tag, publishes the versioned archive and checksum, downloads and verifies those assets, then commits the update feed. The feed remains empty until an archive is published. The component owns combined server package assembly.
