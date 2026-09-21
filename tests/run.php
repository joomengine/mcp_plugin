<?php
/**
 * @package    JoomEngine.Mcp
 * @created    17 September 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @copyright  Copyright (C) 2026 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

$root = dirname(__DIR__);
$manifest = simplexml_load_file($root . '/joomengine_mcp.xml');

if ($manifest === false || (string) $manifest['group'] !== 'console'
	|| (string) $manifest->namespace !== 'VDM\\Plugin\\Console\\JoomEngineMcp'
	|| (string) $manifest->files->folder[0]['plugin'] !== 'joomengine_mcp')
{
	throw new RuntimeException('Plugin identity or namespace contract is invalid.');
}

$language = parse_ini_file($root . '/language/en-GB/plg_console_joomengine_mcp.sys.ini');

if ($language === false || !isset($language[(string) $manifest->description]))
{
	throw new RuntimeException('The plugin manifest description has no language value.');
}

$feed = simplexml_load_file($root . '/joomengine_mcp_update_server.xml');
$changelog = simplexml_load_file($root . '/joomengine_mcp_changelog.xml');

if ($feed === false || $changelog === false)
{
	throw new RuntimeException('Update or changelog XML is invalid.');
}

require $root . '/build.php';
$archive = $root . '/build/plg_console_joomengine_mcp-' . (string) $manifest->version . '.zip';
$firstHash = hash_file('sha256', $archive);
require $root . '/build.php';

if (!hash_equals($firstHash, hash_file('sha256', $archive)))
{
	throw new RuntimeException('The same plugin source did not produce a reproducible archive.');
}

$zip = new ZipArchive();
$zip->open($archive);

foreach (['joomengine_mcp.xml', 'services/provider.php', 'src/Extension/JoomEngineMcpPlugin.php', 'src/Console/McpCommand.php', 'script.php', 'LICENSE'] as $required)
{
	if ($zip->locateName($required) === false)
	{
		throw new RuntimeException('The plugin archive is missing an installation dependency.');
	}
}

for ($index = 0; $index < $zip->numFiles; $index++)
{
	$name = $zip->getNameIndex($index);
	$system = 0;
	$attributes = 0;

	if (!$zip->getExternalAttributesIndex($index, $system, $attributes)
		|| $system !== ZipArchive::OPSYS_UNIX || ($attributes >> 16) !== 0100644)
	{
		throw new RuntimeException('The plugin archive does not normalize source file permissions.');
	}

	if (str_starts_with($name, '/') || str_contains($name, '..') || str_starts_with($name, 'tests/') || str_ends_with($name, '.ts'))
	{
		throw new RuntimeException('The plugin archive contains a forbidden path.');
	}
}

$zip->close();
echo json_encode(['manifest' => 'passed', 'languages' => 'passed', 'package' => 'passed', 'reproducible' => true, 'installedRuntime' => 'Run tests/installed.php separately.'], JSON_PRETTY_PRINT) . PHP_EOL;
