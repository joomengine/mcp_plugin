<?php
/**
 * @package    JoomEngine.Mcp
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @copyright  Copyright (C) 2026 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

/** Read repository release metadata. */
function mcpReleaseXml(string $path): DOMDocument
{
	$document = new DOMDocument('1.0', 'utf-8');
	$document->preserveWhiteSpace = false;
	$document->formatOutput = true;

	if (!$document->load($path, LIBXML_NONET) || $document->doctype !== null)
	{
		throw new RuntimeException('Invalid release XML: ' . $path);
	}

	return $document;
}

/** Append an XML element with escaped text. */
function mcpReleaseAppend(DOMNode $parent, string $name, string $value = ''): DOMElement
{
	$node = $parent->ownerDocument->createElement($name);
	$node->appendChild($parent->ownerDocument->createTextNode($value));
	$parent->appendChild($node);

	return $node;
}

/** Freeze the plugin version or add its tagged download to the Joomla feed. */
function mcpRelease(array $arguments, string $root): void
{
	[$command, $version] = array_pad($arguments, 2, '');
	$version = preg_replace('/\Av/', '', $version);

	if (!in_array($command, ['prepare', 'feed'], true)
		|| preg_match('/\A(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\z/D', $version) !== 1)
	{
		throw new RuntimeException('Usage: release.php prepare|feed VERSION');
	}

	$manifest = mcpReleaseXml($root . '/joomengine_mcp.xml');
	$manifestVersion = $manifest->getElementsByTagName('version')->item(0);
	$writes = [];

	if ($command === 'prepare')
	{
		$changelog = new DOMXPath(mcpReleaseXml($root . '/joomengine_mcp_changelog.xml'));

		if ($changelog->query('/changelogs/changelog[version="' . $version . '"]')->length !== 0
			|| $changelog->query('/changelogs/changelog[version="[[[NEXT_VERSION]]]"]')->length !== 1)
		{
			throw new RuntimeException('Choose an unreleased version with exactly one pending XML changelog version.');
		}

		foreach (['CHANGELOG.md', 'joomengine_mcp_changelog.xml'] as $path)
		{
			$contents = file_get_contents($root . '/' . $path);

			if (substr_count($contents, '[[[NEXT_VERSION]]]') !== 1
				|| version_compare($version, $manifestVersion->textContent, '<'))
			{
				throw new RuntimeException('Use an unreleased version and exactly one [[[NEXT_VERSION]]] section in each changelog.');
			}

			$writes[$path] = str_replace('[[[NEXT_VERSION]]]', $version, $contents);
		}

		$manifestVersion->nodeValue = $version;
		$manifest->getElementsByTagName('creationDate')->item(0)->nodeValue = gmdate('F Y');
		$writes['joomengine_mcp.xml'] = $manifest->saveXML();
	}
	else
	{
		if (version_compare($version, $manifestVersion->textContent, '>'))
		{
			throw new RuntimeException('Prepare the plugin version before adding its update entry.');
		}

		$feed = mcpReleaseXml($root . '/joomengine_mcp_update_server.xml');

		if ((new DOMXPath($feed))->query('/updates/update[version="' . $version . '"]')->length > 0)
		{
			return;
		}

		$entry = $feed->createElement('update');
		$feed->documentElement->insertBefore($entry, $feed->documentElement->firstChild);

		foreach (['name' => 'JoomEngine MCP Console', 'description' => 'JoomEngine MCP console plugin.',
			'element' => 'joomengine_mcp', 'type' => 'plugin', 'version' => $version, 'folder' => 'console',
			'client' => 'site'] as $name => $value)
		{
			mcpReleaseAppend($entry, $name, $value);
		}

		$download = mcpReleaseAppend(mcpReleaseAppend($entry, 'downloads'), 'downloadurl',
			'https://github.com/joomengine/mcp_plugin/archive/refs/tags/v' . $version . '.zip');
		$download->setAttribute('type', 'full');
		$download->setAttribute('format', 'zip');
		mcpReleaseAppend(mcpReleaseAppend($entry, 'tags'), 'tag', 'stable');
		$platform = mcpReleaseAppend($entry, 'targetplatform');
		$platform->setAttribute('name', 'joomla');
		$platform->setAttribute('version', '6\\.[1-9][0-9]*');
		mcpReleaseAppend($entry, 'php_minimum', '8.3.0');
		mcpReleaseAppend($entry, 'infourl', 'https://github.com/joomengine/mcp_plugin/tree/v' . $version);
		mcpReleaseAppend($entry, 'changelogurl', 'https://raw.githubusercontent.com/joomengine/mcp_plugin/main/joomengine_mcp_changelog.xml');
		$writes['joomengine_mcp_update_server.xml'] = $feed->saveXML();
	}

	foreach ($writes as $path => $contents)
	{
		if (file_put_contents($root . '/' . $path, $contents) === false)
		{
			throw new RuntimeException('Cannot write release metadata: ' . $path);
		}
	}
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__)
{
	try
	{
		mcpRelease(array_slice($argv, 1), dirname(__DIR__));
	}
	catch (Throwable $error)
	{
		fwrite(STDERR, $error->getMessage() . "\n");
		exit(1);
	}
}
