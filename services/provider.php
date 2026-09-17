<?php
/**
 * @package    JoomEngine.Mcp
 * @created    17 September 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @copyright  Copyright (C) 2026 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use VDM\Plugin\Console\JoomEngineMcp\Extension\JoomEngineMcpPlugin;


defined('_JEXEC') or die;

/** Compose the plugin at Joomla's extension-service boundary. */
return new class implements ServiceProviderInterface
{
	/**
	 * Register the console plugin without booting the MCP component eagerly.
	 *
	 * @param   Container  $container  Joomla extension container.
	 * @return  void
	 * @since   0.1.0
	 */
	public function register(Container $container): void
	{
		$container->set(PluginInterface::class, static function (Container $container): PluginInterface
		{
			$plugin = new JoomEngineMcpPlugin(
				(array) PluginHelper::getPlugin('console', 'joomengine_mcp')
			);
			$plugin->setApplication(Factory::getApplication());

			return $plugin;
		});
	}
};
