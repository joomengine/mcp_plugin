# MCP plugin architecture

This repository contains the Joomla integration plugin for `joomengine/mcp_component`. Joomla's current plugin, event, routing, authentication and installer contracts are authoritative. Follow JCB-generated plugin layout: root extension manifest, `services/provider.php`, `src/Extension`, language files, installer and update/changelog metadata. Do not nest the installable project inside a second plugin directory.

The plugin is a thin adapter: register supported endpoints and connect Joomla lifecycle events to the component. Application logic, policy evaluation, consent, protocol processing and audit belong to the component. No duplicated runtime or alternate security decisions belong here. Missing or incompatible component dependencies must fail closed without breaking unrelated Joomla requests.

The existing reference is `joomengine/joomla-mcp` and its Joomla-native action/security contracts. This is JCB-aligned hand-authored source, not a claim that a JCB blueprint already exists. Both new repositories started with README and licence files only.

## Delivery requirements

- Explicit extension element/group and namespaced dependency-injected plugin.
- No public execution routes or authentication bypass.
- No automatic trust in arbitrary client confirmation flags.
- Installer/version checks that preserve operator choices during upgrades.
- Matching component/plugin/package versions, update and changelog XML.
- Component-owned `.octojpack` distribution configuration referencing this repository.
- Contract, installation and request-level verification, reported honestly.

Component implementation: https://github.com/joomengine/mcp_component/pull/1
JCB reference: https://github.com/joomengine/Joomla-Component-Builder/tree/6.x
