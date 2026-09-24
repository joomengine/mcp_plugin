<?php
/**
 * @package    JoomEngine.Mcp
 * @created    21 September 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @copyright  Copyright (C) 2026 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

use Joomla\Database\DatabaseInterface;
use Mcp\Client;
use Mcp\Client\Transport\StdioTransport;

$component = realpath((string) getenv('MCP_COMPONENT_SOURCE'));

if ($component === false || !is_file($component . '/tests/integration/bootstrap.php'))
{
	throw new RuntimeException('Set MCP_COMPONENT_SOURCE to the matching component checkout.');
}

// This bootstrap requires explicit destructive-test consent and a fixture marker.
require $component . '/tests/integration/bootstrap.php';
$app->bootComponent('com_joomengine_mcp');
$db = $container->get(DatabaseInterface::class);
$arguments = [];

if (is_string(php_ini_loaded_file()))
{
	$arguments = ['-c', php_ini_loaded_file()];
}

$arguments = array_merge($arguments, ['-d', 'extension_dir=' . ini_get('extension_dir'), '-d', 'display_errors=stderr', JPATH_ROOT . '/cli/joomla.php']);
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

/** Run the real installed CLI with bounded pipes, a deadline and no shell. */
$run = static function (array $command, string $input = '') use ($arguments): array
{
	$process = proc_open(array_merge([PHP_BINARY], $arguments, $command),
		[0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, JPATH_ROOT, null, ['bypass_shell' => true]);

	if (!is_resource($process))
	{
		throw new RuntimeException('Cannot start the installed Joomla console.');
	}

	foreach ($pipes as $pipe)
	{
		stream_set_blocking($pipe, false);
	}

	$stdout = '';
	$stderr = '';
	$offset = 0;
	$deadline = hrtime(true) + 60000000000;

	try
	{
		while (true)
		{
			if (hrtime(true) >= $deadline)
			{
				throw new RuntimeException('Installed console command exceeded its test deadline.');
			}

			if (isset($pipes[0]))
			{
				if ($offset < strlen($input))
				{
					$written = fwrite($pipes[0], substr($input, $offset, 8192));

					if ($written === false)
					{
						throw new RuntimeException('Installed console input pipe failed.');
					}

					$offset += $written;
				}

				if ($offset === strlen($input))
				{
					fclose($pipes[0]);
					unset($pipes[0]);
				}
			}

			$stdout .= stream_get_contents($pipes[1]);
			$stderr .= stream_get_contents($pipes[2]);

			if (strlen($stdout) + strlen($stderr) > 16777216)
			{
				throw new RuntimeException('Installed console output exceeded its test bound.');
			}

			$status = proc_get_status($process);

			if (!$status['running'])
			{
				return [(int) $status['exitcode'], $stdout . stream_get_contents($pipes[1]), $stderr . stream_get_contents($pipes[2])];
			}

			usleep(10000);
		}
	}
	finally
	{
		if (proc_get_status($process)['running'])
		{
			proc_terminate($process, 9);
		}

		foreach ($pipes as $pipe)
		{
			fclose($pipe);
		}

		proc_close($process);
	}
};
$decode = static fn (string $text): array => json_decode(trim($text), true, 128, JSON_THROW_ON_ERROR);
$request = json_encode(['protocol' => 'joomla-mcp/1', 'id' => 'system', 'action' => 'system.info', 'input' => (object) []], JSON_THROW_ON_ERROR);

foreach (['describe', 'self-test', 'cli-inventory'] as $operation)
{
	[$status, $stdout] = $run(['joomla:mcp:' . $operation, '--no-ansi', '--no-interaction']);
	$result = $decode($stdout);
	$check($status === 0 && ($result['protocol'] ?? '') === 'joomla-mcp/1' && ($result['ok'] ?? false),
		'Installed ' . $operation . ' returns one clean native protocol response');
}

[$status, $stdout] = $run(['--live-site', 'https://example.test', 'joomla:mcp:dispatch', '--input=-'], $request);
$result = $decode($stdout);
$check($status === 0 && ($result['id'] ?? '') === 'system' && ($result['result']['joomlaVersion'] ?? '') === JVERSION,
	'Installed dispatch preserves global options and executes the actual Joomla handler');

[$status, $stdout] = $run(['joomla:mcp:dispatch', '--format=ndjson'], "{invalid}\n" . $request . "\n");
$lines = array_map($decode, explode("\n", trim($stdout)));
$check($status !== 0 && count($lines) === 2 && !$lines[0]['ok'] && $lines[1]['ok'] && $lines[1]['id'] === 'system',
	'NDJSON retains failed-frame status and continues with the next valid request');

foreach ([['joomla:mcp:dispatch', '--format=xml'], ['joomla:mcp:dispatch', '--input=unsupported']] as $command)
{
	[$status, $stdout] = $run($command, $request);
	$result = $decode($stdout);
	$check($status !== 0 && !$result['ok'], 'Unsupported framing or input source fails with a structured response');
}

[$status, $stdout] = $run(['joomla:mcp:dispatch']);
$check($status !== 0 && !$decode($stdout)['ok'], 'EOF without a JSON request fails promptly');
[$status, $stdout] = $run(['joomla:mcp:dispatch'], str_repeat(' ', 1048577));
$check($status !== 0 && ($decode($stdout)['error']['code'] ?? '') === 'REQUEST_TOO_LARGE', 'Oversized JSON is rejected at the native input bound');

foreach ([' ', "\t", 'x'] as $fill)
{
	[$status, $stdout] = $run(['joomla:mcp:dispatch', '--format=ndjson'], str_repeat($fill, 1048577));
	$check($status !== 0 && ($decode($stdout)['error']['code'] ?? '') === 'REQUEST_TOO_LARGE',
		'Oversized NDJSON frames fail before blank-frame or JSON-content handling');
}

[$status, $stdout] = $run(['joomla:mcp:dispatch', '--format=ndjson'], " \t\n" . $request . str_repeat(' ', 1048575 - strlen($request)) . "\n");
$result = $decode($stdout);
$check($status === 0 && ($result['id'] ?? '') === 'system' && ($result['ok'] ?? false),
	'Bounded blank NDJSON frames are ignored and a request exactly at the byte bound remains valid');

$client = Client::builder()->setClientInfo('installed-plugin-fixture', '1.0.0')->setInitTimeout(15)->setRequestTimeout(30)->setMaxRetries(0)->build();

try
{
	$client->connect(new StdioTransport(PHP_BINARY, array_merge($arguments, ['joomla:mcp:serve']), JPATH_ROOT, maxBufferSize: 16777216));
	$names = array_map(static fn ($tool): string => $tool->name, $client->listTools()->tools);
	$check(in_array('joomla_action_read', $names, true), 'Installed plugin serve completes MCP handshake and discovery');
	$result = $client->callTool('joomla_action_read', ['action' => 'system.info', 'transport' => 'cli']);
	$content = json_decode(json_encode($result->structuredContent, JSON_THROW_ON_ERROR), true, 128, JSON_THROW_ON_ERROR);
	$check(!$result->isError && ($content['response']['data']['joomlaVersion'] ?? '') === JVERSION, 'Installed plugin serve executes the component-owned native action');
	$client->ping();
	$check($client->isConnected(), 'Native diagnostics leave subsequent protocol frames usable');
}
finally
{
	$client->disconnect();
}

$where = $db->quoteName('type') . ' = ' . $db->quote('component') . ' AND ' . $db->quoteName('element') . ' = ' . $db->quote('com_joomengine_mcp');
$previous = (int) $db->setQuery('SELECT enabled FROM ' . $db->quoteName('#__extensions') . ' WHERE ' . $where)->loadResult();

try
{
	$db->setQuery('UPDATE ' . $db->quoteName('#__extensions') . ' SET enabled = 0 WHERE ' . $where)->execute();
	[$status, $stdout] = $run(['joomla:mcp:describe']);
	$check($status !== 0 && !$decode($stdout)['ok'], 'Unavailable component fails only the affected MCP command');
	[$status] = $run(['list', '--no-ansi', '--no-interaction']);
	$check($status === 0, 'Unrelated Joomla commands remain available with the component disabled');
}
finally
{
	$db->setQuery('UPDATE ' . $db->quoteName('#__extensions') . ' SET enabled = ' . $previous . ' WHERE ' . $where)->execute();
}

echo json_encode(['checks' => $checks, 'joomla' => JVERSION, 'database' => $db->getServerType(), 'actualPluginEntrypoints' => true], JSON_THROW_ON_ERROR) . PHP_EOL;
