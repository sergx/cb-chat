<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;

class SimpleConversation extends Conversation {

  public $user = [];

  public function askGender() {
    $question = Question::create('Ваш гендер')
      ->addButtons([
        Button::create('Женский')->value('woman'),
        Button::create('Мужской')->value('man'),
        Button::create('Другое')->value('other'),
      ]);

    $this->ask($question, function (Answer $answer) {
      // Если кликнули по кнопке
      if ($answer->isInteractiveMessageReply()) {
        switch($answer->getValue()){
          case "woman":
            $this->bot->reply($this->user['name'].", ты прекрасны!");
            break;
          case "man":
            $this->bot->reply($this->user['name'].", твой дух силен!");
            break;
          case "other":
            $this->bot->reply($this->user['name'].", ты загадка!");
            break;
        }
      }else{
        // Если ввели тект, то задаем вопрос заново
        $this->bot->reply("Вы ввели текстом ". $answer->getText(). ", но надо нажать на кнопку..");
        return $this->askGender();
      }
    });
  }


  public function askEmail() {
    $this->ask('Напиши свой E-mail, плз', function (Answer $answer) {
      $this->user['email'] = $answer->getText();

      $this->bot->reply('Спасибо ' . $this->user['name'] . ', теперь оправлю тебе тонну спама на ' . $this->user['email']);
      return $this->askGender();
    });
  }

  public function askName() {
    $this->ask('Привет, как тебя зовут?', function (Answer $answer) {
      // Сохраняем результат
      $this->user['name'] = $answer->getText();

      $this->bot->reply('Приятно познакомиться, ' . $this->user['name']);
      return $this->askEmail();
    });
  }

  public function run() {
    return $this->askName();
  }
}
