<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Tridcatij\Asciitables\AsciitableFacade as Asciitable;
use App\User;
use Illuminate\Support\Facades\Cache;

class CbApiService {

  public function getUserStatistic($subMonths = 3, User $user, $cb_username = '') {
    $CbData = $this->get(['stats_breakdown' => 'date', 'search_criteria' => 3], $subMonths);

    $total_paid_payouts_to_user = $user->Payouts->where('status', 1)->sum("amount");
    $total_new_payouts_to_user = $user->Payouts->where('status', 0)->sum("amount");

    $total_earned_in_cb = 0;

    $payouts = $user->Payouts();

    $merged_data = [];
    foreach ($payouts as $item) {
      $timestamp = strtotime($item['updated_at']);
      $merged_data[] = [
        "is" => "payout",
        "amount" => $item['amount'],
        "date" => $timestamp,
      ];
    }

    if (!$CbData['stats']) {
      return [];
    } else {
      foreach ($CbData['stats'][0]['rows'] as $acc) {
        if ($user->Cb->username === $acc[0]) {
          $timestamp = strtotime($item[0]);
          $merged_data[] = [];
        }
      }
    }
    return "";
  }

  // (new \App\Services\CbApiService)->getUserAllTimePayout();
  public function getUserAllTimePayout(String $cb_username) {
    return $this->getUserPayout($cb_username, 60);
  }

  // (new \App\Services\CbApiService)->getUserPayout();
  public function getUserPayout(String $cb_username, $subMonths) {
    $result = "";
    $data = $this->get(['stats_breakdown' => 'sub_account__username', 'search_criteria' => 3], $subMonths);

    $data_table = [];

    if (!$data['stats']) {
      return 0;
    } else {
      foreach ($data['stats'][0]['rows'] as $acc) {
        if ($cb_username === $acc[0]) {
          return $acc[2];
        }
      }
    }
  }



  public function getAccauntStatistic($subMonths, $cb_username = '') {
    $result = "";
    $data = $this->get(['stats_breakdown' => 'sub_account__username', 'search_criteria' => 3], $subMonths);

    if ($subMonths == 60) {
      $range_text = "за все время";
    } else {
      $range_text = $data['range']['start_date'] . " - " . $data['range']['end_date'];
    }

    $data_table = [];

    if (!$data['stats']) {

      if ($subMonths == 60) {
        $result .= "Нет данных " . $range_text;
      } else {
        $result .= "Нет данных за период " . PHP_EOL . $range_text;
      }
    } else {
      foreach ($data['stats'][0]['rows'] as $acc) {
        if ($cb_username && $cb_username === $acc[0]) {
          $data_table[] = ['Cb' => $acc[0], 'Cb pay' => $acc[2], 'Авансы' => "..."];
        } elseif (!$cb_username) {
          $data_table[] = ['Cb' => $acc[0], 'Cb pay' => $acc[2], 'Авансы' => "..."];
        }
      }
      $result = Asciitable::make_table($data_table, "Статистика" . PHP_EOL . $range_text, true);
    }
    return $result;
  }

  public function get($parameters, $subMonths = 1) {
    //dd($parameters);
    $client = new Client();
    $url = "https://chaturbate.com/affiliates/apistats/";
    $basic_params = [
      'username' => 'high_studio',
      'token' => 'JLQdMYoVrhnqUuBr756Tw5uH',
    ];
    $dates = [
      "date_month" => Carbon::now()->month,
      "date_day" => Carbon::now()->day,
      "date_year" => Carbon::now()->year,
      "end_date_month" => Carbon::now()->month,
      "end_date_day" => Carbon::now()->day,
      "end_date_year" => Carbon::now()->year,
      "start_date_month" => Carbon::now()->subMonthsWithOverflow($subMonths)->month,
      "start_date_day" => Carbon::now()->subMonthsWithOverflow($subMonths)->day,
      "start_date_year" => Carbon::now()->subMonthsWithOverflow($subMonths)->year,
    ];

    //dd($dates);
    $query = array_merge($basic_params, $dates, $parameters);

    $cache_key = "CbApi" . sha1(json_encode($query));

    $data = Cache::get($cache_key);

    if (!$data) {
      $res = $client->request('GET', $url, [
        'proxy' => "http://o5NCcW:2WhWUT@138.59.204.119:9449", // https://proxy6.net/user/proxy
        'query' => $query
      ]);
      $data = json_decode($res->getBody(), true);

      Cache::put($cache_key, $data, 10);
    }
    return $data;

    /*
API PARAMS
stats_breakdown = sub_account__username | date





*/
  }
}
