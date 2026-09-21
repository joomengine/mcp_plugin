<?php
/**
 * @package    JoomEngine.Mcp
 * @created    21 September 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @copyright  Copyright (C) 2026 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

$root = dirname(__DIR__);
$version = (string) simplexml_load_file($root . '/joomengine_mcp.xml')->version;
$name = 'plg_console_joomengine_mcp-' . $version . '.zip';
$directory = sys_get_temp_dir() . '/mcp-plugin-release-' . bin2hex(random_bytes(8));
mkdir($directory . '/tools', 0700, true);
$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void
{
	if (!$condition)
	{
		throw new RuntimeException($message);
	}

	$checks++;
	echo 'PASS ' . $message . PHP_EOL;
};
$run = static function (string $directory, string $name): bool
{
	$argv = [$directory . '/tools/update-feed.php', $directory . '/release.json', $directory . '/' . $name];
	ob_start();

	try
	{
		require $argv[0];

		return true;
	}
	catch (RuntimeException)
	{
		return false;
	}
	finally
	{
		ob_end_clean();
	}
};

try
{
	copy($root . '/tools/update-feed.php', $directory . '/tools/update-feed.php');
	copy($root . '/joomengine_mcp.xml', $directory . '/joomengine_mcp.xml');
	copy($root . '/joomengine_mcp_update_server.xml', $directory . '/joomengine_mcp_update_server.xml');
	copy($root . '/build/' . $name, $directory . '/' . $name);
	copy($root . '/build/' . $name . '.sha256', $directory . '/' . $name . '.sha256');
	$tag = 'v' . $version;
	$base = 'https://github.com/joomengine/mcp_plugin/releases/';
	$release = ['tag_name' => $tag, 'draft' => true, 'prerelease' => false, 'published_at' => '2026-09-21T00:00:00Z',
		'html_url' => $base . 'tag/' . $tag, 'assets' => []];

	foreach ([$name, $name . '.sha256'] as $asset)
	{
		$release['assets'][] = ['name' => $asset, 'state' => 'uploaded', 'size' => filesize($directory . '/' . $asset),
			'browser_download_url' => $base . 'download/' . $tag . '/' . $asset];
	}

	$write = static fn () => file_put_contents($directory . '/release.json', json_encode($release, JSON_THROW_ON_ERROR));
	$before = file_get_contents($directory . '/joomengine_mcp_update_server.xml');
	$write();
	$check(!$run($directory, $name) && file_get_contents($directory . '/joomengine_mcp_update_server.xml') === $before,
		'Unpublished releases cannot advertise an update');
	$release['draft'] = false;
	file_put_contents($directory . '/release.json', json_encode($release, JSON_THROW_ON_ERROR));
	$check($run($directory, $name), 'Published matching release generates an update');
	$feed = simplexml_load_file($directory . '/joomengine_mcp_update_server.xml');
	$entries = $feed->xpath('update[version="' . $version . '"]');
	$check(count($entries) === 1 && (string) $entries[0]->sha256 === hash_file('sha256', $directory . '/' . $name)
		&& (string) $entries[0]->downloads->downloadurl === $release['assets'][0]['browser_download_url'],
		'Published feed binds the correct archive URL, version and checksum');
	$before = file_get_contents($directory . '/joomengine_mcp_update_server.xml');
	$check($run($directory, $name) && file_get_contents($directory . '/joomengine_mcp_update_server.xml') === $before,
		'Repeating verified metadata generation is idempotent');
	file_put_contents($directory . '/' . $name . '.sha256', str_repeat('0', 64) . '  ' . $name . "\n");
	$check(!$run($directory, $name) && file_get_contents($directory . '/joomengine_mcp_update_server.xml') === $before,
		'Mismatched downloaded checksums leave the published feed unchanged');
}
finally
{
	foreach (glob($directory . '/tools/*') as $file)
	{
		unlink($file);
	}

	rmdir($directory . '/tools');

	foreach (glob($directory . '/*') as $file)
	{
		unlink($file);
	}

	rmdir($directory);
}

echo json_encode(['checks' => $checks, 'releaseMetadata' => 'passed'], JSON_THROW_ON_ERROR) . PHP_EOL;
