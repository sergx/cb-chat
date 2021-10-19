<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use App\User;
use Illuminate\Support\Str;


class NavConversation extends Conversation {

  public function userList() {
    $question = Question::create("Список пользователей")
      ->fallback('Что-то пошло не так...')
      ->addButtons([
        Button::create('<- Назад')->value('back'),
      ]);

    return $this->ask($question, function (Answer $answer) {
      if ($answer->isInteractiveMessageReply()) {
        switch ($answer->getValue()) {
          case "back":
          //default:
            return $this->startNav();
            break;
        }
      }
    });
  }

  public function userAdd() {
    $question = Question::create("Добавить пользователя")
      ->fallback('Что-то пошло не так...')
      ->addButtons([
        Button::create('<- Назад')->value('back'),
      ]);

    return $this->ask($question, function (Answer $answer) {
      if ($answer->isInteractiveMessageReply()) {
        switch ($answer->getValue()) {
          case "back":
            //default:
            return $this->startNav();
            break;
        }
      }
    });
  }

  public function startNav() {

    $question = Question::create("Разделы")
      ->fallback('Что-то пошло не так...')
      ->addButtons([
        Button::create('Список пользователей')->value(''),
        Button::create('Добавить пользователя')->value('User'),
      ]);

    return $this->ask($question, function (Answer $answer) {
      if ($answer->isInteractiveMessageReply()) {
        switch($answer->getValue()){
          case "":
            return $this->userList();
            break;
          case "User":
            return $this->userAdd();
            break;
        }
      }
    });
  }

  public function run() {
    return $this->startNav();
  }
}
