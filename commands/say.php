<?php

$say = function ($who, $message, $type) {
	$bot = actionAPI::getBot();
	
	unset($message[0]);
	$message = implode(' ', $message);
	
	if (empty($message)) {
		return $bot->network->sendMessageAutoDetection($who, 'The message cannot be empty.', $type);
	} else {
		return $bot->network->sendMessageAutoDetection($who, in_array($message[0], ['/', '#']) ? '_' . $message : $message, $type);
	}
};
