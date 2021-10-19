<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Conversations\Conversation;
use App\User;

use BotMan\Drivers\Telegram\Extensions\Keyboard;
use BotMan\Drivers\Telegram\Extensions\KeyboardButton;

use App\Services\TelegramQuestionEditMessage as TgEditMessage;
use App\Services\TelegramQuestionMessage as TgMessage;

class SuperadminConversation extends Conversation {

  use \App\Traits\PayoutsTrait;
  use \App\Traits\UserDeleteTrait;
  use \App\Traits\FileTrait;

  public $auth;

  public function __construct(User $auth) {
    $this->auth = $auth;
  }


  public function user_information($id, $backTo = "user_list") {
    $user = User::with(["Tg", "Cb", "Payouts"])->find($id);

    $text = "";
    $text .= "Роль: *" . $user->role . "*" . PHP_EOL;
    $text .= "Telegram username: *@" . $user->Tg->username . "*" . PHP_EOL;
    if (!empty($user->Cb)) {
      $text .= PHP_EOL . "Chaturbate username: *" . $user->Cb->username . "*" . PHP_EOL;
      $text .= "Заработано в Cb: *$" . (new \App\Services\CbApiService)->getUserAllTimePayout($user->Cb->username) . "*" . PHP_EOL;
    }
    if (!empty($user->Payouts->all())) {
      $text .= "Авансы:" . PHP_EOL;
      foreach ($user->Payouts as $item) {
        $text .= date("d.m.y H:i", strtotime($item->status_updated_at)) . " *$" . $item->amount . " (" . $item->amount_payed_rub . " руб)*" . PHP_EOL;
      }
      $text .= "ИТОГО: *$" . $user->Payouts->sum('amount') . " (" . $user->Payouts->sum('amount_payed_rub') . " руб)*";
    }

    $keyboard = [
      'reply_markup' => (new \App\Services\ButtonsAndTextServiсe)->buttons_user_information($user->id, $this->auth)
    ];

    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask(
      $question,
      function (Answer $answer) use ($user, $backTo) {
        if ($answer->isInteractiveMessageReply()) {
          switch ($answer->getValue()) {
            case "back":
              return $this->{$backTo}();
              break;
            case "DeleteUser":
              return $this->deleteUserConfirmation($user->id);
              break;
          }
        }
        return $this->{$backTo}();
      },
      ['parse_mode' => 'markdown']
    );
  }

  public function user_list() {
    $keyboard = [
      'reply_markup' => (new \App\Services\ButtonsAndTextServiсe)->buttons_()
    ];
    $text = "=== Пользователи ===";

    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask($question, function (Answer $answer) {
      if ($answer->isInteractiveMessageReply()) {
        if ($answer->getValue() === "back") {
          return $this->start();
        } elseif (intval($answer->getValue())) {
          return $this->user_information(intval($answer->getValue()));
        }
      }
      return $this->start();
    });
  }

  public function superadmin_panel() {
    $keyboard = Keyboard::create()->type(Keyboard::TYPE_INLINE)
      ->addRow(KeyboardButton::create('🔙 Назад')->callbackData('back'))
      ->addRow(KeyboardButton::create('Список пользователей')->callbackData('UserList'))
      ->addRow(KeyboardButton::create('➕ Добавить пользователя')->callbackData('UserCreate'))
      ->toArray();

    $text = "=== Админ-панель ===";
    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask($question, function (Answer $answer) {
      if ($answer->isInteractiveMessageReply()) {
        switch ($answer->getValue()) {
          case "UserList":
            return $this->user_list();
            break;
          case "UserCreate":
            return $this->bot->startConversation(new \App\Conversations\UserCreateConversation($this->auth));
            break;
          case "back":
            return $this->start();
            break;
        }
      }
      return $this->start();
    });
  }

