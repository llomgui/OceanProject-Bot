<?php

$guestself = function ($who, $message, $type) {
    
    $bot = actionAPI::getBot();
    
    if (!$bot->botHasPower(32)) {
        return $bot->network->sendMessageAutoDetection($who, sprintf('Sorry, but i don\'t have the power \'%s\'.', 'guestself'), $type);
    }
    
    $bot->network->sendMessage('/g');
};
