# JoomEngine MCP console plugin

PHP-only Joomla console integration for [`com_joomengine_mcp`](https://github.com/joomengine/mcp_component).

**Element:** `joomengine_mcp`  
**Group:** `console`  
**Extension:** `plg_console_joomengine_mcp`  
**Namespace:** `VDM\Plugin\Console\JoomEngineMcp`

This repository migrates the existing `joomengine/joomla-mcp` PHP companion into a separately installable plugin. It connects Joomla's console application to the component's shared database-driven MCP engine and provides direct PHP stdio serving. It is not the HTTP webservices routing plugin; that small adapter is distributed with the component.

## Authority and status

Local console execution is the trusted-server track requested by the project owner. It does not need a Joomla API token or row viewing-level authorization. Input validation, explicit action semantics, bounded execution, audit, verification and recovery remain mandatory. No HTTP request or database row can opt into this local privilege.

Work remains on `feature/jcb-mcp-runtime`, draft PR #1. Architecture precedes runtime. See [implementation status](docs/IMPLEMENTATION.md), [architecture](docs/ARCHITECTURE.md) and [agent instructions](AGENTS.md). No production certification is implied by an install manifest, a catalogue entry or a syntax-only test.

Original migration source: `joomengine/joomla-mcp@2cff50f4f6b440da3c684f9995a77efad32e1a36`, especially `companion/plugin`. Preserve all original licences, supported commands, request/result contracts and native operation behaviour. The original repository is not modified.