  public function superadminStatistics($subMonths = "All", $viewType = "view_buttons") {
    $buttons_and_text = (new \App\Services\ButtonsAndTextServiсe)->buttons_payment_and_payouts($subMonths, $viewType);
    $keyboard = [
      'reply_markup' => $buttons_and_text['reply_markup']
    ];

    $text = $buttons_and_text['text'];
    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask(
      $question,
      function (Answer $answer) use ($subMonths, $viewType) {
        if ($answer->isInteractiveMessageReply()) {
          if ($answer->getValue() === 'back') {
            return $this->start();
          } elseif ($answer->getValue() === "view_table") {
            return $this->superadminStatistics($subMonths, "view_table");
          } elseif ($answer->getValue() === "view_buttons") {
            return $this->superadminStatistics($subMonths, "view_buttons");
          } elseif (strpos($answer->getValue(), "month")) {
            return $this->superadminStatistics(strval(str_replace("month", "", $answer->getValue())), $viewType);
          } elseif (intval($answer->getValue())) {
            return $this->user_information(intval($answer->getValue()), "superadminStatistics");
          }
        }
        return $this->start();
      },
      $buttons_and_text['parse_mode']
    );


    // $keyboard = Keyboard::create()->type(Keyboard::TYPE_INLINE)
    //   ->addRow(KeyboardButton::create('🔙 Назад')->callbackData('back'))
    //   ->addRow(KeyboardButton::create(($subMonths === 'All' ? '[ За все время ]' : 'За все время'))->callbackData('All'))
    //   ->addRow(KeyboardButton::create(($subMonths === '1' ? '[ За 1 месяц ]' : 'За 1 месяц'))->callbackData('1'))
    //   ->addRow(KeyboardButton::create(($subMonths === '2' ? '[ За 2 месяца ]' : 'За 2 месяца'))->callbackData('2'))
    //   ->addRow(KeyboardButton::create(($subMonths === '3' ? '[ За 3 месяца ]' : 'За 3 месяца'))->callbackData('3'))
    //   ->addRow(KeyboardButton::create(($subMonths === '6' ? '[ За 6 месяцев ]' : 'За 6 месяцев'))->callbackData('6'))
    //   ->addRow(KeyboardButton::create(($subMonths === '12' ? '[ За 12 месяцев ]' : 'За 12 месяцев'))->callbackData('12'))
    //   ->toArray();

    // if ($subMonths === 'All') {
    //   $text = (new \App\Services\CbApiService)->getAccauntStatistic(60, $this->auth->Cb->username);
    // } else {
    //   $text = (new \App\Services\CbApiService)->getAccauntStatistic($subMonths, $this->auth->Cb->username);
    // }

    // if (strpos($text, "Нет данных") === false) {
    //   $parse_mode = ['parse_mode' => 'HTML'];
    //   $text = '<pre>' . $text . '</pre>';
    // } else {
    //   $parse_mode = [];
    // }

    // $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    // return $this->ask($question, function (Answer $answer) {
    //   if ($answer->isInteractiveMessageReply()) {
    //     switch ($answer->getValue()) {
    //       case "back":
    //         return $this->start();
    //         break;
    //       case "All":
    //       case '1':
    //       case '2':
    //       case '3':
    //       case '6':
    //       case '12':
    //         return $this->superadminStatistics($answer->getValue());
    //         break;
    //     }
    //   }
    //   return $this->superadminStatistics('3');
    // }, $parse_mode);
  }

  public function start() {
    $keyboard = Keyboard::create()->type(Keyboard::TYPE_INLINE)
      ->addRow(KeyboardButton::create('Админ-панель')->callbackData('superadmin_panel'))
      ->addRow(KeyboardButton::create('Статистика')->callbackData('superadminStatistics'))
      ->addRow(KeyboardButton::create('Файлы (правила, помощь)')->callbackData('files_list'))
      ->addRow(KeyboardButton::create('Выплаты')->callbackData('payout_list_all'))
      ->toArray();

    $text = "Разделы для " . $this->auth->role . " " . $this->auth->name;

    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask($question, function (Answer $answer) {
      if ($answer->isInteractiveMessageReply()) {
        switch ($answer->getValue()) {
          case "superadmin_panel":
            return $this->superadmin_panel();
            break;
          case "superadminStatistics":
            return $this->superadminStatistics();
            break;
          case "files_list":
            return $this->files_list();
            break;
          case "payout_list_all":
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

  /*
  public function payouts($subMonths) {
    $keyboard = Keyboard::create()->type(Keyboard::TYPE_INLINE)
      ->addRow(KeyboardButton::create('🔙 Назад')->callbackData('back'))
      ->addRow(KeyboardButton::create(($subMonths === 'All' ? '[ За все время ]' : 'За все время'))->callbackData('All'))
      ->addRow(KeyboardButton::create(($subMonths === '1' ? '[ За 1 месяц ]' : 'За 1 месяц'))->callbackData('1'))
      ->addRow(KeyboardButton::create(($subMonths === '2' ? '[ За 2 месяца ]' : 'За 2 месяца'))->callbackData('2'))
      ->addRow(KeyboardButton::create(($subMonths === '3' ? '[ За 3 месяца ]' : 'За 3 месяца'))->callbackData('3'))
      ->addRow(KeyboardButton::create(($subMonths === '6' ? '[ За 6 месяцев ]' : 'За 6 месяцев'))->callbackData('6'))
      ->addRow(KeyboardButton::create(($subMonths === '12' ? '[ За 12 месяцев ]' : 'За 12 месяцев'))->callbackData('12'))
      ->toArray();

    $text = "<pre>" . (new \App\Services\ButtonsAndTextServiсe)->payoutsTable_admin(intval($subMonths)) . "</pre>";

    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask(
      $question,
      function (Answer $answer) {
        if ($answer->isInteractiveMessageReply()) {
          switch ($answer->getValue()) {
            case "back":
              return $this->start();
              break;
            case "All":
            case '1':
            case '2':
            case '3':
            case '6':
            case '12':
              return $this->payouts($answer->getValue());
              break;
          }
        }
        return $this->start();
      },
      ["parse_mode" => "HTML"]
    );
  }
  */
}
