<?php
/**
 * @package    JoomEngine.Mcp
 * @created    17 September 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @copyright  Copyright (C) 2026 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */
namespace VDM\Plugin\Console\JoomEngineMcp\Extension;


use Joomla\Application\ApplicationEvents;
use Joomla\CMS\Application\ConsoleApplication;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use RuntimeException;
use VDM\Component\JoomEngineMcp\Administrator\Contract\ConsoleRuntimeInterface;
use VDM\Component\JoomEngineMcp\Administrator\Contract\ConsoleRuntimeProviderInterface;
use VDM\Plugin\Console\JoomEngineMcp\Console\McpCommand;


/**
 * Registers local-only commands while keeping the component runtime shared.
 *
 * @since  0.1.0
 */
final class JoomEngineMcpPlugin extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Subscribe to Joomla's console lifecycle, not web request events.
	 *
	 * @return  array<string,string>
	 * @since   0.1.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [ApplicationEvents::BEFORE_EXECUTE => 'registerCommands'];
	}

	/**
	 * Register lazy adapters without affecting unrelated commands or web traffic.
	 *
	 * @return  void
	 * @throws  RuntimeException  When a different extension owns an MCP command.
	 * @since   0.1.0
	 */
	public function registerCommands(): void
	{
		$application = $this->getApplication();

		if (PHP_SAPI !== 'cli' || !$application instanceof ConsoleApplication)
		{
			return;
		}

		$resolve = static function () use ($application): ConsoleRuntimeInterface
		{
			$component = $application->bootComponent('com_joomengine_mcp');

			if (!$component instanceof ConsoleRuntimeProviderInterface)
			{
				throw new RuntimeException('A compatible JoomEngine MCP component must be installed and enabled.');
			}

			return $component->getConsoleRuntime($application);
		};

		foreach (['serve', 'describe', 'dispatch', 'self-test', 'cli-inventory'] as $operation)
		{
			$name = 'joomla:mcp:' . $operation;

			if ($application->hasCommand($name))
			{
				if ($application->getCommand($name) instanceof McpCommand)
				{
					continue;
				}

				throw new RuntimeException('Refusing to replace an existing Joomla MCP console command.');
			}

			$application->addCommand(new McpCommand($operation, $resolve));
		}
	}
}
