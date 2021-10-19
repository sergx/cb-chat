<?php

use App\Http\Controllers\BotManController;
use BotMan\BotMan\BotMan;
use App\User;


$botman = resolve('botman');

$botman->hears('Кто я', function ($bot) {
  $bot->reply("@" . $bot->getUser()->getUsername() . " - твой Username");
  $bot->reply($bot->getUser()->getId() . " - твой Id");
});

// $botman->hears('Hi', function ($bot) {
//   // $bot->getUser()->getId() - tg_user_id
//   //$bot->reply($bot->getMessages() . " man!");
//   $bot->reply("@" . $bot->getUser()->getUsername());
//   $bot->reply('Hello!');
// });

// $botman->hears('yo', function (BotMan $bot) {
//   $bot->sendRequest('sendMessage', ['text' => 'text..', 'reply_markup' => json_encode([
//     'inline_keyboard' => [
//       [['text' => '11', 'callback_data' => "1-1"], ['text' => 'yo', 'callback_data' => "YO"]],
//       [['text' => 'yo', 'callback_data' => "YO"]],
//       [['text' => 'yo', 'callback_data' => "YO"]],
//     ]
//   ], 256)]);
// });

// $botman->hears(['yo','oy'], function ($bot) {
//   // ???
//   //$bot->reply(implode($bot->getMessages())." man!");
// });


//   }
// });

// $botman->hears('yo', function (BotMan $bot) {
//   $bot->sendRequest('sendMessage', ['text' => 'text..', 'reply_markup' => json_encode([
//     'inline_keyboard' => [
//       [['text' => '11', 'callback_data' => "1-1"], ['text' => 'yo', 'callback_data' => "YO"]],
//       [['text' => 'yo', 'callback_data' => "YO"]],
//       [['text' => 'yo', 'callback_data' => "YO"]],
//     ]
//     ], 256)]);
// });

//$botman->hears(/*Create*/'User', BotManController::class . '@userCreate');

//$botman->hears(/*Create*/'List', BotManController::class . '@userlList');

//$botman->hears('Start conversation', BotManController::class . '@startConversation');

$botman->hears('/start', BotManController::class . '@start');

$botman->hears('/demo',  function($bot){
  $bot->startConversation(new \App\Conversations\DemoEditConversation());
});

$botman->hears('/wt',  function($bot){
  $bot->startConversation(new \App\Conversations\SimpleConversation());
});
