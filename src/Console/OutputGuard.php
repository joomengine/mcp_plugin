<?php
/**
 * @package    JoomEngine.Mcp
 * @created    17 September 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @copyright  Copyright (C) 2026 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */
namespace VDM\Plugin\Console\JoomEngineMcp\Console;


use Joomla\CMS\Application\ConsoleApplication;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;


/**
 * Keep Joomla's post-command messages out of the newline-delimited MCP stream.
 *
 * Protocol frames write directly to STDOUT. Joomla's console formatter is muted
 * until AFTER_EXECUTE, while native exception diagnostics still use STDERR.
 * All formatting state is restored after Joomla has flushed its message queue.
 *
 * @since 0.1.0
 */
final class OutputGuard
{
	/** @var OutputInterface Native console formatter, not the protocol stream. @since 0.1.0 */
	private OutputInterface $output;
	/** @var ?OutputInterface Previous exception output. @since 0.1.0 */
	private ?OutputInterface $errorOutput = null;
	/** @var int Previous console verbosity. @since 0.1.0 */
	private int $verbosity;
	/** @var string|false Previous PHP notice-output mode. @since 0.1.0 */
	private string|false $displayErrors;
	/** @var bool Whether the guard still owns the formatter state. @since 0.1.0 */
	private bool $active = true;

	/** @param ConsoleApplication $application Actual local console. @since 0.1.0 */
	public function __construct(ConsoleApplication $application)
	{
		$this->output = $application->getConsoleOutput();
		$this->verbosity = $this->output->getVerbosity();
		$this->displayErrors = ini_get('display_errors');

		if ($this->output instanceof ConsoleOutputInterface)
		{
			$this->errorOutput = $this->output->getErrorOutput();
		}

		$this->output->setVerbosity(OutputInterface::VERBOSITY_QUIET);

		if ($this->output instanceof ConsoleOutputInterface)
		{
			$this->output->setErrorOutput(new StreamOutput(STDERR, $this->verbosity, false));
		}

		ini_set('display_errors', 'stderr');
	}

	/** @param ConsoleApplication $application Finished command application. @return void Restore after Joomla's post-command output. @since 0.1.0 */
	public function restore(ConsoleApplication $application): void
	{
		if (!$this->active)
		{
			return;
		}

		foreach ($application->getMessageQueue() as $type => $messages)
		{
			// Message bodies can contain untrusted content or secrets. Operation
			// results carry their own safe diagnostics; report only queue counts.
			fwrite(STDERR, 'Joomla queued ' . count($messages) . ' ' . preg_replace('/[^a-z_-]/i', '', (string) $type) . ' message(s) during the MCP command.' . PHP_EOL);
		}

		if ($this->errorOutput !== null && $this->output instanceof ConsoleOutputInterface)
		{
			$this->output->setErrorOutput($this->errorOutput);
		}

		$this->output->setVerbosity($this->verbosity);

		if ($this->displayErrors !== false)
		{
			ini_set('display_errors', $this->displayErrors);
		}

		$this->active = false;
	}
}
