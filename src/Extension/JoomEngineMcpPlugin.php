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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Console\ConsoleEvents;
use Joomla\Event\SubscriberInterface;
use RuntimeException;
use Symfony\Component\Console\Exception\ExceptionInterface;
use VDM\Component\JoomEngineMcp\Administrator\Contract\ConsoleRuntimeInterface;
use VDM\Component\JoomEngineMcp\Administrator\Contract\ConsoleRuntimeProviderInterface;
use VDM\Plugin\Console\JoomEngineMcp\Console\McpCommand;
use VDM\Plugin\Console\JoomEngineMcp\Console\OutputGuard;


/**
 * Registers local-only commands while keeping the component runtime shared.
 *
 * @since  0.1.0
 */
final class JoomEngineMcpPlugin extends CMSPlugin implements SubscriberInterface
{
	/** @var ?OutputGuard Scoped console formatter protection. @since 0.1.0 */
	private ?OutputGuard $outputGuard = null;

	/**
	 * Subscribe to Joomla's console lifecycle, not web request events.
	 *
	 * @return  array<string,string>
	 * @since   0.1.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [ApplicationEvents::BEFORE_EXECUTE => 'registerCommands',
			ApplicationEvents::AFTER_EXECUTE => 'restoreOutput', ConsoleEvents::APPLICATION_ERROR => 'restoreOutput'];
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
			if (!ComponentHelper::isEnabled('com_joomengine_mcp'))
			{
				throw new RuntimeException('The JoomEngine MCP component is disabled or not installed.');
			}

			$component = $application->bootComponent('com_joomengine_mcp');

			if (!$component instanceof ConsoleRuntimeProviderInterface)
			{
				throw new RuntimeException('A compatible JoomEngine MCP component must be installed and enabled.');
			}

			return $component->getConsoleRuntime($application);
		};

		$operations = ['serve', 'describe', 'dispatch', 'self-test', 'cli-inventory', 'jcb-sync'];

		// Validate the complete namespace before changing the registry or formatter.
		foreach ($operations as $operation)
		{
			$name = 'joomla:mcp:' . $operation;

			if ($application->hasCommand($name) && !$application->getCommand($name) instanceof McpCommand)
			{
				throw new RuntimeException('Refusing to replace an existing Joomla MCP console command.');
			}
		}

		foreach ($operations as $operation)
		{
			if (!$application->hasCommand('joomla:mcp:' . $operation))
			{
				$application->addCommand(new McpCommand($operation, $resolve));
			}
		}

		$input = $application->getConsoleInput();

		try
		{
			// As in Joomla's dispatcher, bind global options before determining the
			// command. Otherwise --live-site URL may be mistaken for its name.
			$input->bind($application->getDefinition());
		}
		catch (ExceptionInterface)
		{
			// Command-specific options are validated by the actual command later.
		}

		$selected = $input->getFirstArgument();

		if (is_string($selected) && str_starts_with($selected, 'joomla:mcp:')
			&& !$input->hasParameterOption(['--help', '-h', '--version', '-V'], true))
		{
			$this->outputGuard ??= new OutputGuard($application);
		}
	}

	/** @return void Restore native formatter state after Joomla flushes its queue. @since 0.1.0 */
	public function restoreOutput(): void
	{
		$application = $this->getApplication();

		if ($this->outputGuard !== null && $application instanceof ConsoleApplication)
		{
			$this->outputGuard->restore($application);
			$this->outputGuard = null;
		}
	}
}
