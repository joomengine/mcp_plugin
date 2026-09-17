<?php
/**
 * @package    JoomEngine.Mcp
 * @created    17 September 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @copyright  Copyright (C) 2026 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use VDM\Plugin\Console\JoomEngineMcp\Installer\InstallerScript;

\defined('_JEXEC') or die;

if (!class_exists(InstallerScript::class, false))
{
	require_once __DIR__ . '/src/Installer/InstallerScript.php';
}

/**
 * Explicitly injected native Joomla installer provider.
 *
 * @since 0.1.0
 */
return new class implements ServiceProviderInterface
{
	/** @inheritDoc */
	public function register(Container $container): void
	{
		$container->set(InstallerScriptInterface::class, static function (Container $container): InstallerScriptInterface
		{
			return new InstallerScript($container->get(DatabaseInterface::class), Factory::getApplication());
		});
	}
};
