<?php
/**
 * @package    JoomEngine.Mcp
 * @created    17 September 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @copyright  Copyright (C) 2026 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

if (PHP_SAPI !== 'cli' || !class_exists(ZipArchive::class))
{
	fwrite(STDERR, "Build requires PHP CLI with the zip extension.\n");
	exit(1);
}

$root = __DIR__;
$manifest = simplexml_load_file($root . '/joomengine_mcp.xml');

if ($manifest === false || preg_match('/\A\d+\.\d+\.\d+(?:-[a-zA-Z0-9.-]+)?\z/D', (string) $manifest->version) !== 1)
{
	throw new RuntimeException('Invalid plugin manifest version.');
}

$files = ['joomengine_mcp.xml', 'script.php', 'LICENSE'];

foreach (['src', 'services', 'language'] as $directory)
{
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS)) as $file)
	{
		if ($file->isLink())
		{
			throw new RuntimeException('Plugin archives may not contain symbolic links.');
		}

		if ($file->isFile())
		{
			$files[] = substr($file->getPathname(), strlen($root) + 1);
		}
	}
}

sort($files, SORT_STRING);
$output = $root . '/build';

if (!is_dir($output) && !mkdir($output, 0775, true))
{
	throw new RuntimeException('Cannot create the build directory.');
}

$path = $output . '/plg_console_joomengine_mcp-' . (string) $manifest->version . '.zip';
$zip = new ZipArchive();

if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true)
{
	throw new RuntimeException('Cannot create the plugin archive.');
}

$epoch = getenv('SOURCE_DATE_EPOCH');
$mtime = $epoch !== false && ctype_digit($epoch) ? max(315532800, (int) $epoch) : 1789603200;

foreach ($files as $file)
{
	if (!$zip->addFile($root . '/' . $file, $file) || !$zip->setMtimeName($file, $mtime))
	{
		throw new RuntimeException('Cannot add a file to the plugin archive.');
	}
}

if (!$zip->close())
{
	throw new RuntimeException('Cannot finalize the plugin archive.');
}

file_put_contents($path . '.sha256', hash_file('sha256', $path) . '  ' . basename($path) . "\n");
echo $path . PHP_EOL;
