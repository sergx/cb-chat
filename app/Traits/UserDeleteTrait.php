<?php

namespace App\Traits;

use BotMan\BotMan\Messages\Incoming\Answer;

use BotMan\Drivers\Telegram\Extensions\Keyboard;
use BotMan\Drivers\Telegram\Extensions\KeyboardButton;

trait UserDeleteTrait{

  public function deleteUser_done($id) {
    $keyboard = Keyboard::create()->type(Keyboard::TYPE_INLINE)
      ->addRow(KeyboardButton::create('🔙 В корневое меню')->callbackData('back'))
      ->addRow(KeyboardButton::create('🔙 К списку пользователей')->callbackData('user_list'))
      ->toArray();

    $text = "Пользователь id " . $id . " удален!";

    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask($question, function (Answer $answer) {
      if ($answer->isInteractiveMessageReply()) {
        switch ($answer->getValue()) {
          case "back":
            return $this->start();
            break;
          case "user_list":
            return $this->user_list();
            break;
        }
      }
      $this->start();
    });
  }

  public function deleteUserConfirmation($id, $repeat = "") {
    $keyboard = Keyboard::create()->type(Keyboard::TYPE_INLINE)
      ->addRow(KeyboardButton::create('🔙 Назад')->callbackData('back'))
      ->toArray();

    $text = $repeat;
    $text .= "Напишите «Удалить " . $id . "» чтобы подтвердить удаление пользователя";

    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask($question, function (Answer $answer) use ($id) {
      if ($answer->getText() === "Удалить " . $id) {
        $this->deleteUser($id);
        return $this->deleteUser_done($id);
      }
      if ($answer->isInteractiveMessageReply()) {
        switch ($answer->getValue()) {
          case "back":
            return $this->user_information($id);
            break;
        }
      }
      return $this->deleteUserConfirmation($id, ".. не верно указан текст для подтверждения" . PHP_EOL);
    });
  }

  public function deleteUser($id) {
    $user = \App\User::find($id);
    $user->delete();
  }

}

?>
