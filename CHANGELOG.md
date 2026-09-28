# Changelog

## [[[NEXT_VERSION]]]

### Addition

- Add a manual next-version release workflow that freezes both changelogs, creates an immutable tag, updates the native Joomla feed and waits for OctoShoom to commit its checksum.
- Add the categorized Joomla plugin changelog and document GitHub configuration, safe retries and agent responsibilities.

### Change

- Install and test the unchanged repository source ZIP; no build or Composer step is required.
- Keep combined package assembly in the component's external OctoJPack release process and its separate package repository.

### Remove

- Remove the local plugin ZIP builder and release-asset/checksum publication implementation.

### Note

- Install the compatible component before the console plugin. The plugin release does not invoke OctoJPack.
- Configure the GitHub variables and secrets documented in docs/RELEASE.md before releasing.

## 0.1.0 — development baseline

### Addition

- Establish the exact joomengine_mcp console plugin identity, local-server authority and shared component contract.
- Add native plugin/provider/lazy command adapters, output isolation, installer checks and language metadata.
- Expose explicit local JCB catalogue synchronization through the component-owned runtime without replacing JCB's commands.
- Preserve native global options, atomic command registration and output restoration after console errors.
- Verify native Joomla console contracts and installed command/stdio behavior, including whitespace and exact-limit NDJSON frames.
- Verify coordinated installed component/plugin/client execution on MySQL and PostgreSQL; retain JCB golden-image evidence in the component acceptance checklist.
- Separate external Composer-client/remote-bridge ownership into joomengine/mcp_client; no server/plugin dependency on that package.
- Document native command registration, compiler/package semantics, shared jobs and installed acceptance responsibilities.

Exact tested revisions are recorded in [implementation evidence](docs/IMPLEMENTATION.md). Development baseline entries describe source, not a previously published release. Published immutable tags establish release availability.
