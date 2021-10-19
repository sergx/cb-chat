<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotMan;
use Illuminate\Http\Request;
use App\Conversations\ExampleConversation;
use App\Conversations\UserCreateConversation;
use App\Conversations\UserListConversation;
use App\Conversations\NavConversation;

use App\Conversations\SuperadminConversation;
use App\Conversations\AdminConversation;
use App\Conversations\UserConversation;
use App\User;

class BotManController extends Controller {

  /**
   * Place your BotMan logic here.
   */
  public function handle() {

    $botman = app('botman');

    $botman->listen();
  }

  /**
   * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
   */
  public function tinker() {
    return view('tinker');
  }

  public function start(BotMan $bot) {
    $telegram_user_id = $bot->getUser()->getId();
    //$telegram_user_id = 337506768;
    $auth = User::with(['Tg', 'Cb', 'Payouts'])->whereHas('Tg', function ($query) use ($telegram_user_id) {
      $query->where('id', '=', $telegram_user_id);
    })->first();

    if (!$auth) {
      $bot->reply("Отказано в доступе. Пользователь @" . $bot->getUser()->getUsername() . " (telegram id " . $telegram_user_id . ") не найден в системе");
      return;
    }
    //$bot->reply($auth->role);
    //return $bot->startConversation(new AdminConversation($auth));

    switch ($auth->role) {
      case "superadmin":
        $bot->startConversation(new SuperadminConversation($auth));
        break;
      case "admin":
        $bot->startConversation(new AdminConversation($auth));
        break;
      case "user":
        $bot->startConversation(new UserConversation($auth));
        break;
    }
  }

  /**
   * Loaded through routes/botman.php
   * @param  BotMan $bot
   */
  public function startConversation(BotMan $bot) {
    $bot->startConversation(new ExampleConversation());
  }

  //public function userCreate(BotMan $bot) {
    // $telegram_user_id = $bot->getUser()->getId();

    // $user = User::with(['Tg'])->whereHas('Tg', function ($query) use ($telegram_user_id) {
    //   $query->where('id', '=', $telegram_user_id);
    // })->first();

    // $bot->reply($user->role);

    // if (in_array($user->role, ['superadmin', 'admin'])) {
    //   $bot->startConversation(new UserCreateConversation());
    // }
  //}

  public function userlList(BotMan $bot) {
    // $telegram_user_id = $bot->getUser()->getId();
    // $bot->reply($telegram_user_id);

    // $user = User::with(['Tg'])->whereHas('Tg', function ($query) use ($telegram_user_id) {
    //   $query->where('id', '=', $telegram_user_id);
    // })->first();

    //$bot->reply('yo');
    //if (in_array($user->role, ['superadmin', 'admin'])) {
    $bot->reply($this->userList());
    //$bot->startConversation(new UserListConversation());
    //}
  }
  public function userList() {
    $result = [];

    $users = User::with(["Tg", "Cb"])->get();
    //dd($users);
    foreach ($users as $user) {
      $ta = [];
      if ($user->Tg) {
        $ta[] = "Tg: @". $user->Tg->username ." (".$user->role.")";
      }
      if ($user->Cb) {
        $ta[] = "Cb: ".$user->Cb->username;
      }else{
        $ta[] = "Cb: n/a";
      }
      $ta[] = "/delete_user_". $user->id;

      $result[] = implode(PHP_EOL, $ta);
    }
    return implode(PHP_EOL.PHP_EOL, $result);
  }

  public function db() {
    dd($this->userList());
  }

}
