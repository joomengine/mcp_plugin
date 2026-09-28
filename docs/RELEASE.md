# Console plugin releases

The repository source ZIP is the installable Joomla console plugin. Download **Code → Download ZIP**, or a tagged source ZIP, and upload it through Joomla's extension installer after installing the compatible component. There is no local ZIP builder and no Composer step. This repository contains no combined Joomla package.

## Release a version

After merging reviewed changes and checking CI, open Actions → **Release console plugin with OctoShoom**, select the configured release branch and enter the next stable `X.Y.Z` version. A `v` prefix is optional; Git tags use `vX.Y.Z`, Joomla metadata uses `X.Y.Z`.

1. Validate configuration and run the metadata transition checks.
2. Freeze `[[[NEXT_VERSION]]]` in `CHANGELOG.md` and `joomengine_mcp_changelog.xml`; update the plugin manifest's version/date and live metadata URLs. Atomically push the metadata commit and immutable tag.
3. Add the tag's source ZIP URL to `joomengine_mcp_update_server.xml` and commit it, retaining earlier releases.
4. Run the shared OctoShoom action synchronously. It downloads the tagged archive, adds SHA-512 and commits the feed. Verify the committed checksum against the real tag download before reporting success.

The workflow stops after OctoShoom. It never invokes OctoJPack. Once this release succeeds, configure its exact `CONSOLE_TAG` in the component repository and run the component release, which invokes OctoJPack and publishes the combined package to the separate package repository.

Use the manual version workflow rather than pushing a bare tag: metadata must be frozen before the tag is created. The first run fills the initially empty feed with a real tagged download. No GitHub Release assets or fabricated historical entries are needed.

## GitHub variables and secrets

Set these under **Settings → Secrets and variables → Actions**.

| Variable | Value |
| --- | --- |
| `RELEASE_BRANCH` | Optional release branch; defaults to this repository's default branch. Select it when running the workflow. |
| `OCTOSHOOM_REPOSITORY` | Shared hash action repository, normally `octoleo/octoshoom`. |
| `OCTOSHOOM_REF` | Full reviewed 40-character commit SHA. Inspected compatible revision: `a4eba6191388335e0301f74969d92151bc0f520d`. |
| `RELEASE_SSH_KNOWN_HOSTS` | Verified GitHub SSH `known_hosts` lines; strict checking is enabled. |

| Secret | Access needed |
| --- | --- |
| `RELEASE_TOKEN` | GitHub token for source metadata/tag writes and tool-repository checkout. |
| `RELEASE_SSH_KEY` | Unencrypted SSH private key with write access to this repository, used by OctoShoom. A write-enabled deploy key or machine/user identity can be used. |

The identity must be permitted to push metadata and tags under your repository rules. Credentials stay in GitHub secrets. Source repository identity comes from `github.repository`; manifest and feed URLs are derived from it and the release branch.

The component workflow reads the update-server URL from the released console manifest, so a configured release branch is supported.

## Changelog convention

Record every meaningful change in both `CHANGELOG.md` and `joomengine_mcp_changelog.xml`. Keep exactly one pending section headed `[[[NEXT_VERSION]]]`; after release, create a new pending section for further work. The workflow replaces the marker with its version input. Never rewrite released history.

Joomla XML identity is `element` = `joomengine_mcp`, `type` = `plugin`, `folder` = `console`. Categories are `security`, `fix`, `language`, `addition`, `change`, `remove`, and `note`, containing `item` children. Use matching Markdown headings. Compatibility warnings belong under Note; errors fixed under Fix; security corrections under Security. The manifest's `changelogurl` points to the live XML file.

The 0.1.0 entry describes the development baseline, not a published tag. Select a new unused version for the first release.

## Retrying and verification

Rerun the same version after an interrupted release. The workflow verifies the existing tag's manifest/changelog identity and ancestry, leaves the tag untouched, preserves earlier update entries and resumes feed/hash work. Missing or mismatched hashes fail the workflow; they cannot produce a success summary.

Use `php tests/run.php` for source completeness and `php tests/release.php` for isolated positive/negative release transitions. CI also tests the unmodified source ZIP. Installed acceptance can select a component revision through `MCP_COMPONENT_REF` or its reusable workflow input; otherwise it checks the matching component branch when present, falling back to main. No release is published by those tests.
