<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Conversations\Conversation;
use App\User;
use App\Payout;

use BotMan\Drivers\Telegram\Extensions\Keyboard;
use BotMan\Drivers\Telegram\Extensions\KeyboardButton;


class AdminConversation extends Conversation {

  use \App\Traits\PayoutsTrait;

  public $auth;

  public function __construct(User $auth) {
    $this->auth = $auth;
  }

  public function manage_payout($id) {
  }

  public function start($force_new = false) {
    $payouts = Payout::where('status', 0)->get();

    $keyboard = Keyboard::create()->type(Keyboard::TYPE_INLINE)
      ->addRow(KeyboardButton::create('Новые завяки на выплаты (' . $payouts->count() . ')')->callbackData('payout_list_only_new'))
      ->addRow(KeyboardButton::create('Статистика выплат')->callbackData('allPayouts'))
      ->toArray();

    $text = "Разделы";
    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask($question, function (Answer $answer) {
      if ($answer->isInteractiveMessageReply()) {
        switch ($answer->getValue()) {
          case "payout_list_only_new":
            return $this->payout_list_only_new();
            break;
          case "allPayouts":
            return $this->payout_list_all();
            break;
        }
      }
      return $this->start();
    });
  }

  public function run() {
    return $this->start();
  }
}
