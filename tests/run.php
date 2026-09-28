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

if ($manifest === false || (string) $manifest['type'] !== 'plugin' || (string) $manifest['group'] !== 'console'
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

/** Every installed file must already exist in the downloaded source tree. */
$sourcePath = static function (string $relative) use ($root): string
{
	$resolved = realpath($root . '/' . $relative);

	if ($relative === '' || str_starts_with($relative, '/') || str_contains($relative, '\\')
		|| in_array('..', explode('/', $relative), true) || $resolved === false
		|| !str_starts_with($resolved, $root . '/') || is_link($root . '/' . $relative))
	{
		throw new RuntimeException('Missing or unsafe manifest source path: ' . $relative);
	}

	return $resolved;
};
$installed = ['joomengine_mcp.xml' => true];
$script = (string) $manifest->scriptfile;

if (!is_file($sourcePath($script)))
{
	throw new RuntimeException('The installer script is missing from the source tree.');
}

$installed[$script] = true;

foreach ($manifest->files->children() as $entry)
{
	$relative = (string) $entry;
	$path = $sourcePath($relative);

	if ($entry->getName() === 'folder')
	{
		if (!is_dir($path))
		{
			throw new RuntimeException('A manifest folder is not a source directory: ' . $relative);
		}

		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $file)
		{
			if ($file->isLink())
			{
				throw new RuntimeException('Installed plugin sources must not contain symbolic links.');
			}

			if ($file->isFile())
			{
				$installed[substr($file->getPathname(), strlen($root) + 1)] = true;
			}
		}
	}
	elseif ($entry->getName() === 'filename' && is_file($path))
	{
		$installed[$relative] = true;
	}
	else
	{
		throw new RuntimeException('Invalid manifest file entry: ' . $relative);
	}
}

foreach ($manifest->languages->language as $entry)
{
	if (!is_file($sourcePath((string) $entry)) || parse_ini_file($sourcePath((string) $entry)) === false)
	{
		throw new RuntimeException('The plugin language source is missing or invalid.');
	}
}

foreach (['services/provider.php', 'src/Extension/JoomEngineMcpPlugin.php', 'src/Console/McpCommand.php',
	'src/Console/OutputGuard.php', 'src/Installer/InstallerScript.php', 'script.php', 'LICENSE'] as $required)
{
	if (!isset($installed[$required]))
	{
		throw new RuntimeException('The source manifest does not install a runtime dependency: ' . $required);
	}
}

echo json_encode(['manifest' => 'passed', 'languages' => 'passed', 'sourceInstallation' => 'complete',
	'installedRuntime' => 'Run tests/installed.php separately.'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
