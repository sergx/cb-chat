<?php
namespace App\Services;

use App\Services\TelegramQuestionEditMessage as TgEditMessage;
use App\Services\TelegramQuestionMessage as TgMessage;
use App\User;

class BotHelpers{

  public function TelegramQuestion(String $text, $keyboard = [], $messagePayload, $force_new = false){

    if (empty($messagePayload['reply_markup']) || $force_new) {
      if($keyboard){
        $question = TgMessage::create($text)
          ->setKeyboard($keyboard);
      }else{
        $question = TgMessage::create($text);
      }
    } else {
      if ($keyboard) {
        $question = TgEditMessage::create($text)
          ->setKeyboard($keyboard)
          ->setMessageId($messagePayload['message_id']);
        }else{
        $question = TgEditMessage::create($text)
          ->setMessageId($messagePayload['message_id']);
      }
    }
    return $question;
  }
}
