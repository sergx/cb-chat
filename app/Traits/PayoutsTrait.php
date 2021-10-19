<?php

namespace App\Traits;

use BotMan\BotMan\Messages\Incoming\Answer;
use App\Payout;

trait PayoutsTrait{

  public function payout_list_all($subMonths = "All", $viewType = "view_buttons") {
    $buttons_and_text = (new \App\Services\ButtonsAndTextServiсe)->buttons_payout_list_all($subMonths, $viewType);
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
            return $this->payout_list_all($subMonths, "view_table");
          } elseif ($answer->getValue() === "view_buttons") {
            return $this->payout_list_all($subMonths, "view_buttons");
          } elseif (strpos($answer->getValue(), "month")) {
            return $this->payout_list_all(strval(str_replace("month", "", $answer->getValue())), $viewType);
          } elseif (intval($answer->getValue())) {
            return $this->payout_item(intval($answer->getValue()), "payout_list_all");
          }
        }
        return $this->start();
      },
      $buttons_and_text['parse_mode']
    );
  }

  public function payout_item($id, $backTo = "payout_list_only_new") {
    $buttons_and_text = (new \App\Services\ButtonsAndTextServiсe)->buttons_payout_item($id);
    $keyboard = [
      'reply_markup' => $buttons_and_text['reply_markup']
    ];

    $text = $buttons_and_text['text'];
    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask(
      $question,
      function (Answer $answer) use ($id, $backTo) {
        if ($answer->isInteractiveMessageReply()) {
          switch ($answer->getValue()) {
            case "back":
              return $this->{$backTo}();
              break;
            case "payout_done":
              return $this->payout_item_update_status($id, 2);
              break;
            case "payout_rejected":
              return $this->payout_item_update_status($id, 3);
              break;
          }
        }
        return $this->start();
      },
      ['parse_mode' => 'HTML']
    );
  }

  public function payout_list_only_new() {
    $keyboard = [
      'reply_markup' => (new \App\Services\ButtonsAndTextServiсe)->buttons_payout_list_only_new()
    ];

    $text = "Новые заявки на выплаты";
    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask($question, function (Answer $answer) {
      if ($answer->isInteractiveMessageReply()) {
        if (intval($answer->getValue())) {
          return $this->payout_item(intval($answer->getValue()));
        }
      }
      return $this->start();
    });
  }

  public function payout_item_update_status($id, $new_status) {
    $payout = Payout::find($id);
    $payout->status = $new_status;
    $payout->amount_payed_rub = (new \App\Services\CbrApiService)->rate("USD", $payout->amount);
    $payout->status_updated_at = now();
    $payout->save();
    return $this->payout_item($id);
  }

}

?>
