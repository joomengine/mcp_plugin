# Console plugin releases

The repository source ZIP installs directly into Joomla after the compatible component is installed. There is no build, Composer step or combined package in this repository.

## Release a version

Open Actions → **Release console plugin with OctoShoom** → **Run workflow**, select `main`, and enter an unused next version. A `v` prefix is optional. For the first package, `0.1.2` is an example that is newer than the development baselines across all three extension repositories; the workflow input chooses the actual release version.

The workflow uses [git-user](https://github.com/octoleo/git-user#workflows) to configure Git authentication and signing. Its small metadata step freezes `[[[NEXT_VERSION]]]` in both changelogs, updates the manifest version/date, commits and tags the source, then adds the tagged ZIP to `joomengine_mcp_update_server.xml`. It calls the [OctoShoom action](https://github.com/octoleo/octoshoom#quick-start) directly to hash the downloads and commit the update feed.

Repository `joomengine/mcp_plugin`, branch `main` and the update-feed path are fixed in the workflow. OctoShoom inherits the Git identity and authentication from git-user. Existing tags and feed entries are left unchanged when rerunning the same version.

The plugin workflow stops after OctoShoom. To create the first combined package:

1. Run this console plugin's release workflow and wait for it to succeed.
2. In `joomengine/mcp_webservices`, use **Actions → Run workflow** to release the webservices plugin and wait for it to succeed. The two plugin releases may run independently.
3. In `joomengine/mcp_component`, run the component release workflow. OctoJPack reads its standalone `.octojpack`, selects the latest plugin tags, and publishes the package at the component version. The component workflow then updates the package feed and runs OctoShoom.

These workflows start through **Run workflow**; pushing a tag alone does not trigger them. Both plugins must have a published tag before the component release can assemble the package.

## GitHub secrets

Set these under **Settings → Secrets and variables → Actions**. The SSH identity must be allowed to push to this repository.

| Secret | Value |
| --- | --- |
| `GPG_KEY` | ASCII-armored private signing key. |
| `GPG_USER` | Signing key's user ID. |
| `SSH_KEY` | SSH private key. |
| `SSH_PUB` | Matching SSH public key. |
| `GIT_USER` | Git author name. |
| `GIT_EMAIL` | Git author email. |

No release configuration variables or token are required. The shared actions handle authentication setup, signing and hashing; this repository maintains only its version and Joomla metadata.

## Changelogs and checks

Record changes in both `CHANGELOG.md` and `joomengine_mcp_changelog.xml`, under exactly one `[[[NEXT_VERSION]]]` section. After release, create a new pending section. Keep released entries unchanged.

Joomla identity is element `joomengine_mcp`, type `plugin`, folder `console`. Categories are `security`, `fix`, `language`, `addition`, `change`, `remove`, and `note`, with `item` children and matching Markdown headings. Compatibility warnings belong under Note. The manifest links to the raw GitHub XML changelog.

Run `php tests/run.php` for source completeness and `php tests/release.php` for local metadata transitions. These checks do not publish a release. The 0.1.0 changelog describes the development baseline; published tags establish release availability.
