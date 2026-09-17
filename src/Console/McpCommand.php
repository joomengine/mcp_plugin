<?php
/**
 * @package    JoomEngine.Mcp
 * @created    17 September 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @copyright  Copyright (C) 2026 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */
namespace VDM\Plugin\Console\JoomEngineMcp\Console;


use Closure;
use InvalidArgumentException;
use Joomla\CMS\Application\ConsoleApplication;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use VDM\Component\JoomEngineMcp\Administrator\Contract\ConsoleRuntimeInterface;


/**
 * Lazy console adapter preserving legacy framing and adding native MCP stdio.
 *
 * @since  0.1.0
 */
final class McpCommand extends AbstractCommand
{
	/**
	 * Fixed command operation selected by the plugin, never by a request body.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	private string $operation;

	/**
	 * Component runtime resolver invoked only when this command executes.
	 *
	 * @var    Closure():ConsoleRuntimeInterface
	 * @since  0.1.0
	 */
	private Closure $resolveRuntime;

	/**
	 * Bind one known command to the component composition root.
	 *
	 * @param   string    $operation       Known console operation.
	 * @param   callable  $resolveRuntime  Lazy component runtime resolver.
	 * @throws  InvalidArgumentException  For an unknown command operation.
	 * @since   0.1.0
	 */
	public function __construct(string $operation, callable $resolveRuntime)
	{
		if (!in_array($operation, ['serve', 'describe', 'dispatch', 'self-test', 'cli-inventory'], true))
		{
			throw new InvalidArgumentException('Unknown Joomla MCP console operation.');
		}

		$this->operation = $operation;
		$this->resolveRuntime = Closure::fromCallable($resolveRuntime);
		parent::__construct('joomla:mcp:' . $operation);
	}

	/**
	 * Preserve the companion's public options; stdio has no alternate framing.
	 *
	 * @return  void
	 * @since   0.1.0
	 */
	protected function configure(): void
	{
		$this->setDescription($this->operation === 'serve'
			? 'Serve the installed database-driven Joomla MCP over PHP stdio.'
			: 'Run the shared JoomEngine MCP ' . $this->operation . ' console operation.');

		if ($this->operation !== 'serve')
		{
			$this->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format; dispatch also accepts ndjson.', 'json');
		}

		if ($this->operation === 'dispatch')
		{
			$this->addOption('input', null, InputOption::VALUE_REQUIRED, 'Only stdin (-) is supported.', '-');
		}
	}

	/**
	 * Resolve the local component runtime and execute without a shell process.
	 *
	 * @param   InputInterface   $input   Joomla console input.
	 * @param   OutputInterface  $output  Joomla console output.
	 * @return  int  Zero only for successful command execution.
	 * @since   0.1.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		if (PHP_SAPI !== 'cli' || !$this->getApplication() instanceof ConsoleApplication)
		{
			return $this->failure('LOCAL_CONSOLE_REQUIRED', 'This command requires the local Joomla console.');
		}

		try
		{
			$runtime = ($this->resolveRuntime)();

			if (!$runtime instanceof ConsoleRuntimeInterface)
			{
				return $this->failure('DEPENDENCY_UNAVAILABLE', 'A compatible JoomEngine MCP component is required.');
			}

			if ($this->operation === 'serve')
			{
				return $runtime->serveStdio();
			}

			return $runtime->executeCommand($this->operation, $input, $output);
		}
		catch (Throwable)
		{
			return $this->failure('MCP_COMMAND_FAILED', 'The JoomEngine MCP command could not complete; check the component installation and server logs.');
		}
	}

	/**
	 * Keep diagnostics out of MCP stdout and retain legacy structured errors.
	 *
	 * @param   string  $code     Stable error code.
	 * @param   string  $message  Non-sensitive operator diagnostic.
	 * @return  int
	 * @since   0.1.0
	 */
	private function failure(string $code, string $message): int
	{
		if ($this->operation === 'serve')
		{
			fwrite(STDERR, $message . PHP_EOL);
		}
		else
		{
			fwrite(STDOUT, json_encode([
				'protocol' => 'joomla-mcp/1',
				'id' => null,
				'ok' => false,
				'error' => ['code' => $code, 'message' => $message],
			], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL);
		}

		return 1;
	}
}
