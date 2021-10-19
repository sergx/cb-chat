<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use App\User;
use Illuminate\Support\Str;

class UserCreateConversation extends Conversation {

  public $auth;

  public function __construct(User $auth) {
    $this->auth = $auth;
  }

  public $newUser = [
    'role' => 'user',
    'telergam_user_id' => '',
    'telergam_user_name' => '',
    'cb_user_name' => '',
  ];

  public function showNewUserData() {
    $result = [];
    foreach ($this->newUser as $k => $v) {
      if (!$v) {
        continue;
      }
      $result[] = str_replace("_", "\_", $k) . ": *$v*";
    }
    $this->say(implode("\r\n", $result), [
      'parse_mode' => 'Markdown'
    ]);
  }

  public function createNewUser() {

    $user = User::create([
      'name' => ucfirst($this->newUser['telergam_user_name']),
      'email' => 'email-' . Str::random() . "@" . "random.com",
      'password' => bcrypt("123456789"),
      'role' => $this->newUser['role'],
    ]);

    $user->Tg()->create([
      'id' => $this->newUser['telergam_user_id'],
      'username' => $this->newUser['telergam_user_name'],
    ]);

    if ($this->newUser['cb_user_name']) {
      $user->Cb()->create([
        'username' => $this->newUser['cb_user_name'],
      ]);
    }

    $this->say("Ок, пользователь создан. Данные пользователя:");
    $this->showNewUserData();

    return $this->bot->startConversation(new \App\Conversations\SuperadminConversation($this->auth));
  }

  public function askCbUserName() {
    $this->showNewUserData();
    return $this->ask("Укажите Имя пользователя в Chaturbate", function (Answer $answer) {
      if (!$answer->getText()) {
        $this->say("Не расслышал вас, говорите громче..");
        return $this->repeat();
      }

      $this->newUser['cb_user_name'] = $answer->getText();

      return $this->createNewUser();
    });
  }

  public function askTelegramUserName() {
    $this->showNewUserData();
    return $this->ask("Укажите Имя пользователя в Telegram", function (Answer $answer) {
      if (!$answer->getText()) {
        $this->say("Не расслышал вас, говорите громче..");
        return $this->repeat();
      }

      $this->newUser['telergam_user_name'] = $answer->getText();

      if ($this->newUser['role'] === 'user') {
        return $this->askCbUserName();
      } else {
        return $this->createNewUser();
      }
    });
  }

  public function askTelegramId() {
    $this->showNewUserData();
    return $this->ask("Укажите id пользователя в Telegram (только цифры)", function (Answer $answer) {
      $user_id = intval($answer->getText());

      if (!$user_id || $user_id != $answer->getText()) {
        return $this->repeat("Некорректный id. Нужен id, состоящий их цифр. Пожалуйста, укажите корректный id");
      }

      $this->newUser['telergam_user_id'] = $user_id;
      return $this->askTelegramUserName();
    });
  }

  public function askRole() {
    $question = Question::create("Какая роль у пользователя?")
      ->fallback('Что-то пошло не так...')
      //->callbackId('create_user') (Это вроде только для Slack)
      ->addButtons([
        Button::create('🔙 Назад')->value('back'),
        Button::create('User')->value('user'),
        Button::create('Admin')->value('admin'),
        Button::create('Superadmin')->value('superadmin'),
      ]);

    return $this->ask($question, function (Answer $answer) {
      if ($answer->isInteractiveMessageReply()) {
        if ($answer->getValue() === "back") {
          return $this->bot->startConversation(new \App\Conversations\SuperadminConversation($this->auth));
        }
        $this->newUser['role'] = $answer->getValue();
        return $this->askTelegramId();
      }
    });
  }

  public function run() {
    return $this->askRole();
  }
}
