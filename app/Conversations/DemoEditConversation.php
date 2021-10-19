<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;

use BotMan\Drivers\Telegram\Extensions\Keyboard;
use BotMan\Drivers\Telegram\Extensions\KeyboardButton;
use App\Services\TelegramQuestionEditMessage as TgEditMessage;
use App\Services\TelegramQuestionMessage as TgMessage;

class DemoEditConversation extends Conversation {

  public $name = "";
  public $song = "";
  public $movie = "";

  public function getResult() {
    $question = TgEditMessage::create("Ok *" . $this->name . "*, best song is *" . $this->song . "*, movie is *" . $this->movie . "*. " . PHP_EOL . "Type «back» to go back." . PHP_EOL . "Or anything to start again")
      //->setKeyboard($keyboard)
      ->setMessageId($this->bot->getMessage()->getPayload()['message_id']);

    return $this->ask(
      $question,
      function (Answer $answer) {
        if (strtolower($answer->getText()) === "back") {
          return $this->askMovie();
        }
        $this->name = "";
        $this->song = "";
        $this->movie = "";
        return $this->start();
        //$this->stopsConversation($answer);
      },
      ['parse_mode' => 'Markdown']
    );
  }
  public function askMovie() {
    $keyboard = Keyboard::create()
      ->type(Keyboard::TYPE_INLINE)
      ->addRow(
        KeyboardButton::create('<<< Back')->callbackData('back'),
      )
      ->addRow(
        KeyboardButton::create('Star wars')->callbackData('Star wars'),
      )
      ->addRow(
        KeyboardButton::create('Lord of the rings')->callbackData('Lord of the rings'),
      )
      ->addRow(
        KeyboardButton::create('Harry Potter')->callbackData('Harry Potter'),
      )
      ->toArray();

    // Если нет кнопок, тогда их и менять нельзя..
    if (empty($this->bot->getMessage()->getPayload()['reply_markup'])) {
      $question = TgMessage::create("Well, *" . $this->song . "* is very nice song! What about movie?")
        ->setKeyboard($keyboard);
    } else {
      $question = TgEditMessage::create("Wow! *" . $this->song . "* is very nice song! What about movie?")
        ->setKeyboard($keyboard)
        ->setMessageId($this->bot->getMessage()->getPayload()['message_id']);
    }

    return $this->ask(
      $question,
      function (Answer $answer) {
        if ($answer->isInteractiveMessageReply()) {
          switch ($answer->getValue()) {
            case "back":
              return $this->askSong();
              break;
            case "Star wars":
            case "Lord of the rings":
            case "Harry Potter":
              $this->movie = $answer->getValue();
              return $this->getResult();
              break;
          }
        }
        $this->movie = $answer->getText();
        return $this->getResult();
      },
      ['parse_mode' => 'Markdown']
    );
  }

  public function askSong() {
    $keyboard = Keyboard::create()
      ->type(Keyboard::TYPE_INLINE)
      ->addRow(
        KeyboardButton::create('<<< Back')->callbackData('back'),
        KeyboardButton::create('Skip >>>')->callbackData('skip'),
      )
      ->addRow(
        KeyboardButton::create('Merry Christmas')->callbackData('Merry Christmas'),
      )
      ->addRow(
        KeyboardButton::create('Show must go on')->callbackData('Show must go on'),
      )
      ->toArray();

    // Если нет кнопок, тогда их и менять нельзя..
    if (empty($this->bot->getMessage()->getPayload()['reply_markup'])) {
      $question = TgMessage::create("Yo, " . $this->name . "! What is your favorite song - _type_ or _select_?")
        ->setKeyboard($keyboard);
    } else {
      $question = TgEditMessage::create("Hey, " . $this->name . "! What is your favorite song - _type_ or _select_?")
        ->setKeyboard($keyboard)
        ->setMessageId($this->bot->getMessage()->getPayload()['message_id']);
    }

    return $this->ask(
      $question,
      function (Answer $answer) {
        if ($answer->isInteractiveMessageReply()) {
          switch ($answer->getValue()) {
            case "back":
              return $this->start(true);
              break;
            case "skip":
              if (!$this->song) {
                $this->song = "n/a";
              }
              return $this->askMovie(true);
              break;
            case "Merry Christmas":
            case "Show must go on":
              $this->song = $answer->getValue();
              return $this->askMovie(true);
              break;
          }
        }
        $this->song = $answer->getText();
        return $this->askMovie();
      },
      ['parse_mode' => 'Markdown']
    );
  }

  public function start() {
    $keyboard = Keyboard::create()
      ->type(Keyboard::TYPE_INLINE)
      ->addRow(
        KeyboardButton::create('Skip >>>')->callbackData('skip'),
      )
      ->toArray();

    // Если нет кнопок, тогда их и менять нельзя..
    if (empty($this->bot->getMessage()->getPayload()['reply_markup'])) {
      $question = TgMessage::create("Your name?")
        ->setKeyboard($keyboard);
    } else {
      $question = TgEditMessage::create("Your name?")
        ->setKeyboard($keyboard)
        ->setMessageId($this->bot->getMessage()->getPayload()['message_id']);
    }

    return $this->ask(
      $question,
      function (Answer $answer) {
        if ($answer->isInteractiveMessageReply()) {
          switch ($answer->getValue()) {
            case "skip":
              if (!$this->name) {
                $this->name = "...";
              }
              return $this->askSong(true);
              break;
          }
        }
        $this->name = $answer->getText();
        return $this->askSong();
      },
      ['parse_mode' => 'Markdown']
    );
  }

  public function run() {
    return $this->start();
  }
}
