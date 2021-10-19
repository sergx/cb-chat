<?php

namespace App\Traits;

use BotMan\BotMan\Messages\Incoming\Answer;
use App\Services\TelegramQuestionEditMessage as TgEditMessage;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

trait FileTrait {

  public function files_list() {
    $want = [
      ['basename' => 'rules', 'titlename' => 'Правила'],
      ['basename' => 'help', 'titlename' => 'Помощь'],
    ];

    $files = \App\File::all();

    $rows = [
      [['text' => "🔙 Назад", 'callback_data' => "back"]],
    ];

    foreach ($want as $item) {
      $file = $files->filter(function($value) use ($item){return strpos($value['filename'], $item['basename'].".") === 0;})->first();
      if (!$file) {
        $rows[] = [['text' => "«" . $item['titlename'] . "» не загружен. Загрузить ->", "callback_data" => "FileUpload:" . $item['basename']]];
      } else {
        $rows[] = [['text' => "«" . $item['titlename'] . "» есть (" . $file->filename . "). Обновить ->", "callback_data" => "FileUpload:" . $item['basename']]];
        $rows[] = [['text' => "Скачать файл " . $file->filename, "callback_data" => "FileAttache:" . $file->path]];
      }
    }

    $keyboard = [
      'reply_markup' => json_encode([
        'inline_keyboard' => $rows,
        'one_time_keyboard' => false,
        'resize_keyboard' => false,
      ])
    ];

    $text = "Файлы";

    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask($question, function (Answer $answer) {
      if ($answer->getValue() === "back") {
        return $this->start();
      }
      elseif (strpos($answer->getValue(), "FileUpload:") === 0) {
        return $this->fileUpload(str_replace("FileUpload:", "", $answer->getValue()) . ".");
      }
      elseif (strpos($answer->getValue(), "FileAttache:") === 0) {
        return $this->FileAttache(str_replace("FileAttache:", "", $answer->getValue()));
      }
      $this->bot->startConversation(new \App\Conversations\SuperadminConversation($this->auth));
    });
  }

  public function fileManage($basename, $titlename) {
    $file = \App\File::where('filename', 'LIKE', $basename . '.%')->first();

    $rows = [
      [['text' => "🔙 Назад", 'callback_data' => "back"]],
    ];

    if ($file) {
      $rows[] = [['text' => 'Скачать файл', 'callback_data' => 'FileAttache']];
      $rows[] = [['text' => 'Загрузить новый файл', 'callback_data' => 'FileUpload']];
      $text = "Файл «" . $titlename . "» уже есть (" . $file->filename . ")";
    } else {
      $rows[] = [['text' => 'Загрузить файл', 'callback_data' => 'FileUpload']];
      $text = "Файл «" . $titlename . "» не найден в базе данных..";
    }

    $keyboard = [
      'reply_markup' => json_encode([
        'inline_keyboard' => $rows,
        'one_time_keyboard' => false,
        'resize_keyboard' => false,
      ])
    ];

    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask($question, function (Answer $answer) use ($file, $basename) {
      if ($answer->isInteractiveMessageReply()) {
        switch ($answer->getValue()) {
          case "back":
            return $this->start();
            break;
          case "FileAttache":
            return $this->fileAttache($file->path);
            break;
          case "FileUpload":
            return $this->fileUpload($basename . ".");
            break;
        }
      }
      $this->bot->startConversation(new \App\Conversations\SuperadminConversation($this->auth));
    });
  }

  public function rulesFile() {
    $rules_file = \App\File::where('filename', 'LIKE', 'rules.%')->first();

    $rows = [
      [['text' => "🔙 Назад", 'callback_data' => "back"]],
    ];

    if ($rules_file) {
      $rows[] = [['text' => 'Скачать файл', 'callback_data' => 'rulesFileAttache']];
      $rows[] = [['text' => 'Загрузить новый файл', 'callback_data' => 'rulesFileUpload']];
      $text = "Файл «Правила» уже есть (" . $rules_file->filename . ")";
    } else {
      $rows[] = [['text' => 'Загрузить файл', 'callback_data' => 'rulesFileUpload']];
      $text = "Файл «Правила» не найден в базе данных..";
    }

    $keyboard = [
      'reply_markup' => json_encode([
        'inline_keyboard' => $rows,
        'one_time_keyboard' => false,
        'resize_keyboard' => false,
      ])
    ];

    $question = (new \App\Services\BotHelpers)->TelegramQuestion($text, $keyboard, $this->bot->getMessage()->getPayload());

    return $this->ask($question, function (Answer $answer) use ($rules_file) {
      if ($answer->isInteractiveMessageReply()) {
        switch ($answer->getValue()) {
          case "back":
            return $this->start();
            break;
          case "rulesFileAttache":
            return $this->fileAttache($rules_file->path);
            break;
          case "rulesFileUpload":
            return $this->fileUpload("rules.");
            break;
        }
      }
      $this->bot->startConversation(new \App\Conversations\SuperadminConversation($this->auth));
    });
  }


  public function fileAttache($path) {
    $attachment = new File(env('NGROCK_URL') . $path, [
      'custom_payload' => true,
    ]);

    $message = OutgoingMessage::create()
      ->withAttachment($attachment);

    $this->bot->reply($message);
    $this->bot->reply("Для возвращения в меню, нажмите /start");
  }


  public function fileUpload($base_name) {
    $file = \App\File::where('filename', 'LIKE', $base_name . '%')->first();

    if ($file) {
      $text = "Файл уже есть (" . $file->filename . "). Вы можете перезаписать его, загрузив новый файл. Чтобы вернуться в меню, нажмине /start";
    } else {
      $text = "Загрузите файл. Чтобы вернуться в меню, нажмине /start";
    }

    $question = TgEditMessage::create($text)
      ->setMessageId($this->bot->getMessage()->getPayload()['message_id']);

    return $this->askForFiles($question, function ($answer) use ($base_name) {
      foreach ($answer as $file) {
        $ext = pathinfo($file->getUrl(), PATHINFO_EXTENSION);
        $filename = $base_name . $ext;
        \Illuminate\Support\Facades\Storage::put('public/' . $filename, file_get_contents($file->getUrl()));
        \App\File::where('filename', 'LIKE', $base_name . '%')->delete();
        \App\File::create(['filename' => $filename, 'path' => 'storage/' . $filename]);
      }
      return $this->start();
    });
  }
}
