<?php

namespace App\Services;

use App\Services\TelegramQuestionMessage;

class TelegramQuestionEditMessage extends TelegramQuestionMessage {
  public function setMessageId($message_id) {
    $this->message_id = $message_id;
    return $this;
  }
  public function getMessageId() {
    return $this->message_id;
  }
}
