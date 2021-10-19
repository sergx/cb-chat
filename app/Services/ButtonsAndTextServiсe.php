<?php

namespace App\Services;

use App\Payout;
use App\User;
use App\Services\CbrApiService as Cbr;

class ButtonsAndTextServiсe {

  public function buttons_user_information($id, $auth) {
    $user = User::find($id);
    $user_roles = ['superadmin', 'admin', 'user'];
    unset($user_roles[array_search($user->role, $user_roles)]);

    $rows = [
      [
        ['text' => "🔙 Назад", 'callback_data' => "back"],
      ]
    ];

    if ($id != $auth->id) {
      $rows[] = [['text' => '❌ Удалить пользователя', 'callback_data' => 'DeleteUser']];
      $roles = [];
      foreach ($user_roles as $role) {
        $roles[] = ['text' => 'Set role: ' . $role, 'callback_data' => 'set_role:' . $role];
      }
      $rows[] = $roles;
    }
    return json_encode([
      'inline_keyboard' => $rows,
      'one_time_keyboard' => false,
      'resize_keyboard' => false,
    ]);
  }

  public function buttons_() {
    $users = User::with(["Tg", "Cb"])->get();

    $rows = [
      [
        ['text' => "🔙 Назад", 'callback_data' => "back"],
      ]
    ];
    foreach ($users as $item) {
      $title = $item->role . " | @" . $item->Tg->username;
      if (!empty($item->Cb)) {
        $title .= " | " . $item->Cb->username;
      }
      $rows[] = [
        ['text' => $title, 'callback_data' => $item->id],
      ];
    }
    return json_encode([
      'inline_keyboard' => $rows,
      'one_time_keyboard' => false,
      'resize_keyboard' => false,
    ]);
  }

  public function buttons_payout_item($id) {
    $payout = Payout::where('id', $id)
      ->with(["BasicUser", "BasicUser.Cb"])
      ->first();
    $user_id = $payout->BasicUser->id;

    $payouts_for_model = Payout::with(["BasicUser"])->where('status', 2)->whereHas('BasicUser', function ($query) use ($user_id) {
      $query->where('id', $user_id);
    })->get();

    $rows = [
      [
        ['text' => "🔙 Назад", 'callback_data' => "back"]
      ]
    ];
    if ($payout->status === 0) {
      $rows[] = [
        ['text' => "✅ Оплачено", 'callback_data' => "payout_done"],
        ['text' => "❌ Отклонить", 'callback_data' => "payout_rejected"],
      ];
    }
    $text = "ЗАЯВКА" . PHP_EOL;
    $text .= "Подана заявка: <b>" . date("d.m.y H:i", strtotime($payout->created_at)) . "</b>" . PHP_EOL;
    $text .= "Статус: " . __('bot.payout_status_' . $payout->status . '_emoji') . " <b>" . __('bot.payout_status_' . $payout->status) . "</b>" . PHP_EOL . PHP_EOL;

    if ($payout->status === 2) {
      $text .= "Дата оплаты: <b>" . date("d.m.y H:i", strtotime($payout->updated_at)) . "</b>" . PHP_EOL;
    }
    $text .= "Сумма аванса: <b>$" . $payout->amount . " ( " . (new Cbr)->rate("USD", $payout->amount) . " руб )</b>" . PHP_EOL;
    $text .= "Тип: <b>" . __('bot.payout_type_' . $payout->type . '_short') . "</b>" . PHP_EOL;
    if ($payout->type === 1) {
      $text .= "№ карты: <b>" . $payout->payment_details . "</b>" . PHP_EOL;
    }
    $text .= PHP_EOL;
    $text .= "МОДЕЛЬ" . PHP_EOL;
    $text .= "Ник Chaturbate: <b>" . $payout->BasicUser->Cb->username . "</b>" . PHP_EOL;
    $text .= "Ник Telegram: @" . $payout->BasicUser->Tg->username . "" . PHP_EOL . PHP_EOL;
    $text .= "<b>$" . (new \App\Services\CbApiService)->getUserAllTimePayout($payout->BasicUser->Cb->username) . "</b> — за все время на Cb";
    if ($payouts_for_model->sum('amount')) {
      $text .= PHP_EOL . PHP_EOL . "<b>$" . $payouts_for_model->sum('amount') . " (" . $payouts_for_model->sum('amount_payed_rub') . " руб)</b> — выплачено авансов:" . PHP_EOL . PHP_EOL;
      foreach ($payouts_for_model as $item) {
        $text .= date("d.m.y H:i", strtotime($item->status_updated_at)) . " <b>$" . $item->amount . " (" . $item->amount_payed_rub . " руб)</b>" . PHP_EOL;
      }
    }

    return [
      'reply_markup' => json_encode([
        'inline_keyboard' => $rows,
        'one_time_keyboard' => false,
        'resize_keyboard' => false,
      ]),
      'text' => $text,
    ];
  }

