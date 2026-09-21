<?php
/**
 * @package    JoomEngine.Mcp
 * @created    17 September 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @copyright  Copyright (C) 2026 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */
namespace VDM\Plugin\Console\JoomEngineMcp\Installer;


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseInterface;
use RuntimeException;
use Throwable;


/**
 * Joomla-native dependency and version checks for the independent console adapter.
 *
 * @since 0.1.0
 */
final class InstallerScript implements InstallerScriptInterface
{
	/** @var DatabaseInterface Joomla extension registry. @since 0.1.0 */
	private DatabaseInterface $database;
	/** @var CMSApplicationInterface Native installer application. @since 0.1.0 */
	private CMSApplicationInterface $application;

	/** @param DatabaseInterface $database Native database. @param CMSApplicationInterface $application Actual installer. @since 0.1.0 */
	public function __construct(DatabaseInterface $database, CMSApplicationInterface $application)
	{
		$this->database = $database;
		$this->application = $application;
	}

	/** @inheritDoc */
	public function preflight(string $type, InstallerAdapter $adapter): bool
	{
		if ($type === 'uninstall')
		{
			return true;
		}

		try
		{
			$version = (new Version())->getShortVersion();

			if (version_compare(PHP_VERSION, '8.3.0', '<') || version_compare($version, '6.1.0', '<') || version_compare($version, '7.0.0', '>='))
			{
				throw new RuntimeException('JoomEngine MCP requires Joomla 6.1–6.x and PHP 8.3 or later.');
			}

			$db = $this->database;
			$query = $db->createQuery()->select($db->quoteName(['enabled', 'manifest_cache']))->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote('component'))
				->where($db->quoteName('element') . ' = ' . $db->quote('com_joomengine_mcp'));
			$row = $db->setQuery($query)->loadAssoc();
			$manifest = json_decode($row['manifest_cache'] ?? '{}', true);
			$componentVersion = (string) ($manifest['version'] ?? '0');
			$pluginVersion = (string) $adapter->getManifest()->version;

			if ($row === null || (int) $row['enabled'] !== 1 || version_compare($componentVersion, '0.1.1', '<')
				|| explode('.', $componentVersion)[0] !== explode('.', $pluginVersion)[0]
				|| !is_file(JPATH_ADMINISTRATOR . '/components/com_joomengine_mcp/vendor/autoload.php'))
			{
				throw new RuntimeException('Install and enable the built JoomEngine MCP component version 0.1.1 or later in the same major version before its console plugin.');
			}

			return true;
		}
		catch (Throwable $error)
		{
			$this->application->enqueueMessage($error instanceof RuntimeException ? $error->getMessage() : 'The console plugin dependency check failed.', 'error');

			return false;
		}
	}

	/** @inheritDoc */
	public function install(InstallerAdapter $adapter): bool
	{
		$db = $this->database;
		$query = $db->createQuery()->update($db->quoteName('#__extensions'))->set($db->quoteName('enabled') . ' = 1')
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
			->where($db->quoteName('folder') . ' = ' . $db->quote('console'))
			->where($db->quoteName('element') . ' = ' . $db->quote('joomengine_mcp'));
		$db->setQuery($query)->execute();

		return true;
	}

	/** @inheritDoc */
	public function update(InstallerAdapter $adapter): bool
	{
		return true;
	}

	/** @inheritDoc */
	public function uninstall(InstallerAdapter $adapter): bool
	{
		return true;
	}

	/** @inheritDoc */
	public function postflight(string $type, InstallerAdapter $adapter): bool
	{
		return true;
	}
}
