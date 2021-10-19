<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use App\User;
use App\Payout;

use LVR\CreditCard\Cards\Card as CardNumberValidate;
use App\Services\CbApiService;
use App\Services\CbrApiService as Cbr;

use BotMan\Drivers\Telegram\Extensions\Keyboard;
use BotMan\Drivers\Telegram\Extensions\KeyboardButton;
use App\Services\TelegramQuestionEditMessage as TgEditMessage;
use App\Services\TelegramQuestionMessage as TgMessage;

class UserPayoutConversation extends Conversation {

  public $auth;
  public $CbUserAllTimePayout;

  public function __construct(User $auth) {
    $this->auth = $auth;
    $total_payed = $this->auth->Payouts->whereIn('status', [0, 2])->sum('amount');
    $this->CbUserAllTimePayout = (new CbApiService)->getUserAllTimePayout($this->auth->Cb->username) - $total_payed;
  }

  public function refreshUserInfo() {
    $telegram_user_id = $this->bot->getUser()->getId();
    //$telegram_user_id = 337506768;
    $this->auth = User::with(['Tg', 'Cb', 'Payouts'])->whereHas('Tg', function ($query) use ($telegram_user_id) {
      $query->where('id', '=', $telegram_user_id);
    })->first();
  }

  public function userGetPayment_create() {
    $messagePayload = $this->bot->getMessage()->getPayload();

    $newPayoutData = [
      'user_id' => $this->auth->id,
      'amount' => $this->newPayout['amount'],
      'type' => $this->newPayout['type'],
    ];

    if (!empty($this->newPayout['payment_details'])) {
      $newPayoutData['payment_details'] = $this->newPayout['payment_details'];
    }

    $payout = $this->auth->Payouts()->create($newPayoutData);

    $text = "";
    $text .= "НОВАЯ ЗАЯВКА НА ВЫВОД СРЕДСТВ ОФОРМЛЕНА" . PHP_EOL;
    $text .= "Сумма: *$" . $payout->amount . " ( " . (new Cbr)->rate("USD", $payout->amount) . " руб )*" . PHP_EOL;
    $text .= "Тип вывода: *" . __("bot.payout_type_" . $payout->type) . "*" . PHP_EOL;

    if ($payout->type == 1) {
      $text .= "Номер карты: *" . $payout->payment_details . "*" . PHP_EOL;
    }

    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, [], $this->bot->getMessage()->getPayload());
    $this->bot->reply($question, ['parse_mode' => 'Markdown']);
    // $reply = TgEditMessage::create($text)->setMessageId($messagePayload['message_id']);

    // $this->bot->reply($reply, ['parse_mode' => 'Markdown']);

    $this->refreshUserInfo();

