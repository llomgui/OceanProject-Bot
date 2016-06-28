<?php

// classes
require_once 'classes/xatVariables.php';
require_once 'classes/xatBot.php';

// API
require_once 'API/dataAPI.php';
require_once 'API/actionAPI.php';

echo 'Loading variables...' . PHP_EOL;
xatVariables::init();
xatVariables::update();

echo 'Loading API...' . PHP_EOL;
$params     = API::init();
$currentBot = &$params['botID'];
$bot        = &$params['bot'];

echo 'Loading bots...' . PHP_EOL;
$xatBots = [];
foreach (xatVariables::getBots() as $botid => $bot) {
	$xatBots[$botid] = new Bot($bot);
}

echo 'Loading extensions...' . PHP_EOL;
$extensionsList = [];
read();

echo 'Server is ready!' . PHP_EOL;

while (1) {

	foreach ($xatBots as $botid => $Ocean) {

		$currentBot = $botid;
		$bot        = $Ocean;

		usleep(5000);
		try {

			while (1) {

				if (!$Ocean->network->socket->isConnected()) {
					echo 'Socket not connected!' . PHP_EOL;
					exit('You have an error in your code or socket died.');
					break;
				}

				$packet = $Ocean->network->socket->read();

				if ($packet === false) {
					echo 'ERROR packet false!' . PHP_EOL;
					exit('You have an error in your code or socket died.');
					break;
				}

				if (empty($packet)) {
					break;
				}

				if (!isset($packet['node'])) {
					var_dump($packet);
					break;
				}

				$hook   = null;
				$args   = [];
				$unknow = false;

				switch ($packet['node']) {

					case 'm':
						if (!isset($packet['elements']['s'])) {

							if (!isset($packet['elements']['p']) && isset($packet['elements']['i'])) {
								$hook   = 'onMessage'; // onMessage($who, $message)
								$args[] = $Ocean->network->parseID($packet['elements']['u']);
								$args[] = $packet['elements']['t'];
							} else {
								$hook	= 'onOldMessage'; // onOldMessage($who, $message)
								$args[] = $Ocean->network->parseID($packet['elements']['u']);
								$args[] = $packet['elements']['t'];
							}

						}
						break;
						
					case 'p':
						if(isset($packet['elements']['s']))
						{
							unset($packet['elements']['s']);
							$hook   = 'onPC'; // onPC($who, $message)
							$args[] = $Ocean->network->parseID($packet['elements']['u']);
							$args[] = $packet['elements']['t'];
						}
						else
						{
							$hook   = 'onPM'; // onPM($who, $message)
							$args[] = $Ocean->network->parseID($packet['elements']['u']);
							$args[] = $packet['elements']['t'];
						}
						break;
					
					case 'u':
							$hook	= 'onUserJoined'; // onUserJoined($who, $extra)
							$user   = new xatUser($packet['elements']);
							$args[] = $user;

							if(isset($packet['elements']['s']))
								$args[] = $packet['elements']['s'];
							
							$Ocean->users[$user->getID()] = $user;
							unset($user);
						break;
					
					case 'z':
						$hook	= 'onTickle'; // onTickle($who, $array) 
						$args[] = $Ocean->network->parseID($packet['elements']['u']);
						$args[] = $packet['elements'];
						break;

					case 'done':
						$hook   = 'onDone'; // onDone($array)
						$args[] = $packet['elements'];
						break;

					case 'idle':
						$hook   = 'onIdle'; // onIdle($array)
						$args[] = $packet['elements'];
						break;

					default:
						$unknow = true;
						break;
				}

				if (in_array($hook, ['onMessage']) && $args[1][0] == '!') {

					$args[1] = explode(' ', trim($args[1]));
					$command = substr($args[1][0], 1);
					dispatch('commands', $command, $args);

				} else {

					if (!$unknow && !empty($hook)) {
						dispatch('modules', $hook, $args);
					} elseif ($unknow) {
						echo 'Unknow node ['.$packet['node'].'] on chat FIXME' . PHP_EOL;
					}
				}

			}

		} catch (Exception $e) {
			var_dump($e->getMessage());
			echo 'Error botid: ' . $botid . PHP_EOL;
		}

	}
}

function load($data, $type, $name, $url, $callbacks)
{
	require($url);

	for ($i = 0; $i < sizeof($callbacks); $i++) {
		if (isset(${$callbacks[$i]})) {
			$data[$type][$callbacks[$i]][$name] = ${$callbacks[$i]};
		} else {
			unset($data[$type][$callbacks[$i]][$name]);
		}
	}
	
	return $data;
}

function dispatch($type, $name, $args) {

	global $extensionsList;

	if (!isset($extensionsList[$type][$name])) {
		return false;
	}

	foreach ($extensionsList[$type][$name] as $extensionName => $function) {
		call_user_func_array($function, $args);
	}
}

function read()
{
	global $extensionsList;
	$extensionsDirectories = ['modules', 'commands'];

	foreach ($extensionsDirectories as $extensionsDir) {
		$callbacks = json_decode(file_get_contents($extensionsDir . '.json', true), true);

		$dir = opendir($extensionsDir);

		while (($file = readdir($dir)) !== false) {
			$url = '.' . DIRECTORY_SEPARATOR . $extensionsDir . DIRECTORY_SEPARATOR . $file;
			
			if(!is_file($url)) {
				continue;
			}
			
			$pos = strrpos($file, '.');

			if($pos === false) {
				continue;
			}
			
			if(substr($file, $pos + 1) != 'php') {
				continue;
			}
			
			$extensionsList = load($extensionsList, $extensionsDir, substr($file, 0, $pos), $url, $callbacks);
		}
	}
}

function reloadExtensions()
{
	read();
}