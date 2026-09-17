<?php
/**
 * @package    JoomEngine.Mcp
 * @created    17 September 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @copyright  Copyright (C) 2026 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseInterface;


defined('_JEXEC') or die;

/**
 * Validate dependencies and preserve operator settings on upgrades.
 *
 * @since  0.1.0
 */
class PlgConsoleJoomengine_mcpInstallerScript
{
	/**
	 * Reject unsupported PHP/Joomla or a missing component dependency.
	 *
	 * @param   string            $type    Installer operation.
	 * @param   InstallerAdapter  $parent  Joomla installer adapter.
	 * @return  bool
	 * @since   0.1.0
	 */
	public function preflight(string $type, InstallerAdapter $parent): bool
	{
		if ($type === 'uninstall')
		{
			return true;
		}

		if (version_compare(PHP_VERSION, '8.3.0', '<') || version_compare((new Version())->getShortVersion(), '6.1.0', '<'))
		{
			Factory::getApplication()->enqueueMessage('JoomEngine MCP requires Joomla 6.1 or later and PHP 8.3 or later.', 'error');

			return false;
		}

		$database = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $database->getQuery(true)
			->select($database->quoteName(['enabled', 'manifest_cache']))
			->from($database->quoteName('#__extensions'))
			->where($database->quoteName('type') . ' = ' . $database->quote('component'))
			->where($database->quoteName('element') . ' = ' . $database->quote('com_joomengine_mcp'));
		$component = $database->setQuery($query)->loadAssoc();
		$manifest = json_decode($component['manifest_cache'] ?? '{}', true);

		if (!$component || (int) $component['enabled'] !== 1 || version_compare((string) ($manifest['version'] ?? '0.0.0'), '0.1.0', '<'))
		{
			Factory::getApplication()->enqueueMessage('Install and enable the JoomEngine MCP component before its console plugin.', 'error');

			return false;
		}

		return true;
	}

	/**
	 * Enable a newly installed console adapter, without changing upgrade choices.
	 *
	 * @param   string            $type    Installer operation.
	 * @param   InstallerAdapter  $parent  Joomla installer adapter.
	 * @return  void
	 * @since   0.1.0
	 */
	public function postflight(string $type, InstallerAdapter $parent): void
	{
		if ($type !== 'install')
		{
			return;
		}

		$database = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $database->getQuery(true)
			->update($database->quoteName('#__extensions'))
			->set($database->quoteName('enabled') . ' = 1')
			->where($database->quoteName('type') . ' = ' . $database->quote('plugin'))
			->where($database->quoteName('folder') . ' = ' . $database->quote('console'))
			->where($database->quoteName('element') . ' = ' . $database->quote('joomengine_mcp'));
		$database->setQuery($query)->execute();
	}
}
