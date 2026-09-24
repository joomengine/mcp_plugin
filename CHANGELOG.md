# Changelog

## Unreleased

- Establish the exact joomengine_mcp console plugin identity, local-server authority and shared component contract.
- Add native plugin/provider/lazy command adapters, output isolation, installer checks, languages/update metadata and PHP-only reproducible packaging.
- Expose explicit local JCB catalogue synchronization through the component-owned runtime without replacing JCB's commands.
- Preserve native global options, atomic command registration and output restoration after console errors.
- Verify native Joomla console contracts and 18 actual installed command/stdio assertions, including whitespace and exact-limit NDJSON frames, on PHP 8.3 and 8.4.
- Verify coordinated installed component/plugin/client execution on MySQL and PostgreSQL; retain separate JCB golden-image evidence in the component acceptance checklist.
- Add explicit main-only release publication with verified versioned archives, checksums and post-publication update metadata.
- Separate external Composer-client/remote-bridge ownership into `joomengine/mcp_client`; no server/plugin dependency on that package.
- Require complete first-class JCB API/CLI coverage and document native command registration, compiler/package semantics, shared jobs and installed acceptance responsibilities.

Exact tested revisions and workflow results are recorded in [implementation evidence](docs/IMPLEMENTATION.md). Coordinated JCB compiler/package/job acceptance is tracked in the [component checklist](https://github.com/joomengine/mcp_component/pull/1#issuecomment-5732685349). No release has been published by this implementation work.