    return $this->bot->startConversation(new \App\Conversations\UserConversation($this->auth));
  }

  public function userGetPayment_setDetails($text = "") {
    $keyboard = Keyboard::create()->type(Keyboard::TYPE_INLINE)
      ->addRow(KeyboardButton::create('🔙 Назад (изменить способ получения)')->callbackData('back'))
      ->toArray();

    $text .= "УКАЖИТЕ НОМЕР БАНКОВСКОЙ КАРТЫ (16 цифр)" . PHP_EOL;
    $text .= "Сумма для получения: *$" . $this->newPayout['amount'] . " ( " . (new Cbr)->rate("USD", $this->newPayout['amount']) . " руб )*";
    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask(
      $question,
      function (Answer $answer) {
        if ($answer->isInteractiveMessageReply()) {
          switch ($answer->getValue()) {
            case "back":
              return $this->userGetPayment_setType();
              break;
          }
        } else {
          $payment_details = intval(preg_replace("/[^0-9]/", '', $answer->getText()));
          try {
            \LVR\CreditCard\Factory::makeFromNumber($payment_details)->isValidCardNumber();
            $this->newPayout['payment_details'] = $payment_details;
            return $this->userGetPayment_create();
          } catch (\Exception $e) {
            return $this->userGetPayment_setDetails("```" . PHP_EOL . $answer->getText() . " — некорректный номер карты" . PHP_EOL . "```");
          }
        }
        //return $this->bot->startConversation(new \App\Conversations\UserConversation($this->auth));
      },
      ['parse_mode' => 'Markdown']
    );
  }

  public function userGetPayment_setType() {
    $keyboard = Keyboard::create()->type(Keyboard::TYPE_INLINE)
      ->addRow(KeyboardButton::create('🔙 Назад (изменить сумму)')->callbackData('back'))
      ->addRow(
        KeyboardButton::create('Наличными')->callbackData('type_cache'),
        KeyboardButton::create('На банковскую карту')->callbackData('type_card'),
      )
      ->toArray();

    $text = "КАК ХОТИТЕ ПОЛУЧИТЬ ДЕНЬГИ?" . PHP_EOL;
    $text .= "Сумма для получения: *$" . $this->newPayout['amount'] . " ( " . (new Cbr)->rate("USD", $this->newPayout['amount']) . " руб )*";
    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask(
      $question,
      function (Answer $answer) {
        if ($answer->isInteractiveMessageReply()) {
          switch ($answer->getValue()) {
            case "type_card":
              $this->newPayout['type'] = 1;
              return $this->userGetPayment_setDetails();
              break;
            case "type_cache":
              $this->newPayout['type'] = 2;
              return $this->userGetPayment_create();
              break;
            case "back":
              return $this->userGetPayment_setAmount();
              break;
          }
        }
        return $this->bot->startConversation(new \App\Conversations\UserConversation($this->auth));
      },
      ['parse_mode' => 'Markdown']
    );
  }

  public function userGetPayment_setAmount() {
    $keyboard = Keyboard::create()->type(Keyboard::TYPE_INLINE)
      ->addRow(KeyboardButton::create('🔙 Назад')->callbackData('back'))
      ->toArray();

    $text = "*УКАЖИТЕ СУММУ ДЛЯ ПОЛУЧЕНИЯ*" . PHP_EOL . "Доступно: *$" . $this->CbUserAllTimePayout . "*";
    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask(
      $question,
      function (Answer $answer) {
        if ($answer->isInteractiveMessageReply()) {
          switch ($answer->getValue()) {
            case "back":
              return $this->userGetPayment();
              break;
          }
        } else {
          $amount = floatval(str_replace(",", ".", $answer->getText()));
          if ($amount > $this->CbUserAllTimePayout || !$amount) {
            $this->bot->reply("Вы указали некорректную сумму..");
            return $this->userGetPayment_setAmount();
          }
          $this->newPayout['amount'] = $amount;
          return $this->userGetPayment_setType();
        }
        return $this->userGetPayment();
      },
      ['parse_mode' => 'Markdown']
    );
  }

  public function userGetPayment() {
    $keyboard = Keyboard::create()->type(Keyboard::TYPE_INLINE)
      ->addRow(KeyboardButton::create('🔙 Назад')->callbackData('back'))
      ->addRow(KeyboardButton::create('Получить всю сумму')->callbackData('getAll'))
      ->addRow(KeyboardButton::create('Указать сумму для получения')->callbackData('setAmount'))
      ->toArray();

    $text = "Доступно для получения *$" . $this->CbUserAllTimePayout . "*";
    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask(
      $question,
      function (Answer $answer) {
        if ($answer->isInteractiveMessageReply()) {
          switch ($answer->getValue()) {
            case "getAll":
              $this->newPayout['amount'] = $this->CbUserAllTimePayout;
              return $this->userGetPayment_setType();
              break;
            case "setAmount":
              return $this->userGetPayment_setAmount();
              break;
            case "back":
              return $this->bot->startConversation(new \App\Conversations\UserConversation($this->auth));
              break;
          }
        }
        return $this->bot->startConversation(new \App\Conversations\UserConversation($this->auth));
      },
      ['parse_mode' => 'Markdown']
    );
  }

  public function run() {
    return $this->userGetPayment();
  }
}
