<?php
$slots = function ($who, $message, $type) {

    $bot = actionAPI::getBot();
    
    $smilies = ['(smile#)','(biggrin#)','(wink#)','(eek#)','(tongue#)','(cool#)','(mad#)','(confused#)','(redface#)','(frown#)','(crying#)'];
    
    if ($bot->botHasPower(236)) {
        $smilies = ['(slotban#)','(slotbar#)','(cherries#)','(orange2#)','(plum2#)','(seven#)'];
    }
    
    $smilieCount = 3;
    if (isset($message[1]) && !empty($message[1])) {
        $difficulty = ['easy' => 2, 'hard' => 4, 'expert' => 5, 'extreme' => 6, 'impossible' => 7, 'gg' => 10];
        if (array_key_exists(strtolower($message[1]), $difficulty)) {
            $smilieCount = $difficulty[strtolower($message[1])];
        }
    }
    
    $spun = [];
    
    for ($i = 1; $i <= $smilieCount; $i++) {
        array_push($spun, $smilies[array_rand($smilies)]);
    }
    
    $user = $bot->users[$who];
    $response = isset($user) ? ($user->isRegistered() ? $user->getRegname() : $user->getID()) . " has spun: " : $who . " has spun: ";
    
    $bot->network->sendMessageAutoDetection($who, 'Spinning: ' . implode('|', array_fill(0, $smilieCount, '(rolling#)')), $type);
    usleep(800001);//prevert "Limit" (possible better way to do this?)
    if (count(array_unique($spun)) == 1) {
        $bot->network->sendMessageAutoDetection($who, $response . implode('|', $spun) . ' and won (clap#)', $type);
    } else {
        $bot->network->sendMessageAutoDetection($who, $response . implode('|', $spun) . ' and lost :P', $type);
    }
};
