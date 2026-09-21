<?php
/**
 * @package    JoomEngine.Mcp
 * @created    21 September 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @copyright  Copyright (C) 2026 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

use Joomla\Application\ApplicationEvents;
use Joomla\CMS\Application\ConsoleApplication;
use Joomla\CMS\Language\Language;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Console\ConsoleEvents;
use Joomla\DI\Container;
use Joomla\Event\Dispatcher;
use Joomla\Event\Event;
use Joomla\Registry\Registry;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use VDM\Component\JoomEngineMcp\Administrator\Contract\ConsoleRuntimeInterface;
use VDM\Plugin\Console\JoomEngineMcp\Console\McpCommand;
use VDM\Plugin\Console\JoomEngineMcp\Extension\JoomEngineMcpPlugin;

$joomla = realpath((string) getenv('JOOMLA_ROOT'));
$component = realpath((string) getenv('MCP_COMPONENT_SOURCE'));

if (PHP_SAPI !== 'cli' || $joomla === false || $component === false
	|| !is_file($joomla . '/libraries/vendor/autoload.php')
	|| !is_file($component . '/admin/src/Contract/ConsoleRuntimeInterface.php'))
{
	throw new RuntimeException('Set JOOMLA_ROOT to the full Joomla distribution and MCP_COMPONENT_SOURCE to its matching component checkout.');
}

define('_JEXEC', 1);
define('JPATH_BASE', $joomla);
require $joomla . '/includes/defines.php';
require $joomla . '/libraries/bootstrap.php';
require $component . '/admin/src/Contract/ConsoleRuntimeInterface.php';
require dirname(__DIR__) . '/src/Console/McpCommand.php';
require dirname(__DIR__) . '/src/Console/OutputGuard.php';
require dirname(__DIR__) . '/src/Extension/JoomEngineMcpPlugin.php';

/** Native console behaviour without adding the database-backed core commands. */
final class FixtureConsole extends ConsoleApplication
{
	/** @inheritDoc */
	protected function getDefaultCommands(): array
	{
		return [];
	}
}

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void
{
	if (!$condition)
	{
		throw new RuntimeException($message);
	}

	$checks++;
	echo 'PASS ' . $message . PHP_EOL;
};
$make = static function (array $arguments): array
{
	$output = new ConsoleOutput(OutputInterface::VERBOSITY_VERBOSE, false);
	$output->setErrorOutput(new StreamOutput(fopen('php://memory', 'w+'), OutputInterface::VERBOSITY_VERBOSE));
	$dispatcher = new Dispatcher();
	$app = new FixtureConsole(new Registry(), $dispatcher, new Container(), new Language('en-GB'), new ArgvInput($arguments), $output);
	$plugin = new JoomEngineMcpPlugin(['name' => 'joomengine_mcp', 'type' => 'console']);
	$plugin->setApplication($app);
	$dispatcher->addSubscriber($plugin);

	return [$app, $plugin, $output, $dispatcher];
};

foreach ([['joomla.php', 'joomla:mcp:serve'], ['joomla.php', '--live-site', 'https://example.test', 'joomla:mcp:serve']] as $arguments)
{
	[$app, $plugin, $output, $dispatcher] = $make($arguments);
	$previousErrors = ini_get('display_errors');
	$previousStderr = $output->getErrorOutput();
	$dispatcher->dispatch(ApplicationEvents::BEFORE_EXECUTE, new Event(ApplicationEvents::BEFORE_EXECUTE));
	$check($output->isQuiet() && ini_get('display_errors') === 'stderr', 'MCP entry isolates formatter and PHP diagnostics with global options');
	$first = $app->getCommand('joomla:mcp:serve');
	$plugin->registerCommands();
	$check(count($app->getAllCommands()) === 6 && $first === $app->getCommand('joomla:mcp:serve'), 'Repeated registration is idempotent without booting the component');
	$dispatcher->dispatch(ConsoleEvents::APPLICATION_ERROR, new Event(ConsoleEvents::APPLICATION_ERROR));
	$check($output->getVerbosity() === OutputInterface::VERBOSITY_VERBOSE && $output->getErrorOutput() === $previousStderr
		&& ini_get('display_errors') === $previousErrors, 'Native error events restore the exact formatter and PHP diagnostic state');
	$plugin->restoreOutput();
	$check($output->getErrorOutput() === $previousStderr, 'Repeated output restoration is harmless');
}

foreach ([['joomla.php', 'list'], ['joomla.php', 'joomla:mcp:serve', '--help'], ['joomla.php', 'joomla:mcp:serve', '--version']] as $arguments)
{
	[$app, $plugin, $output] = $make($arguments);
	$plugin->registerCommands();
	$check($output->getVerbosity() === OutputInterface::VERBOSITY_VERBOSE, 'Core commands, help and version retain normal output');
}

[$app, $plugin, $output] = $make(['joomla.php', 'joomla:mcp:serve']);
$conflicting = new class('joomla:mcp:cli-inventory') extends AbstractCommand
{
	/** @inheritDoc */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		return 0;
	}
};
$app->addCommand($conflicting);
$rejected = false;

try
{
	$plugin->registerCommands();
}
catch (RuntimeException)
{
	$rejected = true;
}

$check($rejected && count($app->getAllCommands()) === 1 && $app->getCommand('joomla:mcp:cli-inventory') === $conflicting
	&& $output->getVerbosity() === OutputInterface::VERBOSITY_VERBOSE, 'Registration conflicts preserve the existing registry and output state');

$runtime = new class implements ConsoleRuntimeInterface
{
	/** @var array Recorded typed calls. */
	public array $calls = [];

	/** @inheritDoc */
	public function serveStdio(): int
	{
		$this->calls[] = ['serve'];

		return 23;
	}

	/** @inheritDoc */
	public function executeCommand(string $operation, InputInterface $input, OutputInterface $output): int
	{
		$this->calls[] = [$operation, $input, $output];

		return 17;
	}
};
[$app, $plugin, $output] = $make(['joomla.php', 'list']);
$resolved = 0;
$resolve = static function () use ($runtime, &$resolved): ConsoleRuntimeInterface
{
	$resolved++;

	return $runtime;
};

foreach (['serve', 'describe', 'dispatch', 'self-test', 'cli-inventory', 'jcb-sync'] as $operation)
{
	$command = new McpCommand($operation, $resolve);
	$app->addCommand($command);
	$check($resolved === count($runtime->calls), 'Constructing adapters never resolves component runtime eagerly');
	$input = new ArrayInput($operation === 'dispatch' ? ['--input' => '-', '--format' => 'ndjson'] : []);
	$status = $command->execute($input, $output);
	$call = $runtime->calls[array_key_last($runtime->calls)];
	$check($status === ($operation === 'serve' ? 23 : 17) && $call[0] === $operation, 'Adapters preserve selected operation and nonzero native status');

	if ($operation !== 'serve')
	{
		$check($call[1] === $input && $call[2] === $output && $input->getOption('format') === ($operation === 'dispatch' ? 'ndjson' : 'json'),
			'Typed native input and output reach the shared runtime without reconstruction');
	}
}

echo json_encode(['checks' => $checks, 'joomla' => JVERSION, 'nativeConsoleClasses' => true, 'installedRuntime' => false], JSON_THROW_ON_ERROR) . PHP_EOL;
