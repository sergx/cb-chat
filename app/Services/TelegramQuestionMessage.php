<?php

namespace App\Services;

class TelegramQuestionMessage{

  protected $text = "";
  protected $keyboard = ['reply_markup' => ""];
  protected $message_id = "";

  public function __construct($text = "") {
    $this->text = $text;
  }

  public static function create($text = "") {
    return new static($text);
  }

  public function setKeyboard($keyboard) {
    $this->keyboard = $keyboard;
    return $this;
  }

  public function getReplyMarkup() {
    return $this->keyboard['reply_markup'];
  }

  public function getText() {
    return $this->text;
  }
}
