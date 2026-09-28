<?php
/**
 * @package    JoomEngine.Mcp
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @copyright  Copyright (C) 2026 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

require dirname(__DIR__) . '/tools/release.php';
$root = sys_get_temp_dir() . '/mcp-release-' . bin2hex(random_bytes(8));
mkdir($root, 0700);
$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void
{
	if (!$condition)
	{
		throw new RuntimeException($message);
	}

	$checks++;
};

try
{
	$pending = '<changelogs><changelog><element>joomengine_mcp</element><type>plugin</type><folder>console</folder>'
		. '<version>[[[NEXT_VERSION]]]</version><fix><item>Release metadata.</item></fix></changelog></changelogs>';
	file_put_contents($root . '/joomengine_mcp.xml', '<extension type="plugin" group="console">'
		. '<version>1.0.0</version><creationDate>January 2026</creationDate></extension>');
	file_put_contents($root . '/joomengine_mcp_changelog.xml', $pending);
	file_put_contents($root . '/CHANGELOG.md', "# Changelog\n\n## [[[NEXT_VERSION]]]\n\n### Fix\n\n- Release metadata.\n");
	file_put_contents($root . '/joomengine_mcp_update_server.xml', '<updates/>');

	$originalFeed = file_get_contents($root . '/joomengine_mcp_update_server.xml');
	mcpRelease(['prepare', 'v1.1.0'], $root);
	$manifest = mcpReleaseXml($root . '/joomengine_mcp.xml');
	$check($manifest->getElementsByTagName('version')->item(0)->textContent === '1.1.0', 'Manifest uses the selected version.');
	$check($manifest->getElementsByTagName('creationDate')->item(0)->textContent === gmdate('F Y'), 'Manifest date is updated.');
	$check(!str_contains(file_get_contents($root . '/CHANGELOG.md'), '[[[NEXT_VERSION]]]')
		&& !str_contains(file_get_contents($root . '/joomengine_mcp_changelog.xml'), '[[[NEXT_VERSION]]]'), 'Both changelogs are frozen.');
	$check(file_get_contents($root . '/joomengine_mcp_update_server.xml') === $originalFeed, 'Preparing a tag leaves the feed unchanged.');

	mcpRelease(['feed', '1.1.0'], $root);
	$feed = mcpReleaseXml($root . '/joomengine_mcp_update_server.xml');
	$query = new DOMXPath($feed);
	$check($query->evaluate('string(/updates/update[version="1.1.0"]/downloads/downloadurl)')
		=== 'https://github.com/joomengine/mcp_plugin/archive/refs/tags/v1.1.0.zip', 'Feed downloads the immutable source tag.');
	$check($query->evaluate('string(/updates/update[version="1.1.0"]/folder)') === 'console', 'Feed identifies the console plugin.');
	$check($query->query('/updates/update[version="1.1.0"]/sha512')->length === 0, 'Checksum generation belongs to OctoShoom.');
	mcpReleaseAppend($feed->documentElement->firstChild, 'sha512', str_repeat('a', 128));
	$feed->save($root . '/joomengine_mcp_update_server.xml');
	$hashedFeed = file_get_contents($root . '/joomengine_mcp_update_server.xml');
	mcpRelease(['feed', '1.1.0'], $root);
	$check(file_get_contents($root . '/joomengine_mcp_update_server.xml') === $hashedFeed, 'Retry preserves existing feed bytes and hashes.');

	file_put_contents($root . '/joomengine_mcp_changelog.xml', str_replace('<changelogs>',
		'<changelogs>' . preg_replace('#</?changelogs>#', '', $pending), file_get_contents($root . '/joomengine_mcp_changelog.xml')));
	file_put_contents($root . '/CHANGELOG.md', "## [[[NEXT_VERSION]]]\n\n### Fix\n\n- Next release.\n\n"
		. file_get_contents($root . '/CHANGELOG.md'));

	mcpRelease(['prepare', '1.2.0'], $root);
	mcpRelease(['feed', '1.2.0'], $root);
	$query = new DOMXPath(mcpReleaseXml($root . '/joomengine_mcp_update_server.xml'));
	$check($query->query('/updates/update')->length === 2, 'Next release retains the previous update.');
	$check($query->evaluate('string(/updates/update[version="1.1.0"]/sha512)') === str_repeat('a', 128), 'Next release retains the previous checksum.');

	foreach (['01.1.0', '1.0', '1.0.0;false'] as $invalid)
	{
		$rejected = false;

		try
		{
			mcpRelease(['prepare', $invalid], $root);
		}
		catch (RuntimeException)
		{
			$rejected = true;
		}

		$check($rejected, 'Invalid versions are rejected.');
	}

	echo json_encode(['checks' => $checks, 'metadataTransitions' => 'passed'], JSON_THROW_ON_ERROR) . "\n";
}
finally
{
	foreach (glob($root . '/*') as $path)
	{
		unlink($path);
	}

	rmdir($root);
}