  public function buttons_payment_and_payouts($subMonths, $viewType) {
    $users = User::query();

    if ($subMonths !== "All") {
      $date_from = \Carbon\Carbon::now()->subMonthsWithOverflow($subMonths)->toDateString();
      $date_to = \Carbon\Carbon::now()->addDay()->toDateString();

      $users->with(['Payouts' => function ($query) use ($date_from, $date_to) {
        $query->where('status', 2);
        $query->whereBetween('status_updated_at', [
          $date_from,
          $date_to
        ]);
      }, "Cb"]);

      $title_range = "За период " . PHP_EOL . $date_from . " — " . $date_to;
    } else {
      $users->with(['Payouts' => function ($query) {
        $query->where('status', 2);
      }, "Cb"]);
      $title_range = "За все время";
    }

    $users->whereHas("Cb");

    $users = $users->get();

    $rows = [
      [
        ['text' => "🔙 Назад", 'callback_data' => "back"],
      ],
      [
        ['text' => ($subMonths === 'All' ? '[ Все ]' : 'Все'), 'callback_data' => "Allmonth"],
        ['text' => ($subMonths === '1' ? '[ 1 мес ]' : '1 мес'), 'callback_data' => "1month"],
        ['text' => ($subMonths === '2' ? '[ 2 мес ]' : '2 мес'), 'callback_data' => "2month"],
        ['text' => ($subMonths === '3' ? '[ 3 мес ]' : '3 мес'), 'callback_data' => "3month"],
      ],
      [
        ['text' => ($subMonths === '4' ? '[ 4 мес ]' : '4 мес'), 'callback_data' => "4month"],
        ['text' => ($subMonths === '5' ? '[ 5 мес ]' : '5 мес'), 'callback_data' => "5month"],
        ['text' => ($subMonths === '6' ? '[ 6 мес ]' : '6 мес'), 'callback_data' => "6month"],
      ],
      [
        ['text' => ($subMonths === '7' ? '[ 7 мес ]' : '7 мес'), 'callback_data' => "7month"],
        ['text' => ($subMonths === '8' ? '[ 8 мес ]' : '8 мес'), 'callback_data' => "8month"],
        ['text' => ($subMonths === '9' ? '[ 9 мес ]' : '9 мес'), 'callback_data' => "9month"],
      ],
      [
        ['text' => ($subMonths === '10' ? '[ 10 мес ]' : '10 мес'), 'callback_data' => "10month"],
        ['text' => ($subMonths === '11' ? '[ 11 мес ]' : '11 мес'), 'callback_data' => "11month"],
        ['text' => ($subMonths === '12' ? '[ 12 мес ]' : '12 мес'), 'callback_data' => "12month"],
      ],
      [
        ['text' => ($viewType === 'view_table' ? '[ Таблицей ]' : 'Таблицей'), 'callback_data' => "view_table"],
        ['text' => ($viewType === 'view_buttons' ? '[ Кнопками ]' : 'Кнопками'), 'callback_data' => "view_buttons"],
      ]
    ];


    if ($viewType === 'view_buttons') {
      $text = $title_range;
      $parse_mode = [];
      foreach ($users as $item) {
        $payout_from_cb = (new \App\Services\CbApiService)->getUserPayout($item->Cb->username, $subMonths === 'All' ? 60 : $subMonths);
        $rows[] = [
          [
            'text' => $item->Cb->username . " | Cb: $" . $payout_from_cb . " | Авансы: $" . $item->Payouts->sum('amount') . " ( " . $item->Payouts->sum('amount_payed_rub') . " руб )",
            'callback_data' => $item->id
          ],
        ];
      }
    }

    if ($viewType === 'view_table') {
      $table = [];
      foreach ($users as $item) {
        $payout_from_cb = (new \App\Services\CbApiService)->getUserPayout($item->Cb->username, $subMonths === 'All' ? 60 : $subMonths);
        $table[] = [
          'Cb user' => $item->Cb->username,
          'Cb $' => "$" . $payout_from_cb,
          'Авансы' => "$" . $item->Payouts->sum('amount') . " (" . $item->Payouts->sum('amount_payed_rub') . "р.)",
        ];
      }
      if ($table) {
        $text = "<pre>" . \Tridcatij\Asciitables\AsciitableFacade::make_table($table, "Статсистика" . PHP_EOL . $title_range, true) . "</pre>";
        $parse_mode = ['parse_mode' => "HTML"];
      } else {
        $text = "За период " . $title_range . " нет выплат";
        $parse_mode = [];
      }
    }

    return [
      'reply_markup' => json_encode([
        'inline_keyboard' => $rows,
        'one_time_keyboard' => false,
        'resize_keyboard' => false,
      ]),
      'text' => $text,
      'parse_mode' => $parse_mode,
    ];
  }

