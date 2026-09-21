<?php
/**
 * @package    JoomEngine.Mcp
 * @created    21 September 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @copyright  Copyright (C) 2026 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

/** Generate Joomla update metadata only for an already published verified release. */
$root = dirname(__DIR__);
$manifest = simplexml_load_file($root . '/joomengine_mcp.xml');
$version = (string) $manifest->version;
$metadata = $argv[1] ?? '';
$archive = $argv[2] ?? '';

if (PHP_SAPI !== 'cli' || !is_file($metadata) || !is_file($archive)
	|| preg_match('/\A\d+\.\d+\.\d+\z/D', $version) !== 1)
{
	throw new RuntimeException('Supply published GitHub release JSON and its downloaded plugin ZIP for a stable manifest version.');
}

$release = json_decode(file_get_contents($metadata), true, 64, JSON_THROW_ON_ERROR);
$tag = 'v' . $version;
$filename = 'plg_console_joomengine_mcp-' . $version . '.zip';
$base = 'https://github.com/joomengine/mcp_plugin/releases/';
$url = $base . 'download/' . $tag . '/' . $filename;

if (($release['tag_name'] ?? '') !== $tag || ($release['draft'] ?? true) || ($release['prerelease'] ?? true)
	|| ($release['html_url'] ?? '') !== $base . 'tag/' . $tag || empty($release['published_at']) || basename($archive) !== $filename)
{
	throw new RuntimeException('Update feeds require the matching published stable GitHub release.');
}

$assets = [];

foreach ($release['assets'] ?? [] as $asset)
{
	$assets[$asset['name']] = $asset;
}

foreach ([$filename, $filename . '.sha256'] as $asset)
{
	if (($assets[$asset]['state'] ?? '') !== 'uploaded'
		|| ($assets[$asset]['browser_download_url'] ?? '') !== $base . 'download/' . $tag . '/' . $asset)
	{
		throw new RuntimeException('A release archive or checksum has not been published at its immutable version URL.');
	}
}

$checksum = hash_file('sha256', $archive);
$expected = is_file($archive . '.sha256') ? trim(file_get_contents($archive . '.sha256')) : '';

if (!hash_equals($checksum . '  ' . $filename, $expected) || (int) ($assets[$filename]['size'] ?? -1) !== filesize($archive))
{
	throw new RuntimeException('The downloaded release archive does not match its published checksum or asset size.');
}

$zip = new ZipArchive();

if ($zip->open($archive) !== true)
{
	throw new RuntimeException('The published archive is not a ZIP.');
}

$packaged = simplexml_load_string((string) $zip->getFromName('joomengine_mcp.xml'));
$zip->close();

if ($packaged === false || (string) $packaged->version !== $version
	|| (string) $packaged['group'] !== 'console' || (string) $packaged->namespace !== (string) $manifest->namespace)
{
	throw new RuntimeException('The published archive has a different extension identity or version.');
}

$document = new DOMDocument('1.0', 'utf-8');
$document->preserveWhiteSpace = false;
$document->formatOutput = true;

if (!$document->load($root . '/joomengine_mcp_update_server.xml', LIBXML_NONET) || $document->documentElement->nodeName !== 'updates')
{
	throw new RuntimeException('The existing update feed is invalid.');
}

$query = new DOMXPath($document);

foreach ($query->query('/updates/update[version="' . $version . '"]') as $old)
{
	$old->parentNode->removeChild($old);
}

$update = $document->createElement('update');
$append = static function (DOMNode $parent, string $name, string $value) use ($document): DOMElement
{
	$element = $document->createElement($name);
	$element->appendChild($document->createTextNode($value));
	$parent->appendChild($element);

	return $element;
};
$append($update, 'name', 'JoomEngine MCP Console');
$append($update, 'description', 'Local Joomla console integration for JoomEngine MCP.');
$append($update, 'element', 'joomengine_mcp');
$append($update, 'type', 'plugin');
$append($update, 'folder', 'console');
$append($update, 'version', $version);
$downloads = $document->createElement('downloads');
$update->appendChild($downloads);
$download = $append($downloads, 'downloadurl', $url);
$download->setAttribute('type', 'full');
$download->setAttribute('format', 'zip');
$append($update, 'sha256', $checksum);
$append($update, 'tags', '')->appendChild($document->createElement('tag', 'stable'));
$target = $append($update, 'targetplatform', '');
$target->setAttribute('name', 'joomla');
$target->setAttribute('version', '6\\.[1-9][0-9]*');
$append($update, 'php_minimum', '8.3.0');
$append($update, 'detailsurl', $release['html_url']);
$document->documentElement->appendChild($update);

if ($document->save($root . '/joomengine_mcp_update_server.xml') === false)
{
	throw new RuntimeException('Cannot save verified release metadata.');
}

echo 'Published update metadata for ' . $tag . PHP_EOL;
