<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Conversations\Conversation;
use App\User;
use Tridcatij\Asciitables\AsciitableFacade as Asciitable;

use App\Services\CbApiService;

use BotMan\Drivers\Telegram\Extensions\Keyboard;
use BotMan\Drivers\Telegram\Extensions\KeyboardButton;

use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;


class UserConversation extends Conversation {

  public $auth;


  public function __construct(User $auth) {
    $this->auth = $auth;
    $total_payed = $this->auth->Payouts->whereIn('status', [0, 1])->sum('amount');
    $this->CbUserAllTimePayout = (new CbApiService)->getUserAllTimePayout($this->auth->Cb->username) - $total_payed;
  }

  public $newPayout = [];

  /**
   * Статистика
   *   Кнопка «Выберите период»
   *     Список для выбранного периода в виде:
   *     Номер выплаты
   *     Дата
   *     Ник пользователя на чб
   *     Сумма аванса
   *     Наличные или карта
   *   Кнопка «Вся история»
   *     Список всей истории в виде:
   *     Номер выплаты
   *     Дата
   *     Ник пользователя на чб
   *     Сумма аванса
   *     Наличные или карта
   * Получить выплату
   *   Кнопка «Наличные»
   *     Введите сумму
   *     Кнопка «Подтвердить»
   *     Кнопка «Отменить»
   *   Кнопка «На карту»
   *     Введите сумму
   *     Введите номер карты (с проверкой кол-ва цифр от 16 до 20)
   *     Кнопка «Подтвердить»
   *     Кнопка «Отменить»
   * Правила (PDF файл)
   * Помощь (PDF файл)
   * Кнопка написать админу
   */



  public function userStatistics(String $subMonths) {

    $payouts_stat = [];
    $has_new = false;
    $has_rejected = false;

    foreach ($this->auth->Payouts as $item) {
      $payouts_stat[] = [
        "Дата" => date("d.m.y", strtotime($item->updated_at)),
        "Платеж" => __("bot.payout_status_" . $item->status . "_icon") . "$" . $item->amount,
        "Куда" => __("bot.payout_type_" . $item->type . "_short"),
      ];
      if ($item->status == 0) {
        $has_new = true;
      }
      if ($item->status == 3) {
        $has_rejected = true;
      }
    }
    $text = Asciitable::make_table(
      $payouts_stat,
      "Заработно на Chaturbate" . PHP_EOL . " $" . (new CbApiService)->getUserAllTimePayout($this->auth->Cb->username) . " за все время " . PHP_EOL .
        "Выплаты вам:",
      true
    );
    if ($has_new) {
      $text .= "Пометка ~ означает, что  платеж в процессе обработки;" . PHP_EOL;
    }
    if ($has_rejected) {
      $text .= "Пометка x означает, что  платеж отклонен;" . PHP_EOL;
    }
    if ($has_new || $has_rejected) {
      $text .= "Если платеж без пометки, значит веньги отправлены;" . PHP_EOL;
    }

    $keyboard = Keyboard::create()->type(Keyboard::TYPE_INLINE)
      ->addRow(KeyboardButton::create('🔙 Назад')->callbackData('back'))
      ->toArray();

    $text = "<pre>" . $text . "</pre>";
    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask($question, function (Answer $answer) {
      switch ($answer->getValue()) {
        case "back":
          return $this->start();
          break;
      }
      return $this->start();
    }, ["parse_mode" => "HTML"]);
  }

  public function contactWithAdmin() {
    $keyboard = Keyboard::create()->type(Keyboard::TYPE_INLINE)
      ->addRow(KeyboardButton::create('🔙 Назад')->callbackData('back'))
      ->toArray();

    $text = "Для связи с админом .....";

    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());
    return $this->ask($question, function (Answer $answer) {
      switch ($answer->getValue()) {
        case "back":
          return $this->start();
          break;
      }
      return $this->start();
    });
  }

  public function attatchFile($basename) {
    $file = \App\File::where('filename', 'LIKE', $basename . '.%')->first();

    if (!$file) {
      $keyboard = Keyboard::create()->type(Keyboard::TYPE_INLINE)
        ->addRow(KeyboardButton::create('🔙 Назад')->callbackData('back'))
        ->toArray();

      $text = "Файл еще на загружен";

      $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());
      return $this->ask($question, function (Answer $answer) {
        switch ($answer->getValue()) {
          case "back":
            return $this->start();
            break;
        }
        return $this->start();
      });
    }

    $attachment = new File(env('NGROCK_URL') . $file->path, [
      'custom_payload' => true,
    ]);

    $message = OutgoingMessage::create()
      ->withAttachment($attachment);

    $this->bot->reply($message);
    $this->bot->reply("Для возвращения в меню, нажмите /start");
  }

  public function start($force_new = false) {
    $keyboard = Keyboard::create()->type(Keyboard::TYPE_INLINE)
      ->addRow(KeyboardButton::create('Статистика')->callbackData('userStatistics'))
      ->addRow(KeyboardButton::create('Получить выплату')->callbackData('userGetPayment'))
      ->addRow(
        KeyboardButton::create('Правила (PDF файл)')->callbackData('userRules'),
        KeyboardButton::create('Помощь (PDF файл)')->callbackData('userHelp'),
      )
      ->addRow(KeyboardButton::create('Написать админу')->callbackData('contactWithAdmin'))
      ->toArray();

    $text = "Разделы";
    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload(), $force_new);

    return $this->ask($question, function (Answer $answer) {
      if ($answer->isInteractiveMessageReply()) {
        switch ($answer->getValue()) {
          case "userStatistics":
            return $this->userStatistics('3');
            break;
          case "userGetPayment":
            return $this->bot->startConversation(new \App\Conversations\UserPayoutConversation($this->auth));
            break;
          case "userRules":
            return $this->attatchFile("rules");
            break;
          case "userHelp":
            return $this->attatchFile("help");
            break;
          case "contactWithAdmin":
            return $this->contactWithAdmin();
            break;
        }
      }
    });
  }

  public function run() {
    return $this->start(true);
  }
}