  public function buttons_payout_list_all($subMonths, $viewType) {
    $payouts = Payout::query();
    $payouts->with(["BasicUser", "BasicUser.Cb"]);
    $payouts->whereIn('status', [2, 3]);

    if ($subMonths !== "All") {
      $date_from = \Carbon\Carbon::now()->subMonthsWithOverflow($subMonths)->toDateString();
      $date_to = \Carbon\Carbon::now()->addDay()->toDateString();

      $payouts->whereBetween('status_updated_at', [
        $date_from,
        $date_to
      ]);

      $title_range = "за период " . PHP_EOL . $date_from . " — " . $date_to;
    } else {
      $title_range = "за все время";
    }

    $payouts = $payouts->get();

    $rows = [
      [
        ['text' => "🔙 Назад", 'callback_data' => "back"],
      ],
      [
        ['text' => ($subMonths === 'All' ? '[ Все ]' : 'Все'), 'callback_data' => "Allmonth"],
        ['text' => ($subMonths === '1' ? '[ 1 мес ]' : '1 мес'), 'callback_data' => "1month"],
        ['text' => ($subMonths === '2' ? '[ 2 мес ]' : '2 мес'), 'callback_data' => "2month"],
        ['text' => ($subMonths === '3' ? '[ 3 мес ]' : '3 мес'), 'callback_data' => "3month"],
      ],
      [
        ['text' => ($subMonths === '4' ? '[ 4 мес ]' : '4 мес'), 'callback_data' => "4month"],
        ['text' => ($subMonths === '5' ? '[ 5 мес ]' : '5 мес'), 'callback_data' => "5month"],
        ['text' => ($subMonths === '6' ? '[ 6 мес ]' : '6 мес'), 'callback_data' => "6month"],
      ],
      [
        ['text' => ($subMonths === '7' ? '[ 7 мес ]' : '7 мес'), 'callback_data' => "7month"],
        ['text' => ($subMonths === '8' ? '[ 8 мес ]' : '8 мес'), 'callback_data' => "8month"],
        ['text' => ($subMonths === '9' ? '[ 9 мес ]' : '9 мес'), 'callback_data' => "9month"],
      ],
      [
        ['text' => ($subMonths === '10' ? '[ 10 мес ]' : '10 мес'), 'callback_data' => "10month"],
        ['text' => ($subMonths === '11' ? '[ 11 мес ]' : '11 мес'), 'callback_data' => "11month"],
        ['text' => ($subMonths === '12' ? '[ 12 мес ]' : '12 мес'), 'callback_data' => "12month"],
      ],
      [
        ['text' => ($viewType === 'view_table' ? '[ Таблицей ]' : 'Таблицей'), 'callback_data' => "view_table"],
        ['text' => ($viewType === 'view_buttons' ? '[ Кнопками ]' : 'Кнопками'), 'callback_data' => "view_buttons"],
      ]
    ];


    if ($viewType === 'view_buttons') {
      $text = "Выплаты " . $title_range;
      $parse_mode = [];
      foreach ($payouts as $item) {
        $rows[] = [
          [
            'text' => __('bot.payout_status_' . $item->status . '_emoji') . " " . $item->amount_payed_rub . " руб. |  " . $item->BasicUser->Cb->username . " | " . date("d.m.y", strtotime($item->status_updated_at)),
            'callback_data' => $item->id
          ],
        ];
      }
    }

    if ($viewType === 'view_table') {
      $table = [];
      foreach ($payouts as $item) {
        $table[] = [
          'Дата' => date("d.m.y", strtotime($item->status_updated_at)),
          'Cb user' => $item->BasicUser->Cb->username,
          'Сумма р.' => __('bot.payout_status_' . $item->status . '_icon') . $item->amount_payed_rub,
        ];
      }
      if ($table) {
        $text = "<pre>" . \Tridcatij\Asciitables\AsciitableFacade::make_table($table, "Выплаты" . PHP_EOL . $title_range, true) . "</pre>";
        $parse_mode = ['parse_mode' => "HTML"];
      }
    }

    if (!$payouts->count()) {
      $text = "За период " . $title_range . " нет выплат";
      $parse_mode = [];
    }

    return [
      'reply_markup' => json_encode([
        'inline_keyboard' => $rows,
        'one_time_keyboard' => false,
        'resize_keyboard' => false,
      ]),
      'text' => $text,
      'parse_mode' => $parse_mode,
    ];
  }

  public function buttons_payout_list_only_new() {
    $payouts = Payout::with(["BasicUser", "BasicUser.Cb"])
      ->where('status', 0)
      ->get();

    $rows = [
      [
        ['text' => "🔙 Назад", 'callback_data' => "back"],
      ]
    ];
    foreach ($payouts as $item) {
      $rows[] = [
        ['text' => "$" . $item->amount . " (" . __('bot.payout_type_' . $item->type . '_short') . ") — " . $item->BasicUser->Cb->username, 'callback_data' => $item->id],
      ];
    }
    return json_encode([
      'inline_keyboard' => $rows,
      'one_time_keyboard' => false,
      'resize_keyboard' => false,
    ]);
  }

  // Triggered in SuperadminConversation::payouts
  // public function payoutsTable_admin($subMonths) {

  //   $payouts = Payout::query();
  //   $payouts->with(["BasicUser", "BasicUser.Tg", "BasicUser.Cb"]);
  //   if ($subMonths) {
  //     $date_from = \Carbon\Carbon::now()->subMonthsWithOverflow($subMonths)->toDateString();
  //     $date_to = \Carbon\Carbon::now()->addDay()->toDateString();

  //     $payouts->whereBetween('updated_at', [
  //       $date_from,
  //       $date_to
  //     ]);

  //     $time_title = $date_from . " - " . $date_to;
  //   } else {
  //     $time_title = "за все время";
  //   }
  //   $payouts = $payouts->get();

  //   $table = [];
  //   $result = "";
  //   if ($payouts->count()) {

  //     $has_new = false;
  //     $has_rejected = false;

  //     foreach ($payouts as $item) {
  //       $table[] = [
  //         "Id" => $item->id,
  //         "Дата" => date("d.m.y", strtotime($item->updated_at)),
  //         "Ник Cb" => $item->BasicUser->Cb->username,
  //         "Платеж" => __("bot.payout_status_" . $item->status . "_icon") . "$" . $item->amount,
  //         "Куда" => __("bot.payout_type_" . $item->type . "_short"),
  //       ];

  //       if ($item->status == 0) {
  //         $has_new = true;
  //       }
  //       if ($item->status == 3) {
  //         $has_rejected = true;
  //       }
  //     }

  //     $result .= \Tridcatij\Asciitables\AsciitableFacade::make_table($table, "Выплаты" . PHP_EOL . $time_title, true);

  //     if ($has_new) {
  //       $result .= "Пометка ~ означает, что  платеж в процессе обработки;" . PHP_EOL;
  //     }
  //     if ($has_rejected) {
  //       $result .= "Пометка x означает, что  платеж отклонен;" . PHP_EOL;
  //     }
  //     if ($has_new || $has_rejected) {
  //       $result .= "Если платеж без пометки, значит веньги отправлены;" . PHP_EOL;
  //     }
  //   } else {
  //     $result .= "Нет данных за период " . $time_title;
  //   }
  //   return $result;
  // }

}
